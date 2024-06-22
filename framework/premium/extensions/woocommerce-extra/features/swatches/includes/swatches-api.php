<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class SwatchesApi {
	public function __construct() {
		$action = 'blocksy_swatches_get_product_out_of_stock_variations';

		add_action('wp_ajax_' . $action, [$this, 'handle_request']);
		add_action('wp_ajax_nopriv_' . $action, [$this, 'handle_request']);
	}

	public function handle_request() {
		$body = json_decode(file_get_contents('php://input'), true);

		if (! isset($body['product_id'])) {
			wp_send_json_error();
		}

		$product = wc_get_product($body['product_id']);

		$children = $product->get_children();

		$all_ids = [];

		global $wpdb;

		$query =
			"SELECT post_id from $wpdb->postmeta
				WHERE (meta_key = '_stock_status')
				AND (meta_value = 'outofstock')
				AND post_id IN (" . implode(',', $children) . ")";

		$all_ids = $wpdb->get_col($query);

		$result = [];

		foreach ($all_ids as $v) {
			$result[] = $product->get_available_variation($v);
		}

		$all_attributes = $product->get_variation_attributes();

		$attributes_stock = [];

		foreach ($all_attributes as $key => $terms) {
			$attribute_terms_stock = blc_get_ext('woocommerce-extra')
				->utils
				->get_attributes_terms_stock($product, $key);

			$name = blc_get_ext('woocommerce-extra')
				->utils
				->format_attribute_slug($key);

			$attributes_stock[$name] = $attribute_terms_stock;
		}

		wp_send_json_success([
			'attributes_stock' => $attributes_stock,
			'variations' => $result
		]);
	}
}
