<?php

namespace Blocksy\Extensions\WoocommerceExtra;

use \Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer;
use \Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;

class AttributesFilter extends BaseFilter {
	public function get_filter_name() {
		return 'filter_' . $this->attributes['attribute'];
	}

	public function wp_query_arg($query_string, $query_args) {
		$filterer = wc_get_container()->get(Filterer::class);

		if ($filterer->filtering_via_lookup_table_is_active()) {
			return $query_args;
		}

		$layered_nav_chosen = $this->get_layered_nav($query_string);

		foreach ($layered_nav_chosen as $taxonomy => $data) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => $data['terms'],
				'operator' => 'and' === $data['query_type'] ? 'AND' : 'IN',
				'include_children' => false,
			);
		}

		return $query_args;
	}

	public function posts_clauses($clauses, $query, $query_string) {
		$layered_nav_chosen = [];

		$filterer = wc_get_container()->get(Filterer::class);

		if (! $filterer->filtering_via_lookup_table_is_active()) {
			return $clauses;
		}

		$layered_nav_chosen = $this->get_layered_nav($query_string);

		global $wp_the_query;
		$prev_wp_query = $wp_the_query;
		$GLOBALS['wp_the_query'] = $query;

		$clauses = $filterer->filter_by_attribute_post_clauses(
			$clauses,
			$query,
			$layered_nav_chosen
		);

		$GLOBALS['wp_the_query'] = $prev_wp_query;

		return $clauses;
	}

	public function render() {
		$taxonomy_terms = $this->filter_get_terms_list();

		if (empty($taxonomy_terms)) {
			return [
				'items' => []
			];
		}

		$taxonomy_terms = $this->get_attributes_counts($taxonomy_terms);

		$additional_attrs = [];

		$storage = new Storage();
		$settings = $storage->get_settings();

		if ($settings['features']['variation-swatches']) {
			$swatch_type = 'select';

			if (sizeof($taxonomy_terms)) {
				$conf = new SwatchesConfig();

				$swatch_type = $conf->get_attribute_type(
					$taxonomy_terms[0]->taxonomy
				);
			}

			$swatch_shape = 'round';

			if ($swatch_type === 'color') {
				$swatch_shape = blocksy_get_theme_mod('color_swatch_shape', 'round');
			}

			if ($swatch_type === 'image') {
				$swatch_shape = blocksy_get_theme_mod('image_swatch_shape', 'round');
			}

			if ($swatch_type === 'button') {
				$swatch_shape = blocksy_get_theme_mod('button_swatch_shape', 'round');
			}

			if ($swatch_type === 'mixed') {
				$swatch_shape = blocksy_get_theme_mod('mixed_swatch_shape', 'round');
			}

			$additional_attrs = [
				'data-swatches-type' => $swatch_type,
				'data-swatches-shape' => $swatch_shape,
			];
		}

		$items = $this->filter_get_items($taxonomy_terms);

		return [
			'items' => $items,
			'list_attr' => $additional_attrs
		];
	}

	public function filter_get_terms_list() {
		$attribute_slug = $this->attributes['attribute'];

		if (! taxonomy_exists(wc_attribute_taxonomy_name($attribute_slug))) {
			return [];
		}

		$taxonomy_terms = [];
		$list_items_html = [];

		$params = [
			'hide_empty' => true,
			'exclude' => $this->attributes['taxonomy_not_in']
		];

		if (! $this->attributes['excludeTaxonomy']) {
			unset($params['exclude']);
		}

		$taxonomy_terms = get_terms(
			wc_attribute_taxonomy_name($attribute_slug),
			$params
		);

		if (
			! $taxonomy_terms
			||
			is_wp_error($taxonomy_terms)
		) {
			return [];
		}

		return $taxonomy_terms;
	}

	private function filter_get_items($taxonomy_terms) {
		$attribute_slug = $this->attributes['attribute'];

		$list_items_html = [];

		foreach ($taxonomy_terms as $key => $value) {
			$api_url = $this->get_link_url(
				$value->slug,
				[
					'is_multiple' => $this->attributes['multipleFilters'],
					'to_add' => [
						'query_type_' . $this->attributes['attribute'] => 'or'
					]
				]
			);

			$products_count = $this->format_products_count([
				'count' => $value->count
			]);

			if (! $products_count) {
				continue;
			}

			if (! $this->attributes['showCounters']) {
				$products_count = '';
			}

			$swatch_term_html = '';

			$conf = new SwatchesConfig();

			$swatch_term = new SwatchElementRender(
				$conf->get_swatch_element_descriptor($value)
			);

			if ($this->attributes['showItemsRendered']) {
				$swatch_term_html = $swatch_term->get_output(
					!$this->attributes['showTooltips']
				);
			}

			$label_html = $this->attributes['showLabel']
				? blocksy_html_tag(
					'span',
					['class' => 'ct-filter-label'],
					$value->name
				)
				: '';

			$item_classes = ['ct-filter-item'];

			if ($this->is_filter_active($value->slug)) {
				$item_classes[] = 'active';
			}

			$checbox_html = $this->attributes['showAttributesCheckbox']
				? blocksy_html_tag(
					'input',
					array_merge(
						[
							'type' => 'checkbox',
							'class' => 'ct-checkbox',
							'tabindex' => '-1',
							'name' => 'product_attribute_' . $value->term_id,
							'aria-label' => $value->name,
						],
						$this->is_filter_active($value->slug)
							? ['checked' => 'checked']
							: []
					)
				)
				: '';

			$list_items_html[] = blocksy_html_tag(
				'li',
				[
					'class' => implode(' ', $item_classes),
				],
				blocksy_html_tag(
					'div',
					[
						'class' => 'ct-filter-item-inner'
					],
					blocksy_html_tag(
						'a',
						[
							'href' => esc_url($api_url),
							'rel' => 'nofollow',
							'aria-label' => $value->name,
							'data-key' => $attribute_slug,
							'data-value' => $value->term_id,
						],
						$checbox_html .
						$swatch_term_html .
							$label_html .
							$products_count
					)
				)
			);
		}

		return $list_items_html;
	}

	public function get_applied_filter_descriptor($key, $value) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$maybe_attribute = null;

		foreach ($attribute_taxonomies as $attribute) {
			if ('filter_' . $attribute->attribute_name === $key) {
				$maybe_attribute = $attribute;
				break;
			}
		}

		if (! $maybe_attribute) {
			return null;
		}

		$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
		$term = get_term_by('slug', $value, $taxonomy);

		if ($term) {
			return [
				'name' => $term->name,
				'href' => $this->get_link_url($value, [
					'to_add' => [
						'query_type_' . $attribute->attribute_name => 'or'
					]
				])
			];
		}

		return null;
	}

	public function additional_query_string_params() {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$maybe_attribute = null;

		$result = [];
		$params = FiltersUtils::get_query_params();

		foreach ($params['params'] as $key => $value) {
			foreach ($attribute_taxonomies as $attribute) {
				if ('query_type_' . $attribute->attribute_name === $key) {
					$result[] = $key;
				}
			}
		}

		return $result;
	}

	public function remove_my_filters_from_url($url, $key) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$maybe_attribute = null;

		foreach ($attribute_taxonomies as $attribute) {
			if (
				'filter_' . $attribute->attribute_name === $key
				||
				'query_type_' . $attribute->attribute_name === $key
			) {
				$maybe_attribute = $attribute;
				break;
			}
		}

		if (! $maybe_attribute) {
			return $url;
		}

		return remove_query_arg($key, $url);
	}

	public function get_attributes_counts($terms) {
		$filterer = wc_get_container()->get(Filterer::class);

		$counts_result = $this->get_terms_counts([
			'term_ids' => array_map(function ($term) {
				return $term->term_id;
			}, $terms)
		]);

		$terms_with_counts = [];

		foreach ($terms as $term) {
			$term_count = 0;

			if (isset($counts_result[$term->term_id])) {
				$term_count = intval($counts_result[$term->term_id]->term_count);
			}

			$term->count = $term_count;

			$terms_with_counts[] = $term;
		}

		return $terms_with_counts;
	}

	protected function get_terms_counts_sql($args = []) {
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

		$filterer = wc_get_container()->get(Filterer::class);

		if (! $filterer->filtering_via_lookup_table_is_active()) {
			global $wpdb;

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

		$lookup_table_name = wc_get_container()->get(
			DataRegenerator::class
		)->get_lookup_table_name();

		return "
			SELECT term_id, COUNT(DISTINCT product_or_parent_id) as term_count
			FROM {$lookup_table_name}
			WHERE (
				product_or_parent_id IN (" . implode(',', $args['product_ids']) . ")
				AND
				term_id IN (" . implode(',', $args['term_ids']) . ")
			)
			GROUP BY term_id
		";
	}

	private function get_layered_nav($query_string) {
		$layered_nav_chosen = [];

		foreach ($query_string as $key => $value) {
			if (0 !== strpos($key, 'filter_')) {
				continue;
			}

			$attribute = wc_sanitize_taxonomy_name(
				str_replace('filter_', '', $key)
			);

			$taxonomy = wc_attribute_taxonomy_name($attribute);

			$filter_terms = ! empty($value)
				? explode(',', wc_clean(wp_unslash($value)))
				: array();

			if (
				empty($filter_terms)
				||
				! taxonomy_exists($taxonomy)
				||
				! wc_attribute_taxonomy_id_by_name($attribute)
			) {
				continue;
			}

			$all_terms = [];

			foreach ($filter_terms as $term) {
				$term_obj = get_term_by('id', $term, $taxonomy);

				if (! $term_obj) {
					$term_obj = get_term_by('slug', $term, $taxonomy);
				}

				if (! $term_obj) {
					continue;
				}

				$all_terms[] = $term_obj->slug;
			}

			if (! isset($layered_nav_chosen[$taxonomy])) {
				$layered_nav_chosen[$taxonomy] = [
					'terms' => [],
					'query_type' => 'or',
				];
			}

			$layered_nav_chosen[$taxonomy]['terms'] = $all_terms;
		}

		return $layered_nav_chosen;
	}
}

