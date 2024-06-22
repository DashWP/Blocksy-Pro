<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class ImportExport {
	private $export_type = 'product';
	private $column_id = 'blocksy_variation_images';

	public function __construct() {
		add_filter(
			"woocommerce_product_export_{$this->export_type}_default_columns",
			[$this, 'export_column_name']
		);
		add_filter(
			"woocommerce_product_export_{$this->export_type}_column_{$this->column_id}",
			[$this, 'export_column_data'],
			10, 3
		);

		add_filter(
			'woocommerce_csv_product_import_mapping_options',
			[$this, 'export_column_name']
	   	);
		add_filter(
			'woocommerce_csv_product_import_mapping_default_columns',
			[$this, 'default_import_column_name']
	   	);
		add_action(
			'woocommerce_product_import_inserted_product_object',
			[$this, 'process_wc_import'],
			10, 2
		);
	}

	public function export_column_name($columns) {
		$columns[$this->column_id] = __('Blocksy Variation Images', 'blocksy-companion');
		return $columns;
	}

	public function default_import_column_name($columns) {
		$columns[__('Blocksy Variation Images', 'blocksy-companion')] = $this->column_id;
		return $columns;
	}

	public function export_column_data($value, $product, $column_id) {
		$product_id = $product->get_id();

		$gallery_images = get_post_meta(
			$product_id,
			'blocksy_post_meta_options',
			true
		);

		if (empty($gallery_images)) {
			return '';
		}

		return json_encode($gallery_images);
	}

	public function process_wc_import($product, $data) {
		$product_id = $product->get_id();

		if (! isset($data[$this->column_id])) {
			return;
		}

		if (empty($data[$this->column_id])) {
			return;
		}

		$raw_data = json_decode($data[$this->column_id], true);

		if (! $raw_data) {
			return;
		}

		update_post_meta(
			$product_id,
			'blocksy_post_meta_options',
			$raw_data
		);
	}
}


