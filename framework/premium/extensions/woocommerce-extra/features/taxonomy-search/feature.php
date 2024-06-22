<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class TaxonomySearch {
	public function __construct() {
		add_filter('posts_where', function ($where, \WP_Query $query) {
			if (! $query->is_search()) {
				return $where;
			}

			if (is_admin()) {
				return $where;
			}

			global $pagenow, $wpdb, $wp;

			$s = null;

			if (isset($wp->query_vars['s'])) {
				$s = $wp->query_vars['s'];
			}

			if (isset($_GET['search'])) {
				$s = $_GET['search'];
			}

			$per_page = 10;

			if (isset($_GET['per_page'])) {
				$per_page = $_GET['per_page'];
			}

			if (! $s) {
				return $where;
			}

			if (! isset($_GET['ct_search_taxonomies'])) {
				return $where;
			}

			if (
				is_admin()
				&&
				'edit.php' != $pagenow
			) {
				return $where;
			}

			$all_post_types = blocksy_manager()->post_types->get_all();

			$post_types = $all_post_types;

			if (isset($_GET['post_type'])) {
				$post_types = explode(
					':',
					str_replace('ct_forced_', '', $_GET['post_type'])
				);

				if ($post_types[0] === 'any') {
					$post_types = $all_post_types;
				}
			}

			$tax_query = [
				'relation' => 'OR'
			];

			foreach ($post_types as $key => $post_type) {
				$taxonomies = get_object_taxonomies($post_type, 'objects');

				foreach($taxonomies as $taxonomy) {
					$tax_query[] = [
						'taxonomy' => $taxonomy->name,
						'field' => 'slug',
						'terms' => strtolower($s),
						'operator' => 'IN'
					];
				}
			}

			if (empty($tax_query)) {
				return $where;
			}

			$args = array(
				'post_type' => $post_types,
				'posts_per_page' => $per_page,
				'status' => 'publish',
				'fields' => 'ids',
				'suppress_filters' => false,
				'tax_query' => $tax_query
			);

			$posts = new \WP_Query($args);

			if (empty($posts->posts)) {
				return $where;
			}

			$where = str_replace(
				'))',
				") OR ({$wpdb->posts}.ID IN (" . implode(',', $posts->posts) . ")))",
				$where
			);

			return $where;
		}, 9, 2);
	}
}
