<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class CommonWCFilter extends BaseFilter {
	public function get_filter_name() {
		return 'common_wc';
	}

	public function render() {
		return [
			'items' => []
		];
	}

	public function wp_query_arg($query_string, $query_args) {
		add_filter(
			'pre_option_woocommerce_attribute_lookup_enabled',
			[$this, 'force_enable_attribute_lookup']
		);

		$tax_query = WC()->query->get_tax_query(null, true);

		remove_filter(
			'pre_option_woocommerce_attribute_lookup_enabled',
			[$this, 'force_enable_attribute_lookup']
		);

		if (! isset($query_args['tax_query'])) {
			$query_args['tax_query'] = [];
		}

		$query_args['tax_query'] = array_merge(
			$query_args['tax_query'],
			$tax_query
		);

		return $query_args;
	}

	public function force_enable_attribute_lookup($value) {
		return 'yes';
	}

	public function posts_clauses($clauses, $query, $query_string) {
		if (
			! isset($query_string['min_price'])
			&&
			! isset($query_string['max_price'])
		) {
			return $clauses;
		}

		global $wp_the_query;
		$prev_wp_query = $wp_the_query;
		$GLOBALS['wp_the_query'] = $query;

		$clauses = WC()->query->price_filter_post_clauses($clauses, $query);

		$GLOBALS['wp_the_query'] = $prev_wp_query;

		return $clauses;
	}
}

