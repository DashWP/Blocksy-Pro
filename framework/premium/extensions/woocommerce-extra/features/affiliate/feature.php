<?php

namespace Blocksy\Extensions\WoocommerceExtra;

// require_once dirname(__FILE__) . '/helpers.php';

class AffiliateProduct {
	private $wish_list_slug = null;

	public function __construct() {
		add_filter(
			'blocksy_customizer_options:woocommerce:general:end',
			function ($opts) {
				$opts['has_product_affiliates_panel'] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			},
			50
		);

		add_filter('blocksy:woocommerce:product-card:title:link', function($args) {
			global $product;

			if (
				blocksy_get_theme_mod('woo_archive_affiliate_title_link', 'no') === 'no'
				||
				!$product->is_type('external')
			) {
				return $args;
			}

			$open_in_new_tab = blocksy_get_theme_mod(
				'woo_archive_affiliate_title_link_new_tab',
				'no'
			) === 'yes' ? '_blank' : '_self';

			return [
				'href' => $product->get_product_url(),
				'target' => $open_in_new_tab
			];
		});

		add_filter('woocommerce_loop_add_to_cart_args', function($args) {
			global $product;

			if (! $product->is_type('external')) {
				return $args;
			}

			$open_in_new_tab = blocksy_get_theme_mod(
				'woo_archive_affiliate_button_link_new_tab',
				'no'
			) === 'yes' ? '_blank' : '_self';

			$args['attributes']['target'] = $open_in_new_tab;

			return $args;
		});

		add_action('woocommerce_external_add_to_cart', function() {
			global $product;

			if (! $product->is_type('external')) {
				return;
			}

			remove_action(
				'woocommerce_external_add_to_cart',
				'woocommerce_external_add_to_cart',
				30
			);

			$open_in_new_tab = blocksy_get_theme_mod(
				'woo_single_affiliate_button_link_new_tab',
				'no'
			) === 'yes' ? '_blank' : '_self';

			echo blocksy_html_tag(
				'div',
				[
					'class' => 'cart'
				],
				blocksy_html_tag(
					'div',
					[
						'class' => 'ct-cart-actions'
					],
					blocksy_html_tag(
						'a',
						[
							'href' => $product->get_product_url(),
							'class' => 'single_add_to_cart_button button alt wp-element-button',
							'target' => $open_in_new_tab
						],
						$product->single_add_to_cart_text()
					)
				)
			);
		});

		add_filter('blocksy:woocommerce:image_additional_attributes', function($attributes) {
			global $product;

			if ( ! $product ) {
				return $attributes;
			}

			if (
				blocksy_get_theme_mod('woo_single_affiliate_image_link', 'no') === 'yes'
				&&
				$product->is_type('external')
			) {
				$open_in_new_tab = blocksy_get_theme_mod(
					'woo_single_affiliate_image_link_new_tab',
					'no'
				) === 'yes' ? '_blank' : '_self';

				$attributes['tag_name'] = 'a';
				$attributes['html_atts'] = array_merge(
					$attributes['html_atts'],
					[
						'target' => $open_in_new_tab,
						'href' => $product->get_product_url()
					]
				);
			}

			return $attributes;
		});
	}
}

