<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class ActiveFilters {
	public function __construct() {
		add_filter(
			'blocksy:options:woocommerce:archive:active-filters',
			function ($opts) {
				$opts[] = [
					'woo_has_active_filters' => [
						'label' => __('Active Filters', 'blocksy-companion'),
						'type' => 'ct-panel',
						'switch' => true,
						'value' => 'no',
						'sync' => blocksy_sync_whole_page([
							'prefix' => 'woo_categories',
							'loader_selector' => '.ct-container > section'
						]),
						'inner-options' => [
							'woo_has_active_filters_label' => [
								'label' => __( 'Active Filters Label', 'blocksy-companion' ),
								'type' => 'ct-switch',
								'value' => 'yes',
								'divider' => 'top',
							],

							blocksy_rand_md5() => [
								'type' => 'ct-condition',
								'condition' => [ 'woo_has_active_filters_label' => 'yes' ],
								'options' => [

									'woo_active_filters_label' => [
										'label' => false,
										'type' => 'text',
										'design' => 'block',
										'value' => __('Active Filters', 'blocksy-companion'),
										'disableRevertButton' => true,
										'sync' => 'live',
									],

								],
							],

						],
					]
				];

				return $opts;
			},
			50
		);

		add_action(
			'woocommerce_before_shop_loop',
			function () {
				if (blocksy_get_theme_mod('woo_has_active_filters', 'no') === 'no') {
					return;
				}

				add_action(
					'woocommerce_before_shop_loop',
					[$this, 'active_filters'],
					105
				);
			},
			0
		);

		add_action(
			'woocommerce_no_products_found',
			function () {
				if (blocksy_get_theme_mod('woo_has_active_filters', 'no') === 'no') {
					return;
				}

				add_action(
					'woocommerce_no_products_found',
					[$this, 'active_filters'],
					9
				);
			},
			0
		);

		add_action('init', [$this, 'blocksy_active_filters_block']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_admin']);
	}

	public function blocksy_active_filters_block() {
		register_block_type('blocksy/active-filters', [
			'render_callback' => function ($attributes, $content, $block) {
				$attributes = wp_parse_args($attributes, [
					'layout' => 'list',
					'showResetButton' => 'yes',
					'has_label' => false
				]);

				ob_start();
				$this->active_filters($attributes);
				$filters = ob_get_clean();

				if (empty($filters)) {
					return '';
				}

				return $filters;
			},
		]);
	}

	public function enqueue_admin() {
		$data = get_plugin_data(BLOCKSY__FILE__);

		wp_enqueue_script(
			'blocksy/active-filters',
			BLOCKSY_URL .
				'framework/premium/extensions/woocommerce-extra/static/bundle/active-filters.js',
			['wp-blocks', 'wp-element', 'wp-block-editor'],
			$data['Version']
		);
	}

	public function active_filters($attributes = []) {

		if ($attributes === '') {
			$attributes = [
				'layout' => 'inline',
				'showResetButton' => 'yes',
				'has_label' => true,
			];
		}

		$applied_filters = $this->get_applied_filters();

		if (count($applied_filters) < 1) {
			return;
		}

		$content = '';

		if ($attributes['layout'] === 'inline') {
			$content = blocksy_render_view(
				dirname(__FILE__) . '/views/inline-filter.php',
				array_merge(
					$attributes,
					[
						'applied_filters' => $applied_filters,
						'reset_url' => BaseFilter::get_url_without_filters()
					]
				)
			);
		} else {
			$content = blocksy_render_view(
				dirname(__FILE__) . '/views/list-filter.php',
				array_merge(
					$attributes,
					[
						'applied_filters' => $applied_filters,
						'reset_url' => BaseFilter::get_url_without_filters()
					]
				)
			);
		}

		echo $content;
	}

	public function get_applied_filters() {
		$result = [];

		$to_try = ['categories', 'attributes'];
		$params = FiltersUtils::get_query_params();

		$price_filter_added = false;

		foreach ($params['params'] as $key => $value) {
			$values = explode(',', $value);

			foreach ($to_try as $filter_type) {
				$filter = BaseFilter::get_filter_for([
					'type' => $filter_type,
					'taxonomy' => $filter_type === 'attributes' ? $key : str_replace('filter_', '', $key)
				]);

				if (! $filter) {
					continue;
				}

				$filter_name = __('Categories', 'blocksy-companion');
				$maybe_taxonomy_name = str_replace('filter_', '', $key);

				if ($filter_type === 'attributes') {
					$maybe_taxonomy_name = wc_attribute_taxonomy_name($maybe_taxonomy_name);
				}

				if (taxonomy_exists($maybe_taxonomy_name)) {
					$labels = get_taxonomy_labels(get_taxonomy($maybe_taxonomy_name));

					if (isset($labels->singular_name)) {
						$filter_name = $labels->singular_name;
					}
				}

				$items = [];

				foreach ($values as $single_value) {
					$descriptor = $filter->get_applied_filter_descriptor(
						$key,
						$single_value
					);

					if ($descriptor) {
						$items[] = $descriptor;
					}
				}

				if (empty($items)) {
					continue;
				}

				$result[] = [
					'name' => $filter_name,
					'items' => $items
				];
			}

			if (
				(
					$key === 'min_price'
					||
					$key === 'max_price'
				)
				&&
				! $price_filter_added
			) {
				$price_filter_added = true;

				$prices = PriceFilter::get_filtered_price();

				if (! $prices) {
					continue;
				}

				$result[] = [
					'name' => __('Price', 'blocksy-companion'),
					'items' => [
						[
							'name' => blocksy_safe_sprintf(
								'%s - %s',
								wc_price(
									max(blocksy_akg('min_price', $params['params'], $prices['min']), $prices['min']),
									[
										'decimals' => 0
									]
								),
								wc_price(
									min(blocksy_akg('max_price', $params['params'], $prices['max']), $prices['max']),
									[
										'decimals' => 0
									]
								)
							),
							'value' => '',
							'href' => PriceFilter::get_reset_url()
						]
					]
				];
			}
		}

		return $result;
	}
}

