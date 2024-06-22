<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class Brands {
	public function __construct() {
		new \Blocksy\Extensions\WoocommerceExtra\BrandsImportExport();

		add_action(
			'wp_enqueue_scripts',
			function () {
				if (!function_exists('get_plugin_data')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				if (is_admin()) {
					return;
				}

				wp_enqueue_style(
					'blocksy-ext-woocommerce-extra-product-brands-styles',
					BLOCKSY_URL .
						'framework/premium/extensions/woocommerce-extra/static/bundle/product-brands.min.css',
					['blocksy-ext-woocommerce-extra-styles'],
					$data['Version']
				);
			},
			50
		);

		add_action('current_screen', function () {
			if (function_exists('add_settings_field')) {
				add_settings_field(
					'blocksy_woocommerce_extra_product_brands_slug',
					__('Product brands base', 'blocksy-companion'),
					function () {
						$storage = new Storage();
						$settings = $storage->get_settings();

						echo blocksy_html_tag(
							'input',
							[
								'name' => 'blocksy_woocommerce_extra_product_brands_slug',
								'type' => 'text',
								'class' => 'regular-text code',
								'value' => $settings['product-brands-slug'],
								'placeholder' => __('brand', 'blocksy-companion')
							]
						);
					},
					'permalink',
					'optional'
				);
			}

			if (
				is_admin()
				&&
				isset($_POST['blocksy_woocommerce_extra_product_brands_slug'])
				&&
				wp_verify_nonce(
					wp_unslash($_POST['wc-permalinks-nonce']),
					'wc-permalinks'
				)
			) {
				$storage = new Storage();
				$settings = $storage->get_settings();

				$settings['product-brands-slug'] = wc_sanitize_permalink(
					$_POST['blocksy_woocommerce_extra_product_brands_slug']
				);

				update_option(
					'blocksy_ext_woocommerce_extra_settings',
					$settings
				);
			}
		}, 100);

		add_action('init', [$this, 'register_brand_meta']);

		add_filter(
			'blocksy_woo_single_options_layers:defaults',
			[$this, 'add_layer_to_default_layout']
		);
		add_filter(
			'blocksy_woo_compare_layers:defaults',
			[$this, 'add_layer_to_default_layout']
		);
		add_filter(
			'blocksy_woo_card_options_layers:defaults',
			[$this, 'add_layer_to_default_layout']
		);
		add_filter(
			'blocksy_woo_single_right_options_layers:defaults',
			[$this, 'add_layer_to_default_layout']
		);

		add_filter(
			'blocksy_woo_single_options_layers:extra',
			[$this, 'add_single_layer_options']
		);
		add_filter(
			'blocksy_woo_compare_layers:extra',
			[$this, 'add_compare_layer_options']
		);
		add_filter(
			'blocksy_woo_card_options_layers:extra',
			[$this, 'add_archive_layer_options']
		);
		add_filter(
			'blocksy_woo_single_right_options_layers:extra',
			[$this, 'add_single_layer_options']
		);

		add_action(
			'blocksy:woocommerce:product:custom:layer',
			[$this, 'product_single_render']
		);

		add_action(
			'blocksy:woocommerce:product-card:custom:layer',
			[$this, 'product_card_render']
		);

		add_action(
			'blocksy:woocommerce:brands:layer',
			function($attributes) {
				$brands = get_the_terms(get_the_ID(), 'product_brands');

				if (!$brands || !is_array($brands)) {
					return;
				}

				if (!count($brands)) {
					return;
				}

				echo blocksy_html_tag(
					'div',
					[
						'class' => 'ct-product-brands',
						'style' => '--product-brand-logo-size:' . $attributes['brands_size'] . 'px;--product-brands-gap:' . $attributes['brands_gap'] . 'px;'
					],
					$this->render_brands_grid($brands)
				);
			}
		);

		add_action(
			'blocksy:woocommerce:compare:custom:layer',
			[$this, 'product_card_render']
		);

		add_filter(
			'blocksy:options:woo:tabs:general:brands',
			function ($opts) {
				$opts[] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			},
			50
		);

		add_action(
			'wp',
			function() {
				if (blocksy_get_theme_mod('has_woo_brands_tab', 'no') === 'yes') {
					add_filter(
						'woocommerce_product_tabs',
						[$this, 'brands_custom_product_tab']
					);
				}
			}
		);

		add_action(
			'woocommerce_product_duplicate',
			function ($duplicate, $product) {
				$terms = get_the_terms($product->get_id(), 'product_brands');

				if (! is_wp_error($terms)) {
					wp_set_object_terms($duplicate->get_id(), wp_list_pluck($terms, 'term_id'), 'product_brands');
				}
			},
			999,
			2
		);

		add_action('woocommerce_coupon_options_usage_restriction', [$this, 'restrict_coupon_by_brand_form'], 10, 2);
		add_action('woocommerce_coupon_options_save', [$this, 'restrict_coupon_by_brand_save'], 10, 2);
		add_filter('woocommerce_coupon_is_valid_for_product', [$this, 'coupon_is_valid_for_product'], 10, 4);
		add_filter('woocommerce_coupon_is_valid_for_cart', [$this, 'coupon_is_valid_for_cart'], 10, 2);
	}

	public function is_invalid($wc_coupon, $to_compare) {
		$conditions = $this->get_exclude_include_conditions($wc_coupon);

		$include = $conditions['include_product_brands'];
		$exclude = $conditions['exclude_product_brands'];

		if (empty($include)) {
			return false;
		}

		$intersect_include = array_intersect($include, $to_compare);
		$intersect_exclude = array_intersect($exclude, $to_compare);

		if (count($intersect_include) === 0) {
			return true;
		}

		if (count($intersect_exclude) > 0) {
			return true;
		}

		return false;
	}

	public function get_exclude_include_conditions($wc_coupon) {
		$include_product_brands = get_post_meta($wc_coupon->get_id(), 'include_product_brands', true);
		$exclude_product_brands = get_post_meta($wc_coupon->get_id(), 'exclude_product_brands', true);

		if (! $include_product_brands) {
			$include_product_brands = [];
		}

		if (! $exclude_product_brands) {
			$exclude_product_brands = [];
		}

		return [
			'include_product_brands' => array_map('intval', $include_product_brands),
			'exclude_product_brands' => array_map('intval', $exclude_product_brands)
		];
	}

	public function coupon_is_valid_for_cart($valid, $wc_coupon) {
		$cart = WC()->cart->get_cart();

		$cart_brands = [];

		foreach ($cart as $cart_item) {
			$product = wc_get_product($cart_item['product_id']);

			if ($product->get_type() === 'variation') {
				$product = wc_get_product($product->get_parent_id());
			}

			$brands = get_the_terms($product->get_id(), 'product_brands');

			if (! $brands) {
				$brands = [];
			}

			$brands = array_map(function($brand) {
				return $brand->term_id;
			}, $brands);

			$cart_brands = array_merge($cart_brands, $brands);
		}

		$is_invalid = $this->is_invalid($wc_coupon, $cart_brands);

		if ($is_invalid) {
			return false;
		}

		return $valid;
	}

	public function coupon_is_valid_for_product($valid, $product, $wc_coupon, $values) {

		if ($product->get_type() === 'variation') {
			$product = wc_get_product($product->get_parent_id());
		}

		$current_product_brands = get_the_terms($product->get_id(), 'product_brands');

		if (! $current_product_brands) {
			$current_product_brands = [];
		}

		$current_product_brands = array_map(function($brand) {
			return $brand->term_id;
		}, $current_product_brands);

		$is_invalid = $this->is_invalid($wc_coupon, $current_product_brands);

		if ($is_invalid) {
			return false;
		}

		return $valid;
	}

	public function restrict_coupon_by_brand_save($post_id, $coupon) {
		$include_product_brands = isset($_POST['include_product_brands']) ? array_map('intval', $_POST['include_product_brands']) : [];
		$exclude_product_brands = isset($_POST['exclude_product_brands']) ? array_map('intval', $_POST['exclude_product_brands']) : [];

		update_post_meta($post_id, 'include_product_brands', $include_product_brands);
		update_post_meta($post_id, 'exclude_product_brands', $exclude_product_brands);
	}

	public function restrict_coupon_by_brand_form($coupon_id, $coupon) {

		$brands = get_terms('product_brands', 'orderby=name&hide_empty=0');

		$include_html = blocksy_html_tag(
			'p',
			[
				'class' => 'form-field',
			],
			blocksy_html_tag(
				'label',
				[],
				__('Include brands', 'woocommerce')
			) .
			blocksy_html_tag(
				'select',
				[
					'id' => 'include_product_brands',
					'name' => 'include_product_brands[]',
					'style' => 'width: 50%;',
					'class' => 'wc-enhanced-select',
					'multiple' => 'multiple',
					'data-placeholder' => __('No brands', 'woocommerce')
				],
				(function () use ($coupon, $brands) {
					$brand_ids = get_post_meta($coupon->get_id(), 'include_product_brands', true);

					if (! $brand_ids) {
						$brand_ids = [];
					}

					$output = '';

					if ($brands) {
						foreach ($brands as $brand) {
							$output .= blocksy_html_tag(
								'option',
								array_merge(
									[
										'value' => esc_attr($brand->term_id),
									],
									in_array($brand->term_id, $brand_ids) ? ['selected' => 'selected'] : []
								),
								esc_html($brand->name)
							);
						}
					}

					return $output;
				})()
			) .
			wc_help_tip(
				__(
					'Product brands that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.',
					'woocommerce'
				)
			)
		);

		$exlude_html = blocksy_html_tag(
			'p',
			[
				'class' => 'form-field',
			],
			blocksy_html_tag(
				'label',
				[],
				__('Exclude brands', 'woocommerce')
			) .
			blocksy_html_tag(
				'select',
				[
					'id' => 'exclude_product_brands',
					'name' => 'exclude_product_brands[]',
					'style' => 'width: 50%;',
					'class' => 'wc-enhanced-select',
					'multiple' => 'multiple',
					'data-placeholder' => __('No brands', 'woocommerce')
				],
				(function () use ($coupon, $brands) {
					$brand_ids = get_post_meta($coupon->get_id(), 'exclude_product_brands', true);

					if (! $brand_ids) {
						$brand_ids = [];
					}

					$output = '';

					if ($brands) {
						foreach ($brands as $brand) {
							$output .= blocksy_html_tag(
								'option',
								array_merge(
									[
										'value' => esc_attr($brand->term_id),
									],
									in_array($brand->term_id, $brand_ids) ? ['selected' => 'selected'] : []
								),
								esc_html($brand->name)
							);
						}
					}

					return $output;
				})()
			) .
			wc_help_tip(
				__(
					'Product brands that the coupon will be applied to, or that must be in the cart in order for the "Fixed cart discount" to be applied.',
					'woocommerce'
				)
			)
		);

		echo $include_html . $exlude_html;
	}

	public function brands_custom_product_tab( $tabs ) {
		global $product;

		$brands = get_the_terms($product->get_id(), 'product_brands');

		if (!$brands || !is_array($brands)) {
			return $tabs;
		}

		if (!count($brands)) {
			return $tabs;
		}

		$tabs['specific_product_tab'] = array(
			'title' => blocksy_get_theme_mod('use_brand_name_for_tab_title', 'no') === 'no' ? __( 'About Brands', 'blocksy-companion' ) : blc_safe_sprintf(
					__('About %s', 'blocksy-companion'),
					$brands[0]->name
				),
			'priority' => 50,
			'callback' => [$this, 'brands_custom_product_tab_render']
		);

		return $tabs;
	}

	//Add content to a custom product tab
	public function brands_custom_product_tab_render() {
		$brands = get_the_terms(get_the_ID(), 'product_brands');

		if (!$brands || !is_array($brands)) {
			return;
		}

		if (!count($brands)) {
			return;
		}

		$output = '';

		$tabs_type = blocksy_get_theme_mod('woo_tabs_type', 'type-1');

		if ( $tabs_type === 'type-4' ) {
			$output .= blocksy_html_tag(
				'h2',
				[],
				blocksy_get_theme_mod('use_brand_name_for_tab_title', 'no') === 'no'
					? __('About Brands', 'blocksy-companion')
					: blc_safe_sprintf(
						__('About %s', 'blocksy-companion'),
						$brands[0]->name
					)
			);
		}

		foreach ($brands as $key => $brand) {
			$output .= blocksy_html_tag(
				'div',
				[
					'class' => 'ct-product-brands-tab'
				],
				do_shortcode(wpautop($brand->description))
			);
		}

		echo $output;
	}

	public function add_compare_layer_options($opt) {
		$opt = array_merge(
			$opt,
			[
				'product_brands' => [
					'label' => __('Brands', 'blocksy-companion'),
					'options' => [
						'compare_row_sticky' => [
							'type'  => 'ct-switch',
							'label' => __( 'Sticky Row', 'blocksy-companion' ),
							'value' => 'no',
						],
					]
				]
			]
		);

		return $opt;
	}

	public function add_single_layer_options($opt) {
		$opt = array_merge(
			$opt,
			[
				'product_brands' => [
					'label' => __('Brands', 'blocksy-companion'),
					'options' => [

						'brand_layer_title' => [
							'label' => __('Title', 'blocksy'),
							'type' => 'text',
							'design' => 'block',
							'value' => '',
							'disableRevertButton' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							],
						],

						'brand_logo_size' => [
							'label' => __('Logo Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 100,
							'min' => 30,
							'max' => 200,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],

						'brand_logo_gap' => [
							'label' => __('Logos Gap', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 10,
							'min' => 0,
							'max' => 100,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],

						'spacing' => [
							'label' => __('Bottom Spacing', 'blocksy-companion'),
							'type' => 'ct-slider',
							'min' => 0,
							'max' => 100,
							'value' => 10,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],
					]
				],
			]
		);

		return $opt;
	}

	public function add_archive_layer_options($opt) {
		$opt = array_merge(
			$opt,
			[
				'product_brands' => [
					'label' => __('Brands', 'blocksy-companion'),
					'options' => [

						'brand_logo_size' => [
							'label' => __('Logo Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 100,
							'min' => 30,
							'max' => 200,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],

						'brand_logo_gap' => [
							'label' => __('Logos Gap', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 10,
							'min' => 0,
							'max' => 100,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],

						'spacing' => [
							'label' => __('Bottom Spacing', 'blocksy-companion'),
							'type' => 'ct-slider',
							'min' => 0,
							'max' => 100,
							'value' => 10,
							'responsive' => true,
							'sync' => [
								'id' => 'woo_card_layout_skip'
							]
						],
					]
				],
			]
		);

		return $opt;
	}

	public function add_layer_to_default_layout($opt) {
		$opt = array_merge(
			$opt,
			[
				[
					'id' => 'product_brands',
					'enabled' => false,
				]
			]
		);

		return $opt;
	}

	public function register_brand_meta() {
		$storage = new Storage();
		$settings = $storage->get_settings();

		register_taxonomy('product_brands', ['product'], [
			'label'                 => '',
			'labels'                => [
				'name'              => __('Brands', 'blocksy-companion'),
				'singular_name'     => __('Brand', 'blocksy-companion'),
				'search_items'      => __('Search Brands', 'blocksy-companion'),
				'all_items'         => __('All Brands', 'blocksy-companion'),
				'parent_item'       => __('Parent Brand', 'blocksy-companion'),
				'parent_item_colon' => __('Parent Brand:', 'blocksy-companion'),
				'view_item '        => __('View Brand', 'blocksy-companion'),
				'edit_item'         => __('Edit Brand', 'blocksy-companion'),
				'update_item'       => __('Update Brand', 'blocksy-companion'),
				'add_new_item'      => __('Add New Brand', 'blocksy-companion'),
				'new_item_name'     => __('New Brand Name', 'blocksy-companion'),
				'menu_name'         => __('Brands', 'blocksy-companion'),
			],
			'hierarchical'          => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'show_in_rest' => true,
			'rewrite' => [
				'slug' => $settings['product-brands-slug']
			],
		]);

		add_action(
			'product_brands_edit_form',
			[$this, 'term_options']
		);

		add_action(
			'product_brands_add_form',
			[$this, 'term_options']
		);

		add_action('edited_term', [$this, 'save_term_meta'], 10, 3);
		add_action('create_term', [$this, 'save_term_meta'], 10, 3);
	}

	public function save_term_meta($term_id, $tt_id, $taxonomy) {
		if (
			!(
				isset($_POST['action'])
				&&
				('editedtag' === $_POST['action'] || 'add-tag' === $_POST['action'])
				&&
				isset($_POST['taxonomy'])
				&&
				($taxonomy = get_taxonomy(sanitize_text_field(wp_unslash($_POST['taxonomy']))))
				&&
				current_user_can($taxonomy->cap->edit_terms)
			)
		) {
			return;
		}

		$values = [];

		if (isset($_POST['blocksy_taxonomy_meta_options'][blocksy_post_name()])) {
			$values = json_decode(
				sanitize_text_field(
					wp_unslash(
						$_POST['blocksy_taxonomy_meta_options'][
							blocksy_post_name()
						]
					)
				),
				true
			);
		}

		update_term_meta(
			$term_id,
			'blocksy_taxonomy_meta_options',
			$values
		);

		do_action('blocksy:dynamic-css:refresh-caches');
	}

	public function term_options($term) {
		$values = isset($term->term_id) ? get_term_meta(
			$term->term_id,
			'blocksy_taxonomy_meta_options'
		) : [[]];

		if (empty($values)) {
			$values = [[]];
		}

		if (! $values[0]) {
			$values[0] = [];
		}

		$options = [
			'image' => [
				'label' => __('Featured Image', 'blocksy-companion'),
				'type' => 'ct-image-uploader',
				'value' => '',
				'attr' => [
					'data-type' => 'large'
				],
				'emptyLabel' => __('Select Image', 'blocksy-companion'),
			],

			'icon_image' => [
				'label' => __('Featured Icon/Logo', 'blocksy-companion'),
				'type' => 'ct-image-uploader',
				'value' => '',
				'attr' => [
					'data-type' => 'large'
				],
				'emptyLabel' => __('Select Image', 'blocksy-companion'),
			],
		];

		echo blocksy_html_tag(
			'div',
			[],
			blocksy_html_tag(
				'input',
				[
					'type' => 'hidden',
					'value' => htmlspecialchars(wp_json_encode($values[0])),
					'data-options' => htmlspecialchars(
						wp_json_encode($options)
					),
					'name' => 'blocksy_taxonomy_meta_options[' . blocksy_post_name() . ']',
				]
			)
		);
	}

	public function render_brands_grid($brands) {
		$output = '';

		foreach ($brands as $key => $brand) {

			$label = blocksy_html_tag(
				'a',
				[
					'href' => esc_url(get_term_link($brand)),
				],
				$brand->name
			);

			$term_atts = get_term_meta(
				$brand->term_id,
				'blocksy_taxonomy_meta_options'
			);

			if (empty($term_atts)) {
				$term_atts = [[]];
			}

			$term_atts = $term_atts[0];

			$maybe_image = blocksy_akg('icon_image', $term_atts, '');

			if (
				$maybe_image
				&&
				is_array($maybe_image)
				&&
				isset($maybe_image['attachment_id'])
			) {
				$attachment_id = $maybe_image['attachment_id'];

				$label = blocksy_media([
					'attachment_id' => $maybe_image['attachment_id'],
					'size' => 'full',
					'ratio' => 'initial',
					'class' => 'ct-product-brand',
					'tag_name' => 'a',
					'html_atts' => [
						'href' => get_term_link($brand),
						'aria-label' => $brand->name
					]
				]);
			}

			$output .= $label;
		}

		return $output;
	}

	public function product_single_render($layer) {
		if ($layer['id'] !== 'product_brands') {
			return;
		}

		$brands = get_the_terms(get_the_ID(), 'product_brands');

		if (!$brands || !is_array($brands)) {
			return;
		}

		if (!count($brands)) {
			return;
		}

		$section_title = blocksy_akg('brand_layer_title', $layer, '');

		echo blocksy_html_tag(
			'div',
			[
				'class' => 'ct-product-brands-single',
			],
			(
				! empty($section_title) || is_customize_preview() ?
				blocksy_html_tag(
					'span',
					[
						'class' => 'ct-module-title',
					],
					$section_title
				) : ''
			) .
			blocksy_html_tag(
				'div',
				[
					'class' => 'ct-product-brands',
				],
				$this->render_brands_grid($brands)
			)
		);
	}

	public function product_card_render($layer) {
		if ($layer['id'] !== 'product_brands') {
			return;
		}

		$brands = get_the_terms(get_the_ID(), 'product_brands');

		if (!$brands || !is_array($brands)) {
			return;
		}

		if (!count($brands)) {
			return;
		}

		echo blocksy_html_tag(
			'div',
			[
				'class' => 'ct-product-brands',
			],
			$this->render_brands_grid($brands)
		);
	}
}
