<?php

namespace Blocksy\Extensions\WoocommerceExtra;

abstract class BaseFilter {
	public $attributes = [];

	private $cached_product_ids = null;
	protected $ignore_current_query = false;

	// ['items' => [], 'list_attr' => []]
	abstract public function render();
	abstract public function get_filter_name();

	public function __construct($attributes, $args = []) {
		$args = wp_parse_args($args, [
			'ignore_current_query' => false
		]);

		$this->attributes = $attributes;

		$this->ignore_current_query = $args['ignore_current_query'];
	}

	public function additional_query_string_params() {
		return [];
	}

	static public function get_filter_for($attributes, $args = []) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		$initial_attribute = null;

		if (sizeof($attribute_taxonomies)) {
			$initial_attribute = reset($attribute_taxonomies)->attribute_name;
		}

		if (
			isset($attributes['taxonomy'])
			&&
			$attributes['taxonomy'] !== null
		) {
			$maybe_taxonomy_name = str_replace('filter_', '', $attributes['taxonomy']);

			if (taxonomy_exists(wc_attribute_taxonomy_name($maybe_taxonomy_name))) {
				$initial_attribute = $maybe_taxonomy_name;
			}
		}

		$attributes = wp_parse_args($attributes, [
			'type' => 'categories',
			'viewType' => 'list',
			'attribute' => -1,
			'showCounters' => false,
			'attribute' => $initial_attribute,
			'taxonomy' => 'product_cat',
			'showLabel' => true,
			'multipleFilters' => true,
			'hierarchical' => false,
			'showResetButton' => false,
			'showCheckbox' => true,
			'showSearch' => false,
			'showAttributesCheckbox' => false,
			'showItemsRendered' => true,
			'expandable' => false,
			'defaultExpanded' => true,
			'logoMaxW' => 40,
			'useFrame' => false,
			'aspectRatio' => '16/9',
			'excludeTaxonomy' => false,
			'taxonomy_not_in' => [],
			'limitHeight' => false,
			'limitHeightValue' => 400,
			'showTooltips' => false
		]);

		if ($attributes['type'] === 'categories') {
			return new TaxonomiesFilter($attributes, $args);
		}

		$storage = new Storage();
		$settings = $storage->get_settings();

		if ($attributes['type'] === 'attributes') {
			return new AttributesFilter($attributes, $args);
		}

		return null;
	}

	static public function get_url_without_filters() {
		$cleaned_url = add_query_arg([]);

		$to_try = ['categories', 'attributes'];
		$params = FiltersUtils::get_query_params();

		foreach ($params['params'] as $key => $value) {
			foreach ($to_try as $filter_type) {
				$filter = self::get_filter_for([
					'type' => $filter_type,
					'taxonomy' => str_replace('filter_', '', $key)
				]);

				if (! $filter) {
					continue;
				}

				$cleaned_url = $filter->remove_my_filters_from_url(
					$cleaned_url,
					$key
				);
			}
		}

		$cleaned_url = remove_query_arg(
			[
				'min_price',
				'max_price'
			],
			$cleaned_url
		);

		return $cleaned_url;
	}

	public function remove_my_filters_from_url($url, $key) {
		$filter_name = $this->get_filter_name();

		if ($filter_name !== $key) {
			return $url;
		}

		return remove_query_arg($key, $url);
	}

	public function get_applied_filter_descriptor($key, $value) {
		$filter_name = $this->get_filter_name();

		if ($filter_name !== $key) {
			return null;
		}

		$term = get_term($value);

		if ($term) {
			return [
				'name' => $term->name,
				'href' => $this->get_link_url($value)
			];
		}

		return null;
	}

	public function get_link_url($value, $args = []) {
		$args = wp_parse_args($args, [
			'is_multiple' => true,
			'to_add' => []
		]);

		$value = urldecode($value);

		$query_string = array_merge([
			$this->get_filter_name() => $value,
		], $args['to_add']);

		$params = FiltersUtils::get_query_params();

		$url = $params['url'];
		$params = $params['params'];

		if (isset($params[$this->get_filter_name()])) {
			$url = remove_query_arg(
				array_merge([
					$this->get_filter_name()
				], array_keys($args['to_add'])),
				$url
			);

			$all_attrs = explode(',', $params[$this->get_filter_name()]);

			if ($args['is_multiple']) {
				if (in_array($value, $all_attrs)) {
					$all_attrs = array_diff($all_attrs, [$value]);
				} else {
					array_push($all_attrs, $value);
				}
			} else {
				$all_attrs = array_diff([$value], $all_attrs);
			}

			if (! empty($all_attrs)) {
				$query_string = array_merge([
					$this->get_filter_name() => implode(',', $all_attrs)
				], $args['to_add']);
			} else {
				$query_string = [];
			}
		}

		$url = add_query_arg($query_string, $url);

		// if url contains page in url, remove it
		//
		// Need to understand why is that.
		$url = preg_replace('/\/page\/[0-9]+/', '', $url);

		return $url;
	}

	public function is_filter_active($term) {
		$params = FiltersUtils::get_query_params();

		return (
			isset($params['params'][$this->get_filter_name()])
			&&
			in_array(
				urldecode($term),
				explode(',', $params['params'][$this->get_filter_name()])
			)
		);
	}

	public function format_products_count($args = []) {
		$args = wp_parse_args($args, [
			'count' => 0,
			'with_wrap' => false
		]);

		if ($args['count'] === 0) {
			return '';
		}

		if ($args['with_wrap']) {
			$args['count'] = '(' . $args['count'] . ')';
		}

		return blocksy_html_tag(
			'span',
			['class' => 'ct-filter-count'],
			$args['count']
		);
	}

	final protected function get_terms_counts($args = []) {
		global $wpdb;

		$args = wp_parse_args($args, [
			'product_ids' => $this->get_product_ids(),
			'term_ids' => []
		]);

		$sql = $this->get_terms_counts_sql($args);

		if (! $sql) {
			return [];
		}

		return $wpdb->get_results($sql, OBJECT_K);
	}

	protected function get_product_ids() {
		if ($this->cached_product_ids !== null) {
			return $this->cached_product_ids;
		}

		if ($this->ignore_current_query) {
			$lookup_table = blc_get_ext('woocommerce-extra')
				->filters
				->lookup_table;

			$product_ids = $lookup_table->get_product_ids();

			$this->cached_product_ids = $product_ids;

			return $this->cached_product_ids;
		}

		$apply_filters = new ApplyFilters();

		$query_params = FiltersUtils::get_query_params()['params'];

		unset($query_params[$this->get_filter_name()]);

		$products_query = $apply_filters->get_custom_query_for($query_params);

		$this->cached_product_ids = $products_query->posts;

		return $this->cached_product_ids;
	}

	protected function get_terms_counts_sql($args = []) {
		global $wpdb;

		$args = wp_parse_args($args, [
			'product_ids' => [],
			'term_ids' => []
		]);

		if (
			empty($args['product_ids'])
			||
			empty($args['term_ids'])
		) {
			return '';
		}

		return "
			SELECT term_relationships.term_taxonomy_id as term_id, COUNT(DISTINCT posts.ID) as term_count
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			WHERE (
				posts.ID IN (" . implode(',', $args['product_ids']) . ")
				AND
				term_relationships.term_taxonomy_id IN (" . implode(',', $args['term_ids']) . ")
			)
			GROUP BY term_relationships.term_taxonomy_id
		";
	}
}
