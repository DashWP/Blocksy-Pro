<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class FiltersBlock {
	public function __construct() {
		add_action('init', [$this, 'register_block']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_admin']);
		add_action('wp_ajax_blc_ext_filters_get_block_data', [
			$this,
			'get_block_data',
		]);
	}

	public function register_block() {
		register_block_type('blocksy/woocommerce-filters', [
			'render_callback' => function ($attributes, $content, $block) {
				if (
					! is_woocommerce()
					&&
					! wp_doing_ajax()
					||
					(
						is_singular()
						&&
						! is_archive()
					)
				) {
					return '';
				}

				$filter = BaseFilter::get_filter_for($attributes);

				if (! $filter) {
					return '';
				}

				$presenter = new FilterPresenter($filter);

				return $presenter->render();
			},
		]);
	}

	public function get_block_data() {
		$body = json_decode(file_get_contents('php://input'), true);

		if (! $body || ! isset($body['type'])) {
			wp_send_json_error();
		}

		if ($body['type'] === 'categories' && ! isset($body['taxonomy'])) {
			wp_send_json_error();
		}

		if ($body['type'] === 'attributes' && ! isset($body['attribute'])) {
			wp_send_json_error();
		}

		$terms = [];
		$can_display_preview = true;

		$filter = BaseFilter::get_filter_for($body, [
			'ignore_current_query' => true
		]);

		if ($filter && $body['type'] === 'categories') {
			$lookup_table = blc_get_ext('woocommerce-extra')
				->filters
				->lookup_table;

			if ($lookup_table->can_use_lookup_table()) {
				$terms = $filter->get_terms_for_all_products();
			} else {
				$last_product_id = $lookup_table->get_last_existing_product_id();

				if ($last_product_id) {
					$can_display_preview = false;
				}
			}
		}

		if ($filter && $body['type'] === 'attributes') {
			$result = $filter->get_attributes_counts(
				$filter->filter_get_terms_list()
			);

			$terms = [];

			foreach ($result as $term) {
				$term_as_array = (array) $term;

				$term_as_array['meta'] = blocksy_get_taxonomy_options(
					$term->term_id
				);

				$terms[] = $term_as_array;
			}
		}

		$all_taxonomies = blc_get_ext('woocommerce-extra')
			->utils
			->get_product_taxonomies();

		$product_taxonomies = [];

		foreach ($all_taxonomies as $taxonomy) {
			$labels = get_taxonomy_labels(get_taxonomy($taxonomy));

			$product_taxonomies[$taxonomy] = [
				'taxonomy' => $taxonomy,
				'name' => $labels->name,
				'is_taxonomy_hierarchical' => is_taxonomy_hierarchical(
					$taxonomy
				),
			];
		}

		$storage = new Storage();
		$settings = $storage->get_settings();

		$conf = new SwatchesConfig();

		$attribute_taxonomies = wc_get_attribute_taxonomies();

		foreach ($attribute_taxonomies as $key => $attributes_tax) {
			$attribute_taxonomies[$key] = array_merge(
				(array) $attributes_tax,
				[
					'type' => $conf->get_attribute_type(
						$attributes_tax->attribute_name
					),
				]
			);
		}

		wp_send_json_success([
			'terms' => $terms,
			'can_display_preview' => $can_display_preview,

			'attributes_tax' => $attribute_taxonomies,
			'product_taxonomies' => $product_taxonomies,

			'ct_color_swatch_shape' => blocksy_get_theme_mod(
				'color_swatch_shape',
				'round'
			),
			'ct_image_swatch_shape' => blocksy_get_theme_mod(
				'image_swatch_shape',
				'round'
			),
			'ct_button_swatch_shape' => blocksy_get_theme_mod(
				'button_swatch_shape',
				'round'
			),
			'ct_mixed_swatch_shape' => blocksy_get_theme_mod(
				'mixed_swatch_shape',
				'round'
			),

			'has_swatches' => !! $settings['features']['variation-swatches'],
		]);
	}

	public function enqueue_admin() {
		$data = get_plugin_data(BLOCKSY__FILE__);

		$deps = [
			'wp-blocks',
			'wp-element',
			'wp-block-editor'
		];

		global $wp_customize;

		if ($wp_customize) {
			$deps[] = 'ct-customizer-controls';
		} else {
			$deps[] = 'ct-options-scripts';
		}

		wp_enqueue_script(
			'blocksy/woocommerce-filters',
			BLOCKSY_URL .
				'framework/premium/extensions/woocommerce-extra/static/bundle/woocommerce-filters.js',
			$deps,
			$data['Version']
		);
	}
}
