<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class PriceFilter {
	static $cached_prices = '__DEFAULT__';

	public function __construct() {
        add_action('init', [$this, 'blocksy_price_filter_block']);

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			$data['blocksy_woo_extra_price_filters'] = [
				'currency' => html_entity_decode(get_woocommerce_currency_symbol()),
				'priceFormat' => html_entity_decode(get_woocommerce_price_format()),
			];

			return $data;
		});

        add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			if (!class_exists('WC_AJAX')) {
				return $chunks;
			}

            $chunks[] = [
				'id' => 'blocksy_ext_woo_extra_price_filters',
				'selector' => '.ct-price-filter',
				'trigger' => [
					[
						'trigger' => 'change',
						'selector' => '.ct-price-filter-slider input[type="range"]',
					],

					[
						'trigger' => 'input',
						'selector' => '.ct-price-filter-slider input[type="range"]',
					],
				],
				'url' => blocksy_cdn_url(
					BLOCKSY_URL .
						'framework/premium/extensions/woocommerce-extra/static/bundle/price-filter-public.js'
				),
			];

            return $chunks;
        });
    }

	static function get_filtered_price() {
		if (self::$cached_prices !== '__DEFAULT__') {
			return self::$cached_prices;
		}

		$apply_filters = new ApplyFilters();

		$query_params = FiltersUtils::get_query_params()['params'];

		unset($query_params['min_price']);
		unset($query_params['max_price']);

		$products_query = $apply_filters->get_custom_query_for($query_params);

		$products = $products_query->posts;

		if (empty($products)) {
			self::$cached_prices = null;

			return null;
		}

		$wc_filters = new \Automattic\WooCommerce\StoreApi\Utilities\ProductQueryFilters();

		$request = new \WP_REST_Request('GET', '/wp/v2/posts');

		$request->set_param('include', $products);

		$prices = $wc_filters->get_filtered_price($request);

		self::$cached_prices = [
			'min' => floor($prices->min_price),
			'max' => ceil($prices->max_price)
		];

		return self::$cached_prices;
	}

	static function get_reset_url() {
		$params = FiltersUtils::get_query_params();

		$url = $params['url'];
		$params = $params['params'];

		unset($params['min_price']);
		unset($params['max_price']);

		$url = remove_query_arg(
			[
				'min_price',
				'max_price'
			],
			$url
		);

		return $url;
	}

	public function blocksy_price_filter_block() {
		register_block_type('blocksy/woocommerce-price-filter', [
			'render_callback' => function ($attributes, $content, $block) {
				if (
					! is_woocommerce()
					&&
					! wp_doing_ajax()
					||
					is_singular()
				) {
					return '';
				}

				$attributes = wp_parse_args($attributes, [
					'showTooltips' => true,
					'showPrices' => true,
					'showResetButton' => false
				]);

				$prices = self::get_filtered_price();

				if (! $prices) {
					return '';
				}

				$max_range = $prices['max'] - $prices['min'];

				if (intval($max_range) === 0) {
					return '';
				}

				$min_price = max(blocksy_akg('min_price', $_GET, $prices['min']), $prices['min']);
				$max_price = min(blocksy_akg('max_price', $_GET, $prices['max']), $prices['max']);

				$leftStylePos = max(0, (($min_price - $prices['min']) / $max_range) * 100);
				$rightStylePos = min(100, (($max_price - $prices['min']) / $max_range) * 100);

				$currency = get_woocommerce_currency_symbol();
				$price_format = get_woocommerce_price_format();

				$need_reset = (
					isset($_GET['min_price'])
					&&
					$_GET['min_price'] !== $prices['min']
				) ||
				(
					isset($_GET['max_price'])
					&&
					$_GET['max_price'] !== $prices['max']
				);

				$reset_button = '';

				if (
					$need_reset
					&&
					$attributes['showResetButton']
				) {
					$reset_button = blocksy_html_tag(
						'div',
						[
							'class' => 'ct-filter-reset'
						],
						blocksy_html_tag(
							'a',
							[
								'href' => self::get_reset_url(),
								'class' => 'ct-button-ghost'
							],
							blocksy_html_tag(
								'svg',
								[
									'width' => '12',
									'height' => '12',
									'viewBox' => '0 0 15 15',
									'fill' => 'currentColor'
								],
								'<path d="M8.5,7.5l4.5,4.5l-1,1L7.5,8.5L3,13l-1-1l4.5-4.5L2,3l1-1l4.5,4.5L12,2l1,1L8.5,7.5z"></path>'
							) .
							__('Reset Filter', 'blocksy-companion')
						)
					);
				}

				return blocksy_html_tag(
					'div',
					[
						'class' => 'ct-filter-widget-wrapper'
					],
					blocksy_html_tag(
						'div',
						[
							'class' => 'ct-price-filter',
						],
						blocksy_html_tag(
							'div',
							[
								'class' => 'ct-price-filter-slider'
							],
							blocksy_html_tag(
								'div',
								[
									'class' => 'ct-price-filter-range-track',
									'style' => '--start: ' . $leftStylePos . '%; --end: ' . ($rightStylePos) . '%;'
								],
								''
							) .
							blocksy_html_tag(
								'input',
								[
									'type' => 'range',
									'value' => isset($_GET['min_price']) ? $_GET['min_price'] : $prices['min'],
									'min' => $prices['min'],
									'max' => $prices['max'],
									'step' => 1,
									'name' => 'min_price',
								],
								''
							) .
							blocksy_html_tag(
								'span',
								[
									'class' => 'ct-price-filter-range-handle-min',
									'style' => 'inset-inline-start: ' . $leftStylePos . '%',
								],
								(
									$attributes['showTooltips'] ? blocksy_html_tag(
										'span',
										[
											'class' => 'ct-tooltip'
										],
										blocksy_safe_sprintf($price_format, $currency, $min_price)
									) : ''
								)
							) .
							blocksy_html_tag(
								'input',
								[
									'type' => 'range',
									'value' => isset($_GET['max_price']) ? $_GET['max_price'] : $prices['max'],
									'min' => $prices['min'],
									'max' => $prices['max'],
									'step' => 1,
									'name' => 'max_price',
								],
								''
							) .
							blocksy_html_tag(
								'span',
								[
									'class' => 'ct-price-filter-range-handle-max',
									'style' => 'inset-inline-start: ' . $rightStylePos . '%',
								],
								(
									$attributes['showTooltips'] ? blocksy_html_tag(
										'span',
										[
											'class' => 'ct-tooltip'
										],
										blocksy_safe_sprintf($price_format, $currency, $max_price)
									) : ''
								)
							)
						).
						(
							$attributes['showPrices'] ? blocksy_html_tag(
								'div',
								[
									'class' => 'ct-price-filter-inputs',
								],
								blocksy_html_tag(
									'span',
									[],
									__('Price', 'blocksy-companion') . ':&nbsp;'
								) .
								blocksy_html_tag(
									'span',
									['class' => 'ct-price-filter-min'],
									blocksy_safe_sprintf($price_format, $currency, $min_price)
								) .
								blocksy_html_tag(
									'span',
									[],
									'&nbsp;-&nbsp;'
								) .
								blocksy_html_tag(
									'span',
									['class' => 'ct-price-filter-max'],
									blocksy_safe_sprintf($price_format, $currency, $max_price)
								)
							) : ''
						) .
						$reset_button
					)
				);
			},
		]);
	}
}
