<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class BrandsImportExport {
	public function __construct() {
		add_filter('woocommerce_product_export_column_names', [$this, 'add_columns']);
		add_filter('woocommerce_product_export_product_default_columns', [$this, 'add_columns']);
		add_filter('woocommerce_product_export_product_column_blocksy_product_brands', [$this, 'export_taxonomy'], 10, 2);

		add_filter('woocommerce_csv_product_import_mapping_options', [$this, 'map_columns']);
		add_filter('woocommerce_csv_product_import_mapping_default_columns', [$this, 'add_columns_to_mapping_screen']);
		add_filter('woocommerce_product_import_inserted_product_object', [$this, 'set_taxonomy'], 10, 2);
	}

	public function add_columns($columns) {
		$columns['blocksy_product_brands'] = __('Blocksy Brands', 'blocksy-companion');

		return $columns;
	}

	public function export_taxonomy($value, $product) {
		$taxonomy = 'product_brands';

		$term_ids = wp_get_post_terms($product->get_id(), $taxonomy, ['fields' => 'ids']);
		$formatted_terms = [];

		if (! count($term_ids)) {
			return '';
		}

		if (! is_wp_error($term_ids)) {
			foreach ($term_ids as $term_id) {
				$formatted_term = [];
				$ancestor_ids   = array_reverse(get_ancestors($term_id, $taxonomy));

				foreach ($ancestor_ids as $ancestor_id) {
					$term = get_term($ancestor_id, $taxonomy);
					if ($term && ! is_wp_error($term)) {
						$formatted_term[] = $term->name;
					}
				}

				$term = get_term($term_id, $taxonomy);

				if ($term && ! is_wp_error($term)) {
					$formatted_term[] = $term->name;
				}

				$formatted_terms[] = implode(' > ', $formatted_term);
			}
		}

		return $this->implode_values($formatted_terms);
	}

	protected function implode_values($values) {
		$values_to_implode = array();

		foreach ($values as $value) {
			$value               = (string) is_scalar($value) ? html_entity_decode($value, ENT_QUOTES) : '';
			$values_to_implode[] = str_replace(',', '\\,', $value);
		}

		return implode(', ', $values_to_implode);
	}

	public function map_columns($columns) {
		$columns['blocksy_product_brands'] = __('Blocksy Brands', 'blocksy-companion');

		return $columns;
	}

	public function add_columns_to_mapping_screen($columns) {
		$columns[__('Blocksy Brands', 'blocksy-companion')] = 'blocksy_product_brands';

		return $columns;
	}

	public function set_taxonomy($product, $data) {
		if (! $product instanceof \WC_Product) {
			return $product;
		}

		if (empty($data['blocksy_product_brands'])) {
			return $product;
		}

		wp_delete_object_term_relationships($product->get_id(), 'product_brands');

		$product_brands = explode(',', $data['blocksy_product_brands']);
		$terms = [];

		foreach ($product_brands as $brands) {
			if (! current_user_can('manage_product_terms')) {
				break;
			}

			$brands = trim(html_entity_decode($brands));

			if (empty($brands)) {
				continue;
			}

			if (strpos($brands, '>') !== false) {
				$brands = explode('>', $brands);
			} else {
				$brands = [$brands];
			}

			$parent = 0;

			
			foreach ($brands as $brand) {
				$brand = trim($brand);

				if (empty($brand)) {
					continue;
				}

				$term = term_exists($brand, 'product_brands');

				if ($term) {
					$parent = $term['term_id'];

					$terms[] = intval($term['term_id']);
				} else {
					$term = wp_insert_term($brand, 'product_brands', ['parent' => $parent]);

					if (is_wp_error($term)) {
						if ($term->get_error_code() === 'term_exists') {
							$term_id = $term->get_error_data();
						} else {
							break;
						}
					} else {
						$term_id = $term['term_id'];
					}

					$parent = $term_id;
				}

				if ($term_id) {
					$terms[] = $term_id;
				}
			}
		}

		wp_set_object_terms($product->get_id(), $terms, 'product_brands');
	}
}
