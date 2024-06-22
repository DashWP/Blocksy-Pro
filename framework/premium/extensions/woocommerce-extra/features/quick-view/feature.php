<?php

namespace Blocksy\Extensions\WoocommerceExtra;

require_once dirname(__FILE__) . '/helpers.php';

class QuickView {
	public function __construct() {
		add_filter('blocksy_woo_card_options:additional_actions', function (
			$actions
		) {
			$actions[] = [
				'id' => 'has_archive_quick_view',
				'label' => __('Quick View Button', 'blocksy-companion'),
			];

			return $actions;
		}, 3);

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			if (!class_exists('WC_AJAX')) {
				return $chunks;
			}

			$chunks[] = [
				'id' => 'blocksy_ext_woo_extra_quick_view',
				'selector' => implode(
					', ',
					[
						'.ct-open-quick-view',
						'[data-quick-view="image"] .ct-media-container',
						'[data-quick-view="card"] > .type-product'
					]
				),
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/woocommerce-extra/static/bundle/quick-view.js'
				),
				'deps' => [
					'underscore',
					'wc-add-to-cart-variation',
					'wp-util'
				],
				'global_data' => [
					[
						'var' => 'wc_add_to_cart_variation_params',
						'data' => [
							'wc_ajax_url'                      => \WC_AJAX::get_endpoint('%%endpoint%%'),
							'i18n_no_matching_variations_text' => esc_attr__('Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce'),
							'i18n_make_a_selection_text'       => esc_attr__('Please select some product options before adding this product to your cart.', 'woocommerce'),
							'i18n_unavailable_text'            => esc_attr__('Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce'),
						]
					]
				],
				'trigger' => 'click',
				'ignore_click' => implode(
					', ',
					[
						"[data-quick-view='card'] > * [data-product_id]",
						"[data-quick-view='card'] > * .added_to_cart",
						"[data-quick-view='card'] > * .ct-woo-card-extra > *",
					]
				),
				'has_loader' => [
					'type' => blocksy_get_theme_mod('woocommerce_quick_view_trigger', 'button') === 'button' ? 'button' : 'modal',
					'class' => 'quick-view-modal'
				]
			];

			return $chunks;
		});

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			$data['dynamic_styles_selectors'][] = [
				'selector' => '.product',
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/woocommerce-extra/static/bundle/quick-view-lazy.min.css'
				)
			];

			return $data;
		});

		add_filter('thwvsf_is_quick_view_plugin_active', function ($result) {
			return true;
		});

		add_action('wp_ajax_blocsky_get_woo_quick_view', [
			$this,
			'get_quick_view',
		]);

		add_action('wp_ajax_nopriv_blocsky_get_woo_quick_view', [
			$this,
			'get_quick_view',
		]);

		add_filter(
			'blocksy_customizer_options:woocommerce:general:end',
			function ($opts) {
				$opts['has_quick_view_panel'] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			}
		);

		add_action(
			'blocksy:woocommerce:quick-view:add-to-cart:after',
			function () {
				$layout = blocksy_get_theme_mod(
					'woo_single_layout',
					blocksy_get_woo_single_layout_defaults()
				);

				$product_view_type = blocksy_get_theme_mod(
					'product_view_type',
					'default-gallery'
				);

				if (
					$product_view_type === 'top-gallery'
					||
					$product_view_type === 'columns-top-gallery'
				) {
					$woo_single_split_layout = blocksy_get_theme_mod(
						'woo_single_split_layout',
						[
							'left' => blocksy_get_woo_single_layout_defaults('left'),
							'right' => blocksy_get_woo_single_layout_defaults('right')
						]
					);

					$layout = array_merge(
						$woo_single_split_layout['left'],
						$woo_single_split_layout['right']
					);
				}

				$actions_layer = array_values(array_filter($layout, function($k) {
					return $k['id'] === 'product_actions';
				}));

				if ($actions_layer) {
					blocksy_manager()
						->woocommerce
						->single
						->additional_actions
						->render(array_shift($actions_layer));
				}
			}
		);
	}

	public function get_quick_view() {
		if (function_exists('YITH_Name_Your_Price_Frontend')) {
			YITH_Name_Your_Price_Frontend();
		}

		global $product;
		global $post;

		global $blocksy_is_quick_view;

		do_action('blocksy:content-blocks:display-hooks');

		$blocksy_is_quick_view = true;

		$product = wc_get_product(sanitize_text_field($_GET['product_id']));

		$variation = null;

		if (get_class($product) === 'WC_Product_Variation') {
			global $blocksy_current_variation;

			$variation = $product;
			$product = wc_get_product($variation->get_parent_id());

			$blocksy_current_variation = $variation;

			$permalink = $variation->get_permalink();

			parse_str(parse_url($permalink, PHP_URL_QUERY), $res);

			foreach ($res as $key => $val) {
				$_REQUEST[$key] = $val;
				$_GET[$key] = $val;
			}
		}

		$GLOBALS['product'] = $product;

		$id = $product->get_id();

		$post = get_post($id);

		if (!$product) {
			wp_send_json_error();
		}

		$is_in_stock = true;

		if (!$product->managing_stock() && !$product->is_in_stock()) {
			$is_in_stock = false;
		}

		remove_filter(
			'woocommerce_post_class',
			'blocksy_woo_single_post_class',
			999,
			2
		);

		remove_action(
			'woocommerce_product_thumbnails',
			'woocommerce_show_product_thumbnails',
			20
		);

		$is_customize_preview = (
			isset($_GET['is_customizer'])
			&&
			$_GET['is_customizer']
		);

		$content = blocksy_render_view(
			dirname(__FILE__) . '/view.php',
			[
				'id' => $id,
				'product' => $product,
				'variation' => $variation,
				'is_customize_preview' => $is_customize_preview
			]
		);

		ob_start();
		if (function_exists('wc_get_template')) {
			wc_get_template('single-product/add-to-cart/variation.php');
		}
		$body_html = ob_get_clean();

		wp_send_json_success([
			'quickview' => $content,
			'body_html' => $body_html,
		]);
	}
}
