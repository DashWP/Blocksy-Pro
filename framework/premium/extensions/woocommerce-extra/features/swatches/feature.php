<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class Swatches {
	public function __construct() {
		new SwatchesApi();

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			if (!function_exists('get_plugin_data')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_data = get_plugin_data(BLOCKSY__FILE__);

			$data['dynamic_styles']['swatches'] = add_query_arg(
				'ver',
				$plugin_data['Version'],
				blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/woocommerce-extra/static/bundle/variation-swatches.min.css'
				)
			);

			return $data;
		});

		add_action(
			'wp_enqueue_scripts',
			function () {
				if (!function_exists('get_plugin_data')) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_register_style(
					'blocksy-ext-woocommerce-extra-variation-swatches-styles',
					BLOCKSY_URL .
						'framework/premium/extensions/woocommerce-extra/static/bundle/variation-swatches.min.css',
					['blocksy-ext-woocommerce-extra-styles'],
					$data['Version']
				);

				add_filter('do_shortcode_tag', function($output, $tag, $attr) {
					if (
						'products' === $tag
						&&
						function_exists('blocksy_has_product_card_specific_layer')
						&&
						blocksy_has_product_card_specific_layer('product_swatches')
					) {
						wp_enqueue_style(
							'blocksy-ext-woocommerce-extra-variation-swatches-styles'
						);
					}

					return $output;
				}, 10, 3);

				add_filter(
					'render_block',
					function ($block_content, $block) {
						if ($block['blockName'] === 'blocksy/woocommerce-filters') {
							wp_enqueue_style(
								'blocksy-ext-woocommerce-extra-variation-swatches-styles'
							);
						}

						return $block_content;
					},
					10,
					2
				);

				$force_enqueue = apply_filters('blocksy:ext:woocommerce-extra:swatches:css', false);

				if ($force_enqueue) {
					wp_enqueue_style(
						'blocksy-ext-woocommerce-extra-variation-swatches-styles'
					);
				}

				global $post;
				$product = null;

				if (
					$post
					&&
					$post instanceof \WP_Post
				) {
					$product = wc_get_product($post->ID);
				}

				$is_simple_product = blc_get_ext(
					'woocommerce-extra'
				)->utils->is_simple_product($product);

				if (
					is_admin()
					||
					(
						is_singular('product')
						&&
						$is_simple_product['value']
						&&
						! is_customize_preview()
						&&
						(
							blocksy_get_theme_mod('woo_has_related_upsells', 'yes') === 'no'
							||
							(
								blocksy_get_theme_mod('woo_has_related_upsells', 'yes') === 'yes'
								&&
								function_exists('blocksy_has_product_card_specific_layer')
								&&
								! blocksy_has_product_card_specific_layer('product_swatches')
							)
						)
					)
					||
					(
						is_singular('product')
						&&
						function_exists('blocksy_has_product_specific_layer')
						&&
						! blocksy_has_product_specific_layer('product_add_to_cart')
						&&
						! is_customize_preview()
						&&
						blocksy_get_theme_mod('woo_has_related_upsells', 'yes') === 'no'
					)
					||
					(
						function_exists('is_woocommerce')
						&&
						! is_woocommerce()
					)
					||
					(
						(
							is_shop()
							||
							is_product_tag()
							||
							is_product_category()
							||
							is_product_taxonomy()
							||
							is_search()
						)
						&&
						function_exists('blocksy_has_product_card_specific_layer')
						&&
						! blocksy_has_product_card_specific_layer('product_swatches')
					)
				) {
					return;
				}

				wp_enqueue_style(
					'blocksy-ext-woocommerce-extra-variation-swatches-styles'
				);
			},
			50
		);

		new SwatchesLoopVariableProduct();

		if (is_admin()) {
			new SwatchesPersistAttributes();
		}

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			if (! class_exists('WC_AJAX')) {
				return $chunks;
			}

			ob_start();
			wc_get_template('single-product/add-to-cart/variation.php');
			$raw_html = ob_get_clean();

			$chunks[] = [
				'id' => 'blocksy_ext_woo_extra_swatches',
				'selector' => '.product .ct-swatch-container,.product .ct-variation-swatches select:last-child',
				'trigger' => [
					[
						'trigger' => 'click',
						'selector' => '.product .ct-swatch-container',
					],

					[
						'trigger' => 'change',
						'selector' => '.product .ct-variation-swatches select:last-child',
					],

					[
						'trigger' => 'click',
						'selector' => '.product .reset_variations',
					]
				],
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/woocommerce-extra/static/bundle/swatches.js'
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
							'i18n_no_matching_variations_text' => esc_attr__('Sorry, no products matched your selection. Please choose a different combination.', 'blocksy-companion'),
							'i18n_make_a_selection_text'       => esc_attr__('Please select some product options before adding this product to your cart.', 'blocksy-companion'),
							'i18n_unavailable_text'            => esc_attr__('Sorry, this product is unavailable. Please choose a different combination.', 'blocksy-companion'),
							'i18n_out_of_stock' 			   => esc_attr__('Out of Stock', 'blocksy-companion'),
						]
					]
				],
				'raw_html' => [
					'html' => $raw_html,
					'selector' => '#tmpl-variation-template'
				]
			];

			return $chunks;
		});

		add_filter(
			'blocksy_customizer_options:woocommerce:general:end',
			function ($opts) {
				$opts['has_variation_swatches_panel'] = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				return $opts;
			},
			55
		);

		add_filter(
			'blocksy_woo_card_options_layers:defaults',
			function ($defaults) {
				$defaults[] = [
					'id' => 'product_swatches',
					'enabled' => false
				];

				return $defaults;
			}
		);

		add_filter(
			'blocksy_woo_card_options_layers:extra',
			[$this, 'add_layer_options']
		);

		add_action('blocksy:woocommerce:product-card:custom:layer', [
			$this,
			'render_layer',
		]);

		add_filter(
			'woocommerce_dropdown_variation_attribute_options_html',
			function ($html, $args) {
				$has_single_product_swatches = apply_filters(
					'blocksy:pro:woocommerce-extra:swatches:has-single-product-swatches',
					true
				);

				if (! $has_single_product_swatches) {
					return $html;
				}

				global $blocksy_rendering_woo_card;

				if ($blocksy_rendering_woo_card) {
					return $html;
				}

				$conf = new SwatchesConfig();
				$type = $conf->get_attribute_type($args['attribute']);

				$attr = [
					'class' => 'ct-variation-swatches',
					'data-swatches-type' => $type
				];

				if ($type === 'color') {
					$attr['data-swatches-shape'] = blocksy_get_theme_mod('color_swatch_shape', 'round');
				}

				if ($type === 'image') {
					$attr['data-swatches-shape'] = blocksy_get_theme_mod('image_swatch_shape', 'round');
				}

				if ($type === 'button') {
					$attr['data-swatches-shape'] = blocksy_get_theme_mod('button_swatch_shape', 'round');
				}

				if ($type === 'mixed') {
					$attr['data-swatches-shape'] = blocksy_get_theme_mod('mixed_swatch_shape', 'round');
				}

				$custom_swatch_html = '';
				$renderer = new SwatchesFrontend();

				if ($type !== 'select') {
					$custom_swatch_html = $renderer->get_swatch_html($args);
				}

				return blocksy_html_tag('div', $attr, $html . $custom_swatch_html);
			},
			999, 2
		);

		add_action('woocommerce_post_class', function ($classes) {
			if (! is_product()) {
				return $classes;
			}

			global $product;

			$is_simple_product = blc_get_ext(
				'woocommerce-extra'
			)->utils->is_simple_product($product);

			if (
				blocksy_get_theme_mod('has_swatches_url', 'no') === 'yes'
				&&
				! $is_simple_product['value']
			) {
				$classes[] = 'ct-has-swatches-url';
			}

			return $classes;
		});

		add_filter(
			'woocommerce_attribute_label',
			function ($label, $name) {
				global $product;

				if (
					! $product
					||
					! $product instanceof \WC_Product
				) {
					return $label;
				}

				if ($product->get_type() !== 'variable') {
					return $label;
				}

				$default_attributes = $product->get_default_attributes();
				$maybe_value = '';

				$conf = new SwatchesConfig();
				$type = $conf->get_attribute_type($name);

				if ($type === 'select') {
					return $label;
				}

				if (isset($default_attributes[$name])) {
					if (taxonomy_exists($name)) {
						$term = get_term_by(
							'slug',
							$default_attributes[$name],
							$name
						);

						if (! $term) {
							return $label;
						}

						$maybe_value = $term->name;
					} else {
						$maybe_value = $default_attributes[$name];
					}
				} else {
					$attributes = $product->get_attributes();
					$attributes = array_map(function ($attr) {
						return $attr->get_name();
					}, $attributes);

					$maybe_custom_attribute = array_search($name, $attributes);

					if (
						$maybe_custom_attribute
						&&
						isset($default_attributes[$maybe_custom_attribute])
					) {
						$maybe_value = $default_attributes[$maybe_custom_attribute];
					}
				}

				if ($maybe_value) {
					return $label . ': ' . $maybe_value;
				}

				return $label;
			},
			10,
			3
		);

		add_filter(
			'woocommerce_post_class',
			function ($classes, $product) {

				global $blocksy_rendering_woo_card;

				if ($blocksy_rendering_woo_card) {
					return $classes;
				}

				$product_view_type = blocksy_get_theme_mod('product_view_type', 'default-gallery');
				if (
					$product_view_type === 'default-gallery'
					||
					$product_view_type === 'stacked-gallery'
				) {
					$default_product_layout = [];

					if (function_exists('blocksy_get_woo_single_layout_defaults')) {
						$default_product_layout = blocksy_get_woo_single_layout_defaults();
					}

					$woo_single_layout = blocksy_get_theme_mod(
						'woo_single_layout',
						$default_product_layout
					);
				} else {
					$woo_single_split_layout_defults = [
						'left' => [],
						'right' => []
					];

					if (function_exists('blocksy_get_woo_single_layout_defaults')) {
						$woo_single_split_layout_defults = [
							'left' => blocksy_get_woo_single_layout_defaults('left'),
							'right' => blocksy_get_woo_single_layout_defaults('right')
						];
					}
					$woo_single_split_layout = blocksy_get_theme_mod(
						'woo_single_split_layout',
						$woo_single_split_layout_defults
					);

					$woo_single_layout_left = $woo_single_split_layout['left'];
					$woo_single_layout_right = $woo_single_split_layout['right'];

					$woo_single_layout = array_merge(
						$woo_single_layout_left,
						$woo_single_layout_right
					);
				}

				$product_layer = array_search('product_add_to_cart', array_column($woo_single_layout, 'id'));
				$variations_swatches_display_type = blocksy_get_theme_mod('variations_swatches_display_type', 'no');

				if (
					! $product_layer
					||
					! isset($woo_single_layout[$product_layer])
					||
					! isset($woo_single_layout[$product_layer]['enabled'])
					||
					! $woo_single_layout[$product_layer]['enabled']
					||
					$variations_swatches_display_type === 'no'
				) {
					return $classes;
				}

				$classes[] = 'ct-inline-variations';

				return $classes;
			},
			99999, 2
		);

		add_filter('blocksy_woo_single_options:after_layers', function ($opts) {
			return [
				$opts,

				'variations_swatches_display_type' => [
					'label' => __('Display Variations Inline', 'blocksy-companion'),
					'type' => 'ct-switch',
					'value' => 'no',
					'sync' => [
						'id' => 'woo_single_layout_skip'
					],
				],
			];
		});

		add_filter(
			'product_attributes_type_selector',
			function ($columns) {
				return array_merge(
					$columns,
					$this->get_attribute_types()
				);
			},
		);

		add_action(
			'woocommerce_product_option_terms',
			function($attribute_taxonomy, $i, $attribute) {
				if ('select' !== $attribute_taxonomy->attribute_type && in_array($attribute_taxonomy->attribute_type, array_keys($this->get_attribute_types()))) {
                    $name = sprintf('attribute_values[%s][]', esc_attr($i));
                    ?>
                    <select multiple="multiple" data-placeholder="<?php esc_attr_e('Select terms', 'woo-variation-swatches'); ?>" class="multiselect attribute_values wc-enhanced-select" name="<?php echo esc_attr($name) ?>">
                        <?php
                            $args = array(
                                'orderby'    => ! empty($attribute_taxonomy->attribute_orderby) ? $attribute_taxonomy->attribute_orderby : 'name',
                                'hide_empty' => 0,
                        	);

                            $all_terms = get_terms($attribute->get_taxonomy(), apply_filters('woocommerce_product_attribute_terms', $args));
                            if ($all_terms) {
                                foreach ($all_terms as $term) {
                                    $options = $attribute->get_options();
                                    $options = ! empty($options) ? $options : array();
                                    echo '<option value="' . esc_attr($term->term_id) . '"' . wc_selected($term->term_id, $options) . '>' . esc_html(apply_filters('woocommerce_product_attribute_term_name', $term->name, $term)) . '</option>';
                                }
                            }
                        ?>
                    </select>
                    <button class="button plus select_all_attributes"><?php esc_html_e('Select all', 'woo-variation-swatches'); ?></button>
                    <button class="button minus select_no_attributes"><?php esc_html_e('Select none', 'woo-variation-swatches'); ?></button>
                    <button class="button fr plus add_new_attribute"><?php esc_html_e('Add new', 'woo-variation-swatches'); ?></button>

                    <?php
				}
			},
			10,
			3
		);

		add_action('woocommerce_product_data_tabs', [$this, 'blc_add_product_tab']);
		add_action('woocommerce_process_product_meta', [$this, 'blc_save_swatches_for_product']);
		add_action('woocommerce_product_data_panels', function() {
			$options = blocksy_get_variables_from_file(
				dirname(__FILE__) . '/woo-tab-options.php',
				[
					'tooltip_options' => [],
					'mixed_options' => [],
					'color_options' => [],
					'image_options' => [],
					'button_options' => [],
					'inherit_options' => [],
				],
				[
					'option_design' => true
				]
			);

			global $post;
			$current_product_id = $post->ID;

			$meta = get_post_meta($current_product_id, '_ct-woo-attributes-list', true);

			echo blocksy_html_tag(
				'div',
				[
					'id' => 'ct_product_variation_swatches',
					'class' => 'panel woocommerce_options_panel hidden'
				],
				blocksy_html_tag(
					'div',
					[
						'class' => 'options_group'
					],
					blocksy_html_tag(
						'p',
						[
							'class' => 'form-field'
						],
						blocksy_html_tag(
							'label',
							[],
							__('Swatches', 'blocksy-companion')
						) .
						blocksy_html_tag(
							'input',
							[
								'id' => 'ct-woo-attributes-list',
								'name' => 'ct-woo-attributes-list',
								'class' => 'ct-woo-attributes-list',
								'data-options' => htmlspecialchars(
									wp_json_encode($options)
								),
								'value' => $meta
							],
							''
						)
					)
				)
			);
		});

		add_action('wp_ajax_blocksy_save_attributes_swatches', function () {
			if (! current_user_can('edit_posts')) {
				wp_send_json_error();
			}

			if (
				empty($_POST['woocommerce_meta_nonce'])
				||
				! wp_verify_nonce(sanitize_key($_POST['woocommerce_meta_nonce']), 'woocommerce_save_data')
			) {
				wp_send_json_error();
			}

			$url = wp_get_referer();
			$queryString = parse_url($url, PHP_URL_QUERY);

			parse_str($queryString, $params);

			$postId = isset($params['post']) ? $params['post'] : null;

			if (!$postId) {
				wp_send_json_error();
			}

			$attributes_config = isset($_POST['ct-woo-attributes-list']) ? $_POST['ct-woo-attributes-list'] : []; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if (! $attributes_config) {
				delete_post_meta($postId, '_ct-woo-attributes-list');

				wp_send_json_success([
					'message' => __('Swatches removed', 'blocksy-companion')
				]);
				return;
			}

			update_post_meta($postId, '_ct-woo-attributes-list', $attributes_config);
			wp_send_json_success([
				'message' => __('Swatches saved', 'blocksy-companion'),
				'$attributes_config' => $attributes_config
			]);
		});
	}

	public function blc_save_swatches_for_product($post_id) {
		if (
			empty($_POST['woocommerce_meta_nonce'])
			||
			! wp_verify_nonce(
				sanitize_key($_POST['woocommerce_meta_nonce']),
				'woocommerce_save_data'
			)
		) {
			return;
		}

		$attributes_config = [];

		if (isset($_POST['ct-woo-attributes-list'])) {
			$attributes_config = $_POST['ct-woo-attributes-list'];
		}

		if (! $attributes_config) {
			delete_post_meta($post_id, '_ct-woo-attributes-list');
			return;
		}

		update_post_meta($post_id, '_ct-woo-attributes-list', $attributes_config);
	}

	private function get_attribute_types() {
		return [
			'button' => __('Button', 'blocksy-companion'),
			'color' => __('Color', 'blocksy-companion'),
			'image' => __('Image', 'blocksy-companion'),
			'mixed' => __('Mixed', 'blocksy-companion')
		];
	}

	public function blc_add_product_tab($tabs) {
		$tabs['ct-swatches'] = array(
			'label' => __('Swatches', 'blocksy-companion'),
			'target' => 'ct_product_variation_swatches',
			'class' => [
				'hide_if_simple',
				'hide_if_virtual',
				'hide_if_grouped',
				'hide_if_external'
			],
			'priority' => 60,
		);

		return $tabs;
	}

	public function add_layer_options($opt) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$tax_choices = [];
		$taxonomies_to_show = [
			'ct_custom_attributes' => [
				'label' => __('Custom Attributes', 'blocksy-companion'),
				'options' => [
					blocksy_rand_md5() => [
						'type' => 'ct-group',
						'options' => [
							'limit_number_of_swatches' => [
								'label' => __('Terms Limit', 'blocksy-companion'),
								'type' => 'ct-switch',
								'value' => 'no',
								'desc' => __('Set how many terms you want to display in this attribute.', 'blocksy-companion'),
							],

							blocksy_rand_md5() => [
								'type' => 'ct-condition',
								'condition' => [
									'limit_number_of_swatches' => 'yes'
								],
								'options' => [
									'limit' => [
										'label' => __('Limit', 'blocksy-companion'),
										'type' => 'ct-number',
										'min' => 0,
										'max' => 100,
										'value' => 10,
										'design' => 'inline',
									],
								]
							]
						],
					],
				]
			]
		];

		foreach ($attribute_taxonomies as $tax) {
			$tax_choices[$tax->attribute_name] = $tax->attribute_label;

			$taxonomies_to_show[$tax->attribute_name] = [
				'label' => $tax->attribute_label,
				'options' => [
					blocksy_rand_md5() => [
						'type' => 'ct-group',
						'options' => [

							'limit_number_of_swatches' => [
								'label' => __('Terms Limit', 'blocksy-companion'),
								'type' => 'ct-switch',
								'value' => 'no',
								'desc' => __('Set how many terms you want to display in this attribute.', 'blocksy-companion'),
							],

							blocksy_rand_md5() => [
								'type' => 'ct-condition',
								'condition' => [
									'limit_number_of_swatches' => 'yes'
								],
								'options' => [
									'limit' => [
										'label' => __('Limit', 'blocksy-companion'),
										'type' => 'ct-number',
										'min' => 0,
										'max' => 100,
										'value' => 10,
										'design' => 'inline',
									],
								]
							]

						],
					],
				]
			];
		}

		return array_merge(
			$opt,
			[
				'product_swatches' => [
					'label' => __('Swatches', 'blocksy-companion'),
					'options' => [
						'product_attributes_source' => [
							'type' => 'ct-radio',
							'label' => false,
							'value' => 'all',
							'design' => 'block',
							'disableRevertButton' => true,
							'choices' => [
								'all' => __('All', 'blocksy-companion'),
								'custom' => __('Custom', 'blocksy-companion'),
							],
						],

						blocksy_rand_md5() => [
							'type' => 'ct-condition',
							'condition' => ['product_attributes_source' => 'custom'],
							'options' => [
								'taxonomies_to_show' => [
									'label' => false,
									'type' => 'ct-layers',
									'manageable' => true,
									'value' => [],
									'settings' => $taxonomies_to_show
								]
							]
						],

						blocksy_rand_md5() => [
							'type' => 'ct-condition',
							'condition' => [
								'product_attributes_source' => 'all',
							],
							'options' => [

								blocksy_rand_md5() => [
									'type' => 'ct-group',
									'options' => [

										'limit_number_of_swatches' => [
											'label' => __('Terms Limit', 'blocksy-companion'),
											'type' => 'ct-switch',
											'value' => 'no',
											'desc' => __('Set how many terms you want to display in each attribute.', 'blocksy-companion'),
										],

										blocksy_rand_md5() => [
											'type' => 'ct-condition',
											'condition' => [
												'product_attributes_source' => 'all',
												'limit_number_of_swatches' => 'yes'
											],
											'options' => [
												'limit' => [
													'label' => __('Limit', 'blocksy-companion'),
													'type' => 'ct-number',
													'min' => 0,
													'max' => 100,
													'value' => 10,
													'design' => 'inline',
												],
											]
										],

									],
								],
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
	}

	public function render_layer($layer) {
		if ($layer['id'] !== 'product_swatches') {
			return;
		}

		global $product;
		$renderer = new SwatchesFrontend();

		if ($product->get_type() !== 'variable') {
			return '';
		}

		echo $renderer->render_variation_swatches([
			'product' => $product,
			'layer' => $layer
		]);
	}
}
