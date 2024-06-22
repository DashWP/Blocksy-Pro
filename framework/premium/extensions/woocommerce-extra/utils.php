<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class Utils {
	private $products_cache = [];

	public function is_simple_product($product_object) {
		if (! $product_object) {
			return [
				'value' => false,
			];
		}

		$is_simple = (
			$product_object->is_type('simple')
			||
			$product_object->is_type('variation')
		);

		if (
			class_exists('WC_Price_Calculator_Product')
			&&
			\WC_Price_Calculator_Product::calculator_enabled($product_object)
		) {
			return [
				'value' => false,
				'fake_type' => 'variable'
			];
		}

		if (
			class_exists('WC_Product_Addons_Helper')
			&&
			\WC_Product_Addons_Helper::get_product_addons(
				$product_object->get_id()
			)
		) {
			return [
				'value' => false,
				'fake_type' => 'variable'
			];
		}

		if (class_exists('PPOM_Form')) {
			$form_obj = new \PPOM_Form( $product_object, [] );

			if ($form_obj->has_ppom_fields()) {
				return [
					'value' => false,
					'fake_type' => 'variable'
				];
			}
		}

		if (class_exists('PPOM_Meta')) {
			$ppom = new \PPOM_Meta($product_object->get_id());

			if ($ppom->fields) {
				return [
					'value' => false,
					'fake_type' => 'variable'
				];
			}
		}

		if (
			(
				class_exists('\SW_WAPF\Includes\Classes\Field_Groups')
				||
				class_exists('\SW_WAPF_PRO\Includes\Classes\Field_Groups')
			)
			&&
			! in_array($product_object->get_type(), ['grouped', 'external'])
		) {
			if (class_exists('\SW_WAPF\Includes\Classes\Field_Groups')) {
				$field_groups = \SW_WAPF\Includes\Classes\Field_Groups::get_valid_field_groups('product');
			} else {
				global $product;
				$prev_p = $product;
				$product = $product_object;

				$field_groups = \SW_WAPF_PRO\Includes\Classes\Field_Groups::get_valid_field_groups('product');
				$product = $prev_p;
			}

			$product_field_group = get_post_meta(
				$product_object->get_id(),
				'_wapf_fieldgroup',
				true
			);

			if ($product_field_group) {
				if (class_exists('\SW_WAPF\Includes\Classes\Field_Groups')) {
					array_unshift(
						$field_groups,
						\SW_WAPF\Includes\Classes\Field_Groups::process_data(
							$product_field_group
						)
					);
				} else {
					array_unshift(
						$field_groups,
						\SW_WAPF_PRO\Includes\Classes\Field_Groups::process_data(
							$product_field_group
						)
					);
				}
			}

			if (! empty($field_groups)) {
				return [
					'value' => false,
					'fake_type' => 'variable'
				];
			}
		}

		return [
			'value' => $is_simple
		];
	}

	public function get_formatted_title($post = 0) {
		$post = get_post($post);

		$post_title = isset($post->post_title) ? $post->post_title : '';
		$post_id = isset($post->ID) ? $post->ID : 0;

		if (! empty($post->post_password)) {
			$prepend = __('Protected: %s', 'blocksy-companion');

			$protected_title_format = apply_filters(
				'protected_title_format',
				$prepend,
				$post
			);

			$post_title = blc_safe_sprintf($protected_title_format, $post_title);
		} elseif (isset($post->post_status) && 'private' === $post->post_status) {
			$prepend = __('Private: %s', 'blocksy-companion');

			$private_title_format = apply_filters(
				'private_title_format',
				$prepend,
				$post
			);

			$post_title = blc_safe_sprintf($private_title_format, $post_title);
		}

		return apply_filters('the_title', $post_title, $post_id);
	}

	// In WooCommerce the get_available_variations() method is not cached at
	// all for some reason. This is a problem when we have a lot of products
	// with swatches on archive pages.
	//
	// TODO: respect woocommerce_ajax_variation_threshold filter here
	// and return false in case we exceed the threshold.
	//
	// All the callers of this helpers should handle this case gracefully.
	public function get_available_variations($id) {
		$id = intval($id);

		$product = wc_get_product($id);

		if (! $product) {
			return [];
		}

		if (! isset($this->products_cache[$id])) {
			$variations = $product->get_available_variations();

			$this->products_cache[$id] = $variations;
		}

		return $this->products_cache[$id];
	}

	public function get_product_taxonomies($args = []) {
		$args = wp_parse_args($args, [
			'include_attributes' => false
		]);

		$all_taxonomies = array_values(
			array_diff(
				get_object_taxonomies('product'),
				[
					"post_format",
					"product_type",
					"product_visibility",
					"product_shipping_class",
				]
			)
		);

		$result = [];

		foreach ($all_taxonomies as $taxonomy) {
			if (strpos($taxonomy, 'pa_') === 0) {
				if ($args['include_attributes']) {
					$result[] = $taxonomy;
				}

				continue;
			}

			$result[] = $taxonomy;
		}

		return $result;
	}

	public function format_attribute_slug($attribute_slug) {
		if (strpos($attribute_slug, 'attribute_') !== false) {
			return $attribute_slug;
		}

		return 'attribute_' . sanitize_title($attribute_slug);
	}

	public function get_attributes_terms_stock($product, $attribute) {
		global $wpdb;

		$product_id = $product->get_id();

		$attribute_meta_key = $this->format_attribute_slug($attribute);

		$all_terms = $this->get_attribute_terms(strtolower($attribute), $product);

		if (! $all_terms) {
			return [
				'valid' => [],
				'invalid' => []
			];
		}

		$query = $wpdb->prepare(
			"SELECT pm2.meta_value as terms, count(pm2.meta_value) as c from $wpdb->posts p, $wpdb->postmeta pm1, $wpdb->postmeta pm2, $wpdb->postmeta pm3
					WHERE p.ID = pm1.post_id
					AND  p.ID = pm2.post_id
					AND p.ID = pm3.post_id
					AND (pm1.meta_key = '_stock_status' and pm1.meta_value IN ( 'instock', 'onbackorder' ))
					AND (pm2.meta_key = %s)
					AND (pm3.meta_key = '_price')
					AND p.post_parent = %d
					AND p.post_status = 'publish'

					GROUP BY pm2.meta_value",
			$attribute_meta_key,
			$product_id
		);

		$in_stock_terms = $wpdb->get_col($query);

		$not_in_stock_terms = array_diff($all_terms, $in_stock_terms);

		return [
			'valid' => $in_stock_terms,
			'invalid' => array_values($not_in_stock_terms)
		];
	}

	public function get_attribute_terms($attribute, $product) {
		$all_attributes = $product->get_variation_attributes();

		foreach ($all_attributes as $attribute_key => $terms) {
			$clean_attribute_key = sanitize_title(strtolower($attribute_key));
			$clean_attribute = sanitize_title(strtolower($attribute));

			if ($clean_attribute_key === $clean_attribute) {
				return $terms;
			}
		}

		return false;
	}
}
