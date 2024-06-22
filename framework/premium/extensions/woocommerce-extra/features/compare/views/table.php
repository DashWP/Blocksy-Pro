<?php

if ( !function_exists('blc_render_compare_column') ) {
	function blc_render_compare_column(
		$content = '~',
		$attrs = [
			'class' => 'ct-compare-column'
		]
	) {
		return blocksy_html_tag(
			'div',
			$attrs,
			$content
		);
	}
}

if (!function_exists('blc_render_compare_table_actions')) {
	function blc_render_compare_table_actions(
		$product,
		$maybeVariations,
		$is_mobile = false
	) {
		global $has_compare_list;
		$has_compare_list = true;

		$maybeVariationsAttrs = $product->get_attributes();

		if (
			isset($maybeVariations['attributes']) &&
			!empty($maybeVariations['attributes'])
		) {
			$maybeVariationsAttrs = array_merge(
				$maybeVariationsAttrs,
				$maybeVariations['attributes']
			);
		}

		$product->set_attributes($maybeVariationsAttrs);

		$is_simple_product = blc_get_ext(
			'woocommerce-extra'
		)->utils->is_simple_product($product);

		if ($is_simple_product['value'] && !$is_mobile) {
			do_action('woocommerce_simple_add_to_cart');
		} else {
			woocommerce_template_loop_add_to_cart();
		}

		$has_compare_list = false;
	}
}

if (!function_exists('blc_row_classes')) {
	function blc_row_classes($layout) {
		$classes = ['ct-compare-row'];

		if (
			blocksy_akg('compare_row_sticky', $layout, 'no') === 'yes'
			&&
			blocksy_get_theme_mod('compare_table_placement', 'modal') === 'modal'
		) {
			$classes[] = 'ct-compare-row-is-sticky';
		}

		return trim(implode(' ', $classes));
	}
}

$compare_list = blc_get_ext('woocommerce-extra')
	->get_compare()
	->get_current_compare_list();


add_filter('wsa_sample_should_add_button', '__return_false');

if (class_exists('EPOFW_Front')) {
	$instance = EPOFW_Front::instance();

	remove_action(
		'woocommerce_before_add_to_cart_button',
		[$instance, 'epofw_before_add_to_cart_button'],
		10
	);

	remove_action(
		'woocommerce_after_add_to_cart_button',
		[$instance, 'epofw_after_add_to_cart_button'],
		10
	);
}

$render_layout_config = blocksy_get_theme_mod('product_compare_layout', [
	[
		'id' => 'product_main',
		'enabled' => true,
	],
	[
		'id' => 'product_description',
		'enabled' => true,
	],
	[
		'id' => 'product_attributes',
		'enabled' => true,
		'product_attributes_source' => 'all',
	],
	[
		'id' => 'product_availability',
		'enabled' => true,
	],
]);

if (count($compare_list) > 0) {
	$products = [];

	foreach ($compare_list as $single_product) {
		$products[] = wc_get_product($single_product['id']);
	}
?>

	<div class="ct-compare-table" style="<?php echo '--compare-products:' . count($products) . ';'  ?>">
		<?php

			echo '<div class="ct-compare-row">';
			echo blc_render_compare_column(
				'&nbsp;',
				[
					'class' => 'ct-compare-column ct-compare-item-label',
				]
			);

			if (function_exists('blocksy_action_button')) {
				foreach ($products as $product) {
					echo blc_render_compare_column(
						blocksy_action_button(
							[
								'button_html_attributes' => [
									'href' => '#compare-remove-' . $product->get_id(),
									'class' => 'ct-compare-remove',
									'data-id' => $product->get_id(),
									'title' => __('Remove Product', 'blocksy-companion')
								],
								'icon' => '<svg viewBox="0 0 15 15"><path d="M8.5,7.5l4.5,4.5l-1,1L7.5,8.5L3,13l-1-1l4.5-4.5L2,3l1-1l4.5,4.5L12,2l1,1L8.5,7.5z"></path></svg>',
								'content' => __('Remove Product', 'blocksy-companion')
							]
						)
					);
				}
			}

			echo '</div>';

			foreach ($render_layout_config as $layout) {

				if (!$layout['enabled']) {
					continue;
				}

				if ($layout['id'] === 'product_main') {
					echo '<div class="' . blc_row_classes($layout) . '">';
					echo blc_render_compare_column(
						__('General', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					foreach ($products as $product) {
						$GLOBALS['product'] = $product;
						echo '<div class="ct-compare-column">';

						$thumbnail = $product->get_image();

						$link_attrs = [
							'href' => $product->is_visible() ? $product->get_permalink() : '',
						];

						$output = '';

						if (function_exists('blocksy_output_add_to_wish_list')) {
							$output = blocksy_html_tag(
								'div',
								[
									'class' => 'ct-woo-card-extra',
									'data-type' => 'type-1'
								],
								blocksy_output_add_to_wish_list('archive')
							);
						}

						echo blocksy_html_tag(
							'figure',
							[],
							$output . blocksy_media(
								[
									'attachment_id' => get_post_thumbnail_id($product->get_id()),
									'post_id' => $product->get_id(),
									'ratio' => blocksy_get_compare_ratio($layout),
									'size' => blocksy_akg('compare_image_size', $layout, 'medium_large'),
									'class' => 'ct-media-container',
									'tag_name' => 'a',
									'html_atts' => $link_attrs
								]
							)
						);

						echo blocksy_html_tag(
							'h2',
							[
								'class' => esc_attr(
									apply_filters(
										'woocommerce_product_loop_title_classes',
										'woocommerce-loop-product__title'
									)
								),
							],
							blocksy_html_tag(
								'a',
								array_merge(
									[
										'class' =>
										'woocommerce-LoopProduct-link woocommerce-loop-product__link',
									],
									$link_attrs
								),
								blc_get_ext(
									'woocommerce-extra'
								)->utils->get_formatted_title($product->get_id())
							)
						);

						woocommerce_template_loop_price();

						woocommerce_template_loop_add_to_cart();

						echo '</div>';
					}

					echo '</div>';

					continue;
				}

				if ($layout['id'] === 'product_description') {
					echo '<div class="' . blc_row_classes($layout) . '">';
					echo blc_render_compare_column(
						__('Description', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					foreach ($products as $product) {
						$GLOBALS['product'] = $product;
						ob_start();
						blocksy_trim_excerpt($product->get_short_description(),  blocksy_akg('excerpt_length', $layout, '40'));
						$excerpt = ob_get_clean();

						echo blc_render_compare_column(
							$excerpt
						);
					}

					echo '</div>';

					continue;
				}

				if ($layout['id'] === 'product_attributes') {
					$taxonomies = [];

					if ( blocksy_akg('product_attributes_source', $layout, 'all') === 'all' ) {
						$attribute_taxonomies = wc_get_attribute_taxonomies();

						foreach ($attribute_taxonomies as $tax) {
							$taxonomies[] = $tax->attribute_name;
						}
					} else {
						if (
							! isset( $layout['taxonomies_to_compare'])
							||
							empty($layout['taxonomies_to_compare'])
						) {
							continue;
						}

						$taxonomies = array_column($layout['taxonomies_to_compare'], 'id');
					}

					if (empty($taxonomies)) {
						continue;
					}

					foreach ($taxonomies as $taxonomy_to_compare) {
						if (
							! $taxonomy_to_compare
							||
							! taxonomy_exists(wc_attribute_taxonomy_name($taxonomy_to_compare))
						) {
							continue;
						}

						$taxonomy_name = wc_attribute_taxonomy_name($taxonomy_to_compare);
						$taxonomy_hr_name = $taxonomy_to_compare;

						if (taxonomy_exists($taxonomy_name)) {
							$labels = get_taxonomy_labels(get_taxonomy($taxonomy_name));

							if (isset($labels->singular_name)) {
								$taxonomy_hr_name = $labels->singular_name;
							}
						}

						$columns = [];
						$columns[] = blc_render_compare_column(
							$taxonomy_hr_name,
							[
								'class' => 'ct-compare-column ct-compare-item-label',
							]
						);

						$has_content = false;

						foreach ($products as $product) {
							$GLOBALS['product'] = $product;

							$attributes = $product->get_attributes();

							if ( ! isset($attributes[sanitize_title($taxonomy_name)]) ) {
								$columns[] = blc_render_compare_column();
								continue;
							}

							$attribute = $attributes[sanitize_title($taxonomy_name)];

							if ($attribute === false) {
								$columns[] = blc_render_compare_column();
								continue;
							} else {
								$values = [];

								if ( $attribute->is_taxonomy() ) {
									$attribute_taxonomy = $attribute->get_taxonomy_object();
									$attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

									foreach ( $attribute_values as $attribute_value ) {

										$value_name = esc_html( $attribute_value->name );

										if ( $attribute_taxonomy->attribute_public ) {
											$values[] = $value_name;
										} else {
											$values[] = $value_name;
										}
									}
								} else {
									$values = $attribute->get_options();

									foreach ( $values as &$value ) {
										$value = make_clickable( esc_html( $value ) );
									}
								}

								$has_content = true;

								$columns[] = blc_render_compare_column(
									apply_filters(
										'woocommerce_attribute',
										wpautop( wptexturize( implode( ', ', $values ) ) ),
										$attribute,
										$values
									)
								);
							}
						}
						if ( $has_content ) {
							echo blocksy_html_tag(
								'div',
								[
									'class' =>  blc_row_classes($layout)
								],
								join('', $columns)
							);
						}
					}

					continue;
				}

				if ( $layout['id'] === 'product_rating' ) {
					$columns = [];
					$columns[] = blc_render_compare_column(
						__('Rating', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					$has_content = false;

					foreach ($products as $product) {
						$GLOBALS['product'] = $product;
						ob_start();
						woocommerce_template_loop_rating();
						$rating = ob_get_clean();

						if ( ! empty($rating) ) {
							$has_content = true;

							$columns[] = blc_render_compare_column($rating);
							continue;
						}

						$columns[] = blc_render_compare_column();
					}

					if ( $has_content ) {
						echo blocksy_html_tag(
							'div',
							[
								'class' =>  blc_row_classes($layout)
							],
							join('', $columns)
						);
					}

					continue;
				}

				if ( $layout['id'] === 'product_sku' ) {
					$columns = [];
					$columns[] = blc_render_compare_column(
						__('SKU', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					$has_content = false;

					foreach ($products as $product) {
						if ( empty($product->get_sku()) ) {
							$columns[] = blc_render_compare_column();
							continue;
						}

						$has_content = true;
						$columns[] = blc_render_compare_column(
							$product->get_sku()
						);
					}

					if ( $has_content ) {
						echo blocksy_html_tag(
							'div',
							[
								'class' => blc_row_classes($layout)
							],
							join('', $columns)
						);
					}

					continue;
				}

				if ($layout['id'] === 'product_availability') {
					echo '<div class="' . blc_row_classes($layout) . '">';
					echo blc_render_compare_column(
						__('Availability', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					foreach ($products as $product) {
						$GLOBALS['product'] = $product;
						$availability = $product->is_in_stock();

						echo blc_render_compare_column(
							$availability ? __('In Stock', 'blocksy-companion') : __('Out of Stock', 'blocksy-companion')
						);
					}

					echo '</div>';

					continue;
				}

				if ($layout['id'] === 'product_brands') {
					echo '<div class="' . blc_row_classes($layout) . '">';
					echo blc_render_compare_column(
						__('Brands', 'blocksy-companion'),
						[
							'class' => 'ct-compare-column ct-compare-item-label',
						]
					);

					foreach ($products as $product) {
						$GLOBALS['product'] = $product;

						$brands = get_the_terms($product->get_id(), 'product_brands');

						if (!$brands || !is_array($brands)) {
							echo blocksy_html_tag(
								'div',
								[
									'class' => 'ct-compare-column'
								],
								'~'
							);
							continue;
						}

						if (!count($brands)) {
							echo blc_render_compare_column();
							continue;
						}

						foreach ($brands as $key => $brand) {
							$label = blocksy_html_tag(
								'a',
								[
									'href' => esc_url(get_term_link($brand)),
									'class' => 'ct-button'
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
									]
								]);
							}
						}

						echo blocksy_html_tag(
							'div',
							[
								'class' => 'ct-compare-column'
							],
							blocksy_html_tag(
								'div',
								[
									'class' => 'ct-product-brands'
								],
								$label
							)
						);
					}

					echo '</div>';

					continue;
				}

				// do_action('blocksy:woocommerce:compare:custom:layer', $layout);

			}
		?>
	</div>

	<?php } else { ?>

	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url(
														apply_filters(
															'woocommerce_return_to_shop_redirect',
															wc_get_page_permalink('shop')
														)
													); ?>">

			<?php echo __('Browse products', 'blocksy-companion'); ?>
		</a>

		<?php echo __(
			"You don't have any products in your compare list yet.",
			'blocksy-companion'
		); ?>
	</div>

<?php } ?>
