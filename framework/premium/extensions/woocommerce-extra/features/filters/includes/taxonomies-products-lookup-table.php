<?php

namespace Blocksy\Extensions\WoocommerceExtra;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

class FiltersTaxonomiesProductsLookupTable {
	private $lookup_table_name = '';

	private $state_option_name = 'blocksy_taxonomy_lookup_regeneration_state';
	private $scheduled_action_name = 'blocksy_run_product_taxonomy_lookup_regeneration_callback';

	private $store = null;

	private $triggered_from_init = false;

	public function __construct() {
		global $wpdb;
		$this->lookup_table_name = $wpdb->prefix . 'blocksy_product_taxonomies_lookup';

		$this->store = new FiltersTaxonomiesProductsLookupStore($this->lookup_table_name);

		add_filter(
			'woocommerce_debug_tools',
			[$this, 'add_initiate_regeneration_entry_to_tools_array'],
			5
		);


		add_action(
			'init',
			function () {
				$state = $this->get_regeneration_state();

				if ($state['state'] === 'progress') {
					$queue = WC()->get_instance_of(\WC_Queue::class);

					$pending = $queue->search([
						'hook' => $this->scheduled_action_name,
						'status' => \ActionScheduler_Store::STATUS_PENDING
					]);

					$failed = $queue->search([
						'hook' => $this->scheduled_action_name,
						'status' => \ActionScheduler_Store::STATUS_FAILED
					]);

					if (empty($pending) && ! empty($failed)) {
						$this->finalize_regeneration(false);
					}

					return;
				}

				if (
					! $this->check_lookup_table_exists()
					||
					(
						! $this->lookup_table_has_data()
						&&
						$state['state'] === 'idle'
					)
				) {
					$this->initiate_regeneration('on_init');
					$this->triggered_from_init = true;
				}
			}
		);

		// TODO: introduce action after update is done
		add_action(
			'blocksy:cache-manager:purge-all',
			function () {
				if (! $this->check_lookup_table_exists()) {
					return;
				}

				if ($this->triggered_from_init) {
					return;
				}

				$state = $this->get_regeneration_state();

				if ($state['state'] === 'idle' && ! $state['enabled']) {
					$this->initiate_regeneration(
						'on_cache_purge:state_was_disabled'
					);

					return;
				}

				if ($state['state'] === 'progress') {
					return;
				}

				if (
					$this->lookup_table_has_data()
					||
					! $this->get_last_existing_product_id()
				) {
					$this->finalize_regeneration($this->can_use_lookup_table());
				} else {
					$this->initiate_regeneration('on_cache_purge');
				}
			}
		);

		add_action(
			$this->scheduled_action_name,
			[$this, 'run_regeneration_step_callback']
		);
	}

	public function get_table_name() {
		return $this->lookup_table_name;
	}

	public function can_use_lookup_table() {
		$state = $this->get_regeneration_state();

		return (
			$state['state'] === 'idle'
			&&
			$state['enabled']
		);
	}

	public function check_lookup_table_exists() {
		global $wpdb;

		$query = $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->esc_like($this->lookup_table_name)
		);

		return $this->lookup_table_name === $wpdb->get_var($query);
	}

	public function get_regeneration_state() {
		return get_option($this->state_option_name, [
			// Maybe introduce version here in order to be able to handle
			// changes in the state structure in the future.

			// 1. default
			// 2. progress
			// 3. aborted
			// 4. idle
			'state' => 'default',

			// Relevant only when state is 'idle':
			// 'enabled' => false,

			// Relevant only when state is 'progress':
			// 'processed_count' => 0,
			// 'last_product_id' => 0,
		]);
	}

	public function set_regeneration_state($state) {
		$previous_state = $this->get_regeneration_state();

		update_option($this->state_option_name, $state);

		wc_get_logger()->debug(
			'FiltersTaxonomiesProductsLookupTable:set_regeneration_state',
			[
				'source' => 'taxonomies_lookup_table',
				'previous_state' => $previous_state,
				'state' => $state
			]
		);
	}

	public function initiate_regeneration($reason = 'default') {
		$state = $this->get_regeneration_state();

		wc_get_logger()->info(
			'FiltersTaxonomiesProductsLookupTable:start_regeneration:before',
			[
				'source' => 'taxonomies_lookup_table',
				'state' => $state,
				'reason' => $reason,
				'triggered_from_init' => $this->triggered_from_init
			]
		);

		$queue = WC()->get_instance_of(\WC_Queue::class);
		$queue->cancel_all($this->scheduled_action_name);

		$this->delete_all_taxonomies_lookup_data();

		$products_exist = $this->initialize_table_and_data();

		if ($products_exist) {
			$this->enqueue_regeneration_step_run();
		} else {
			$this->finalize_regeneration(true);
		}
	}

	private function finalize_regeneration($enable_usage) {
		$state = $this->get_regeneration_state();

		$this->set_regeneration_state([
			'state' => 'idle',
			'enabled' => $enable_usage
		]);

		wc_get_logger()->info(
			'FiltersTaxonomiesProductsLookupTable:finalize_regeneration',
			[
				'source' => 'taxonomies_lookup_table',
				'state' => $state,
				'result' => $enable_usage
			]
		);
	}

	public function lookup_table_has_data() {
		global $wpdb;

		return (
			(int) $wpdb->get_var(
				"SELECT EXISTS (SELECT 1 FROM {$this->lookup_table_name})"
			)
		) !== 0;
	}

	public function get_last_existing_product_id() {
		$last_existing_product_id_array = WC()->call_function(
			'wc_get_products',
			[
				'return'  => 'ids',
				'limit'   => 1,
				'orderby' => [
					'ID' => 'DESC',
				]
			]
		);

		if (empty($last_existing_product_id_array)) {
			return null;
		}

		return current($last_existing_product_id_array);
	}

	private function initialize_table_and_data() {
		$database_util = wc_get_container()->get(DatabaseUtil::class);
		$database_util->dbdelta($this->get_table_creation_sql());

		$last_existing_product_id = $this->get_last_existing_product_id();

		if (! $last_existing_product_id) {
			// No products exist, nothing to (re)generate.
			return false;
		}

		$this->set_regeneration_state([
			'state' => 'progress',
			'processed_count' => 0,
			'last_product_id' => $last_existing_product_id
		]);

		wc_get_logger()->info(
			'FiltersTaxonomiesProductsLookupTable:start_regeneration',
			[
				'source' => 'taxonomies_lookup_table',
				'last_existing_product_id' => $last_existing_product_id
			]
		);

		return true;
	}

	private function delete_all_taxonomies_lookup_data() {
		global $wpdb;

		if ($this->check_lookup_table_exists()) {
			$wpdb->query("TRUNCATE TABLE {$this->lookup_table_name}");
		}
	}

	public function get_table_creation_sql() {
		global $wpdb;

		$collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';

		return "CREATE TABLE {$this->lookup_table_name} (
			product_id bigint(20) NOT NULL,
			taxonomy varchar(32) NOT NULL,
			term_id bigint(20) NOT NULL,
			PRIMARY KEY  (`term_id`, `product_id`, `taxonomy`)
) $collate;";
	}

	private function enqueue_regeneration_step_run() {
		$queue = WC()->get_instance_of(\WC_Queue::class);

		$queue->schedule_single(
			WC()->call_function('time') + 1,
			$this->scheduled_action_name,
			[],
			'woocommerce-db-updates'
		);
	}

	/**
	 * Action scheduler callback, performs one regeneration step and then
	 * schedules the next step if necessary.
	 */
	public function run_regeneration_step_callback() {
		$state = $this->get_regeneration_state();

		if ($state['state'] !== 'progress') {
			$this->finalize_regeneration(false);
			return;
		}

		$result = $this->do_regeneration_step();

		if ($result) {
			$this->enqueue_regeneration_step_run();
		} else {
			$this->finalize_regeneration(true);
		}
	}

	/**
	 * Perform one regeneration step: grabs a chunk of products and creates
	 * the appropriate entries for them in the lookup table.
	 *
	 * @return bool True if more steps need to be run, false otherwise.
	 */
	private function do_regeneration_step() {
		$products_per_generation_step = 30;

		$state = $this->get_regeneration_state();

		$products_already_processed = 0;

		if (isset($state['processed_count'])) {
			$products_already_processed = $state['processed_count'];
		}

		$product_ids = WC()->call_function(
			'wc_get_products',
			[
				'limit' => $products_per_generation_step,
				'offset' => $products_already_processed,
				[
					'ID' => 'ASC',
				],
				'return' => 'ids',
			]
		);

		$last_product_id_to_process = PHP_INT_MAX;

		if (isset($state['last_product_id'])) {
			$last_product_id_to_process = $state['last_product_id'];
		}

		$last = end($product_ids);

		wc_get_logger()->debug(
			'FiltersTaxonomiesProductsLookupTable:do_regeneration_step',
			[
				'source' => 'taxonomies_lookup_table',
				'product_ids' => $product_ids,
				'last' => $last,
				'result' => $last < $last_product_id_to_process
			]
		);

		if (! is_array($product_ids) || empty($product_ids)) {
			return false;
		}

		foreach ($product_ids as $id) {
			try {
				$this->store->create_data_for_product($id);
			} catch (\Exception $e) {
				wc_get_logger()->error(
					'FiltersTaxonomiesProductsLookupTable:do_regeneration_step:error',
					[
						'source' => 'taxonomies_lookup_table',
						'product_id' => $id,
						'error' => $e->getMessage()
					]
				);

				continue;
			}
		}

		$products_already_processed += count($product_ids);

		$this->set_regeneration_state([
			'state' => 'progress',
			'processed_count' => $products_already_processed,
			'last_product_id' => $last_product_id_to_process
		]);

		return $last < $last_product_id_to_process;
	}

	public function get_product_ids() {
		global $wpdb;

		if (! $this->can_use_lookup_table()) {
			return [];
		}

		$query = $wpdb->prepare(
			'SELECT DISTINCT product_id FROM %i',
			$this->lookup_table_name
		);

		return array_column(
			$wpdb->get_results($query, ARRAY_A),
			'product_id'
		);
	}

	public function add_initiate_regeneration_entry_to_tools_array($tools_array) {
		if (! $this->check_lookup_table_exists()) {
			return $tools_array;
		}

		$state = $this->get_regeneration_state();

		$entry = [
			'name' => __('Regenerate the product taxonomies lookup table', 'blocksy-companion'),
			'desc' => __('This tool will regenerate the product taxonomies lookup table data from existing product(s) data. This process may take a while.', 'blocksy-companion'),
			'requires_refresh' => true,
			'callback' => function() {
				$state = $this->get_regeneration_state();

				if ($state['state'] === 'progress') {
					throw new \Exception("Can't do product taxonomies lookup data regeneration: regeneration is already in progress");
				}

				$this->initiate_regeneration('manual');

				return __('Product taxonomies lookup table data is regenerating', 'blocksy-companion');
			}
		];

		if ($state['state'] === 'progress') {
			$entry['button'] = sprintf(
				/* translators: %d: How many products have been processed so far. */
				__('Filling in progress (%d)', 'blocksy-companion'),
				$state['processed_count']
			);

			$entry['disabled'] = true;
		} else {
			$entry['button'] = __('Regenerate', 'blocksy-companion');
		}

		$tools_array['regenerate_product_taxonomies_lookup_table'] = $entry;

		if ($state['state'] === 'progress') {
			$entry = [
				'name' => __('Abort the product taxonomies lookup table regeneration', 'blocksy-companion'),
				'desc' => __('This tool will abort the regenerate product taxonomies lookup table regeneration. After this is done the process can be either started over, or resumed to continue where it stopped.', 'blocsky-companion'),
				'requires_refresh' => true,
				'callback' => function() {
					$state = $this->get_regeneration_state();

					if ($state['state'] !== 'progress') {
						throw new \Exception("Can't do product taxonomies lookup data regeneration abort: regeneration is not in progress");
					}

					// $this->abort_regeneration_from_tools_page();
					$queue = WC()->get_instance_of(\WC_Queue::class);
					$queue->cancel_all($this->scheduled_action_name);

					$this->set_regeneration_state([
						'state' => 'aborted',
						'processed_count' => $state['processed_count'],
						'last_product_id' => $state['last_product_id']
					]);

					return __('Product taxonomies lookup table regeneration process has been aborted.', 'blocksy-companion');
				},
				'button' => __('Abort', 'blocksy-companion'),
			];

			$tools_array['abort_product_taxonomies_lookup_table_regeneration'] = $entry;
		} elseif ($state['state'] === 'aborted') {
			$entry = [
				'name' => __('Resume the product taxonomies lookup table regeneration', 'blocksy-companion'),
				'desc' => sprintf(
					/* translators: %1$s = count of products already processed. */
					__('This tool will resume the product taxonomies lookup table regeneration at the point in which it was aborted (%1$s products were already processed).', 'blocksy-companion'),
					$state['processed_count']
				),
				'requires_refresh' => true,
				'callback' => function() {
					$state = $this->get_regeneration_state();

					if ($state['state'] !== 'aborted') {
						throw new \Exception("Can't do product taxonomies lookup data regeneration resume: regeneration is not in aborted state");
					}

					$this->set_regeneration_state([
						'state' => 'progress',
						'processed_count' => $state['processed_count'],
						'last_product_id' => $state['last_product_id']
					]);

					$this->enqueue_regeneration_step_run();

					return __('Product taxonomies lookup table regeneration process has been resumed.', 'blocksy-companion');
				},
				'button' => __('Resume', 'blocksy-companion'),
			];

			$tools_array['resume_product_taxonomies_lookup_table_regeneration'] = $entry;
		}

		return $tools_array;
	}
}
