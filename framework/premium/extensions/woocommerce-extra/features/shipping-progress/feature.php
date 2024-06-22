<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class ShippingProgress {
	public function __construct() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				if (!function_exists('get_plugin_data')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				$render = new \Blocksy_Header_Builder_Render();
				$has_mini_cart = $render->contains_item('cart');

				if (
					is_admin()
					||
					(
						is_cart()
						&&
						blocksy_get_theme_mod('woo_shipping_progress_in_cart', 'no') === 'no'
					)
					||
					(
						is_checkout()
						&&
						blocksy_get_theme_mod('woo_shipping_progress_in_checkout', 'no') === 'no'
					)
					||
					(
						(
							! $has_mini_cart
							||
							(
								$has_mini_cart
								&&
								blocksy_get_theme_mod('woo_shipping_progress_in_mini_cart', 'no') === 'no'
							)
						)
						&&
						is_singular('product')
						&&
						function_exists('blocksy_has_product_specific_layer')
						&&
						! blocksy_has_product_specific_layer('free_shipping')
					)
					||
					(
						! is_cart()
						&&
						! is_checkout()
						&&
						! is_singular('product')
						&&
						$has_mini_cart
						&&
						blocksy_get_theme_mod('woo_shipping_progress_in_mini_cart', 'no') === 'no'
					)
					||
					(
						! is_cart()
						&&
						! is_checkout()
						&&
						! is_singular('product')
						&&
						! $has_mini_cart
					)
				) {
					return;
				}

				wp_enqueue_style(
					'blocksy-ext-woocommerce-extra-shipping-progress-styles',
					BLOCKSY_URL .
						'framework/premium/extensions/woocommerce-extra/static/bundle/shipping-progress.min.css',
					['blocksy-ext-woocommerce-extra-styles'],
					$data['Version']
				);
			},
			50
		);

		add_action('wp', function () {
			if (blocksy_get_theme_mod('woo_shipping_progress_in_cart', 'no') === 'yes') {
				add_action('woocommerce_proceed_to_checkout', [
					$this,
					'cart_page_render',
				]);
			}

			if (blocksy_get_theme_mod('woo_shipping_progress_in_checkout', 'no') === 'yes') {
				add_action('woocommerce_checkout_order_review', [
					$this,
					'checkout_page_render',
				]);
			}

			if (blocksy_get_theme_mod('woo_shipping_progress_in_mini_cart', 'no') === 'yes') {
				add_action('woocommerce_widget_shopping_cart_before_buttons', [
					$this,
					'minicart_render',
				]);
			}
		});

		add_filter('blocksy:woocommerce:cart-fragments', [
			$this,
			'blocksy_header_cart_item_fragment',
		]);

		add_filter(
			'blocksy_customizer_options:woocommerce:general:end',
			function ($opts) {
				$opts['has_free_shipping_panel'] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			},
			55
		);

		add_filter('blocksy_woo_single_options_layers:defaults', [
			$this,
			'add_layer_to_default_layout',
		]);

		add_filter('blocksy_woo_single_options_layers:extra', [
			$this,
			'add_layer_options',
		]);

		add_action('blocksy:woocommerce:product:custom:layer', [
			$this,
			'render_shipping_layer',
		]);
	}

	public function render_wrapper($additional_classes = '', $content = '') {
		return blocksy_html_tag(
			'div',
			[
				'class' => 'ct-shipping-progress' . $additional_classes,
			],
			$content
		);
	}

	public function cart_page_render() {
		echo $this->render_wrapper(
			'-cart-page',
			$this->render_shipping_progress_bar()
		);
	}

	public function checkout_page_render() {
		echo $this->render_wrapper(
			'-checkout-page',
			$this->render_shipping_progress_bar()
		);
	}

	public function minicart_render() {
		echo $this->render_wrapper(
			'-mini-cart',
			$this->render_shipping_progress_bar()
		);
	}

	public function add_layer_options($opt) {
		$opt = array_merge($opt, [
			'free_shipping' => [
				'label' => __('Free Shipping Bar', 'blocksy-companion'),
				'options' => [
					'show_if_cart_is_empty' => [
						'label' => __( 'Show if cart is empty', 'blocksy-companion' ),
						'type' => 'ct-switch',
						'value' => 'yes',
						'divider' => 'top'
					],

					'spacing' => [
						'label' => __('Bottom Spacing', 'blocksy-companion'),
						'type' => 'ct-slider',
						'min' => 0,
						'max' => 100,
						'value' => 10,
						'responsive' => true,
						'sync' => [
							'id' => 'woo_card_layout_skip',
						],
					],
				],
			],
		]);

		return $opt;
	}

	public function add_layer_to_default_layout($opt) {
		$opt = array_merge($opt, [
			[
				'id' => 'free_shipping',
				'enabled' => false,
			],
		]);

		return $opt;
	}

	public function render_shipping_layer($layer) {
		if ($layer['id'] !== 'free_shipping') {
			return;
		}

		if (
			blocksy_akg('show_if_cart_is_empty', $layer, 'yes') === 'no'
			&&
			WC()->cart->is_empty()
		) {
			return;
		}

		echo $this->render_wrapper(
			'-single',
			$this->render_shipping_progress_bar()
		);
	}

	private function has_paid_shipping_item() {
		$exclude_categories = apply_filters(
			'blocksy:pro:woocommerce-extra:shipping-progress:exclude-categories',
			[]
		);

		$hide_shipping_bar = false;

		if (empty($exclude_categories)) {
			return $hide_shipping_bar;
		}

		foreach (WC()->cart->get_cart() as $cart_item) {
			if (
				has_term($exclude_categories , 'product_cat', $cart_item['product_id'])
			) {
				$hide_shipping_bar = true;
				break;
			}
		}

		return $hide_shipping_bar;
	}

	public function render_shipping_progress_bar($return_html = '') {
		$calculation = blocksy_get_theme_mod('woo_count_method', 'custom');
		$wrapper_classes = '';
		$percent = 100;
		$limit = 0;
		$free_shipping = false;

		if (
			! is_object(WC())
			||
			! property_exists(WC(), 'cart')
			||
			! is_object(WC()->cart)
			||
			! method_exists(WC()->cart, 'get_displayed_subtotal')
		) {
			$total = 0;
			$calculation = 'custom';
		} else {
			$total = WC()->cart->get_displayed_subtotal();

			if (method_exists(WC()->cart, 'get_fee_total')) {
				$total += WC()->cart->get_fee_total();
			}
		}

		$isCustomByItems = $calculation === 'custom' && blocksy_get_theme_mod('woo_custom_count_criteria', 'price') === 'items';

		if ('woo' === $calculation) {
			$packages = WC()->cart->get_shipping_packages();
			$package = reset($packages);
			$zone = wc_get_shipping_zone($package);

			foreach ($zone->get_shipping_methods(true) as $method) {
				if ('free_shipping' === $method->id) {
					$limit = $method->get_option('min_amount');
				}
			}
		} elseif ('custom' === $calculation) {
			$limit = blocksy_get_theme_mod('woo_count_progress_amount', 100);
		}

		if (defined('WOOCS_VERSION')) {
			global $WOOCS;

			$limit *= $WOOCS->get_sign_rate([
				'sign' => $WOOCS->current_currency,
			]);
		} elseif (class_exists('woocommerce_wpml')) {
			global $woocommerce_wpml;

			$multi_currency = $woocommerce_wpml->get_multi_currency();

			if (
				!empty($multi_currency->prices)
				&&
				method_exists($multi_currency->prices, 'convert_price_amount')
			) {
				$limit = $multi_currency->prices->convert_price_amount($limit);
			}
		}

		if ($isCustomByItems) {
			$total = WC()->cart->get_cart_contents_count();
			$limit = blocksy_get_theme_mod('woo_count_progress_items', 2);
		}

		if (
			$total
			&&
			WC()->cart->get_coupons()
			&&
			blocksy_get_theme_mod('woo_count_with_discount', 'include') === 'include'
		) {
			foreach (WC()->cart->get_coupons() as $coupon) {
				$total -= WC()->cart->get_coupon_discount_amount(
					$coupon->get_code(),
					WC()->cart->display_cart_ex_tax
				);

				if ($coupon->get_free_shipping()) {
					$free_shipping = true;
					break;
				}
			}
		}

		if (
			$total < $limit
			&&
			! $free_shipping
		) {
			$percent = floor(($total / $limit) * 100);
			$message = str_replace(
				'{price}',
				wc_price($limit - $total),
				blocksy_get_theme_mod(
					'free_not_enought_message',
					__(
						'Add {price} more to get free shipping!',
						'blocksy-companion'
					)
				)
			);

			if ($isCustomByItems) {
				$message = str_replace(
					'{items}',
					$limit - $total,
					blocksy_get_theme_mod(
						'free_not_enought_items_message',
						__(
							'Add {items} more items to get free shipping!',
							'blocksy-companion'
						)
					)
				);
			}
		} else {
			$message = blocksy_get_theme_mod(
				'free_enought_message',
				__(
					'Congratulations! You got free shipping 🎉',
					'blocksy-companion'
				)
			);
		}


		if (! $limit) {
			return;
		}

		if ($this->has_paid_shipping_item()) {
			return;
		}

		$message_html = blocksy_html_tag(
			'div',
			[
				'class' => 'ct-message',
			],
			$message
		);

		$bar_html = blocksy_html_tag(
			'div',
			[
				'class' => 'ct-progress-bar',
			],
			$percent
				? blocksy_html_tag('span', [
					'style' => 'width: ' . $percent . '%',
				])
				: ''
		);

		if ($return_html === 'message') {
			return $message_html;
		}

		if ($return_html === 'bar') {
			return $bar_html;
		}

		return implode('', [$message_html, $bar_html]);
	}

	public function blocksy_header_cart_item_fragment($fragments) {
		$fragments['.ct-message'] = $this->render_shipping_progress_bar(
			'message'
		);
		$fragments['.ct-progress-bar'] = $this->render_shipping_progress_bar(
			'bar'
		);

		return $fragments;
	}
}
