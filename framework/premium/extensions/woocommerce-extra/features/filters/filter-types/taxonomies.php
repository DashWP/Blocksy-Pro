<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class TaxonomiesFilter extends BaseFilter {
	use QueryManager;

	public function get_filter_name() {
		if (isset($this->attributes['taxonomy'])) {
			return 'filter_' . $this->attributes['taxonomy'];
		}

		return 'filter_product_cat';
	}

	public function wp_query_arg($query_string, $query_args) {
		$layered_nav_chosen = $this->get_layered_nav($query_string);

		foreach ($layered_nav_chosen as $taxonomy => $data) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => $data['terms'],
				'operator' => 'IN',
				'include_children' => true
			);
		}

		return $query_args;
	}

	public function render() {
		$lookup_table = blc_get_ext('woocommerce-extra')
			->filters
			->lookup_table;

		if (! $lookup_table->can_use_lookup_table()) {
			$last_product_id = $lookup_table->get_last_existing_product_id();

			if (
				! $last_product_id
				||
				! current_user_can('manage_options')
			) {
				return [
					'items' => []
				];
			}

			return new \WP_Error(
				'blocksy_lookup_table',
				__('Please wait until the lookup table is generated.', 'blocksy-companion')
			);
		}

		$render_descriptor = [
			'items' => $this->filter_get_items_for_taxonomies()
		];

		if (
			$this->attributes['taxonomy'] === 'product_brands'
			||
			$this->attributes['type'] === 'brands'
		) {
			$render_descriptor['list_attr'] = [
				'style' => "--product-brand-logo-size: {$this->attributes['logoMaxW']}px;",
				'data-frame' => $this->attributes['useFrame'] ? 'yes' : 'no',
			];
		}

		return $render_descriptor;
	}

	public function get_terms_for_all_products() {
		$terms = [];

		$terms_counts = $this->get_terms_counts();

		if (! empty($terms_counts)) {
			$terms = $this->get_terms_for($terms_counts);
		}

		return $terms;
	}

	private function get_terms_for($terms_counts, $args = []) {
		$args = wp_parse_args($args, [
			'is_hierarchical' => true
		]);

		$all_term_ids = array_keys($terms_counts);

		$root_id = 0;

		$is_taxonomy_page = $this->is_taxonomy_page();

		if ($is_taxonomy_page) {
			$queried_object = get_queried_object();

			if ($queried_object->taxonomy === $this->attributes['taxonomy']) {
				$root_id = $queried_object->term_id;
			}
		}

		if ($args['is_hierarchical']) {
			$term_hierarchy = _get_term_hierarchy($this->attributes['taxonomy']);

			$hierarchy_children_first = [];

			foreach ($term_hierarchy as $parent_id => $children) {
				foreach ($children as $child) {
					$hierarchy_children_first[$child] = $parent_id;
				}
			}

			$root_terms = [];

			if ($root_id === 0) {
				foreach ($all_term_ids as $term_id) {
					if (! isset($hierarchy_children_first[$term_id])) {
						$root_terms[] = $term_id;
					}
				}
			}

			if ($root_id !== 0 && isset($term_hierarchy[$root_id])) {
				$root_terms = $term_hierarchy[$root_id];
			}
		}

		if (! $args['is_hierarchical']) {
			if ($root_id !== 0) {
				$root_terms = array_diff(
					$all_term_ids,
					[$root_id]
				);
			}

			if ($root_id === 0) {
				$root_terms = $all_term_ids;
			}
		}

		$terms_structure_with_ids = [];

		foreach ($root_terms as $root_term) {
			if (in_array($root_term, $this->attributes['taxonomy_not_in'])) {
				continue;
			}

			$children = [];

			if ($args['is_hierarchical']) {
				$children = $this->find_children($root_term, $term_hierarchy);
			}

			$terms_structure_with_ids[] = [
				'term_id' => $root_term,
				'children' => $children
			];
		}

		$all_terms = get_terms([
			'taxonomy' => $this->attributes['taxonomy'],
			'include' => $all_term_ids
		]);

		$terms_by_id = [];

		foreach ($all_terms as $term) {
			$term->count = $terms_counts[$term->term_id]->term_count;
			$terms_by_id[$term->term_id] = $term;
		}

		return $this->transform_terms_structure_with_ids_into_real(
			$terms_structure_with_ids,
			$terms_by_id
		);
	}

	private function find_children($term_id, $terms_hierarchy) {
		if (! isset($terms_hierarchy[$term_id])) {
			return [];
		}

		$children = [];

		foreach ($terms_hierarchy[$term_id] as $child_id) {
			if (in_array($child_id, $this->attributes['taxonomy_not_in'])) {
				continue;
			}

			$child = [
				'term_id' => $child_id,
				'children' => $this->find_children($child_id, $terms_hierarchy)
			];

			$children[] = $child;
		}

		return $children;
	}

	private function transform_terms_structure_with_ids_into_real(
		$terms_structure_with_ids,
		$terms_by_id
	) {
		$terms_structure = [];

		foreach ($terms_structure_with_ids as $term) {
			if (! isset($terms_by_id[$term['term_id']])) {
				continue;
			}

			$term_obj = $terms_by_id[$term['term_id']];

			unset($terms_by_id[$term['term_id']]);

			$term_obj->children = $this->transform_terms_structure_with_ids_into_real(
				$term['children'],
				$terms_by_id
			);

			// Expose brand image for block preview.
			if (
				$this->ignore_current_query
				&&
				$this->attributes['taxonomy'] === 'product_brands'
			) {
				$term_atts = get_term_meta(
					$term['term_id'],
					'blocksy_taxonomy_meta_options'
				);

				if (empty($term_atts)) {
					$term_atts = [[]];
				}

				$term_atts = $term_atts[0];

				$maybe_image = blocksy_akg('icon_image', $term_atts, '');

				$term_obj->logo = $maybe_image;
			}

			$terms_structure[] = $term_obj;
		}

		return $terms_structure;
	}

	private function filter_get_items_for_taxonomies() {
		$is_hierarchical = false;

		if ($this->attributes['viewType'] === 'list') {
			$is_hierarchical = $this->attributes['hierarchical'];
		}

		$terms_counts = $this->get_terms_counts([
			'exclude' => $this->attributes['taxonomy_not_in']
		]);

		if (empty($terms_counts)) {
			return [];
		}

		$terms = $this->get_terms_for($terms_counts, [
			'is_hierarchical' => $is_hierarchical
		]);

		usort($terms, function ($a, $b) {
			return strcmp($a->name, $b->name);
		});

		$list_items_html = [];

		foreach ($terms as $key => $value) {
			$list_items_html[] = self::get_taxonomy_item($value);
		}

		return $list_items_html;
	}

	private function get_item_label($term, $is_expandable, $products_count) {
		$label_text = $term->name;

		$label_html = '';

		if (
			! $this->attributes['showLabel']
			&&
			$this->attributes['taxonomy'] === 'product_brands'
		) {
			$label_text = '';
		}

		if (! empty($label_text)) {
			$label_html = blocksy_html_tag(
				'span',
				['class' => 'ct-filter-label'],
				$label_text
			);
		}

		if (
			$is_expandable
			&&
			$this->attributes['showCounters']
		) {
			$label_html = blocksy_html_tag(
				'span',
				['class' => 'ct-filter-label'],
				$label_text . $products_count
			);
		}

		return $label_html;
	}

	private function get_taxonomy_item($term) {
		$is_hierarchical = false;

		if ($this->attributes['viewType'] === 'list') {
			$is_hierarchical = $this->attributes['hierarchical'];
		}

		$is_expandable = $is_hierarchical ? $this->attributes['expandable'] : false;

		$api_url = $this->get_link_url(
			$term->term_id,
			[
				'is_multiple' => $this->attributes['multipleFilters']
			]
		);

		$brand_image = '';

		$term_atts = get_term_meta(
			$term->term_id,
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
			&&
			function_exists('blocksy_media')
			&&
			$this->attributes['showItemsRendered']
		) {
			$attachment_id = $maybe_image['attachment_id'];

			$inner_content = $this->attributes['showTooltips'] ? blocksy_html_tag(
				'span',
				[
					'class' => 'ct-tooltip'
				],
				$term->name
			) : '';

			$brand_image = blocksy_html_tag(
				'div',
				[
					'class' => 'ct-product-brand'
				],
				blocksy_media([
					'attachment_id' => $maybe_image['attachment_id'],
					'size' => 'full',
					'ratio' => $this->attributes['aspectRatio'],
				]) .
				$inner_content
			);
		}

		$checbox_html = '';

		if ($this->attributes['showCheckbox']) {
			$checkox_attr = [
				'type' => 'checkbox',
				'class' => 'ct-checkbox',
				'tabindex' => '-1',
				'name' => $this->attributes['taxonomy'] . '_' . $term->term_id,
				'aria-label' => $term->name,
			];

			if ($this->is_filter_active($term->term_id)) {
				$checkox_attr['checked'] = 'checked';
			}

			$checbox_html = blocksy_html_tag('input', $checkox_attr);
		}

		$products_count = $this->format_products_count([
			'count' => $term->count,
			'with_wrap' => $is_expandable && $this->attributes['showCounters']
		]);

		if (! $products_count) {
			return '';
		}

		$label_html = $this->get_item_label(
			$term,
			$is_expandable,
			$products_count
		);

		if (! $this->attributes['showCounters']) {
			$products_count = '';
		}

		$childrens_html = '';
		$expandable_triger = '';

		if ($is_hierarchical && ! empty($term->children)) {
			$childrens_items_html = [];

			$term_children = $term->children;

			usort($term_children, function ($a, $b) {
				return strcmp($a->name, $b->name);
			});

			foreach ($term_children as $key => $value) {
				$childrens_items_html[] = self::get_taxonomy_item(
					$value,
					$this->attributes
				);
			}

			$childrens_html = blocksy_html_tag(
				'ul',
				[
					'class' => 'ct-filter-children',
					'aria-hidden' => $this->attributes['defaultExpanded'] ? 'false' : 'true',
					'data-behaviour' => $is_expandable ? 'drop-down' : 'list',
				],
				implode('', $childrens_items_html)
			);

			if ($is_expandable) {
				$expandable_triger = blocksy_html_tag(
					'button',
					[
						'class' => 'ct-expandable-trigger',
						'aria-expanded' => $this->attributes['defaultExpanded']
							? 'true'
							: 'false',
						'aria-label' => $this->attributes['defaultExpanded']
							? __('Collapse', 'blocksy-companion')
							: __('Expand', 'blocksy-companion'),
						'data-icon' => 'arrow'
					],
					"<svg class='ct-icon' width='10' height='10' viewBox='0 0 25 25'><path d='M.207 17.829 12.511 5.525l1.768 1.768L1.975 19.596z'/><path d='m10.721 7.243 1.768-1.768L24.793 17.78l-1.768 1.767z'/></svg>"
				);
			}
		}

		if ($this->attributes['showCounters'] && empty($products_count)) {
			return '';
		}

		if ($is_expandable && $this->attributes['showCounters']) {
			$products_count = '';
		}

		$item_classes = ['ct-filter-item'];

		if ($this->is_filter_active($term->term_id)) {
			$item_classes[] = 'active';
		}

		return blocksy_html_tag(
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
						'aria-label' => $term->name,
						'data-key' => $this->attributes['taxonomy'],
						'data-value' => $term->term_id,
					],
					$checbox_html .
					$brand_image .
					$label_html .
					$products_count
				) . $expandable_triger
			) . $childrens_html
		);
	}

	protected function get_terms_counts_sql($args = []) {
		global $wpdb;

		$args = wp_parse_args($args, [
			'product_ids' => [],
			'term_ids' => []
		]);

		$lookup_table = blc_get_ext('woocommerce-extra')
			->filters
			->lookup_table;

		if (! $lookup_table->check_lookup_table_exists()) {
			return '';
		}

		if (empty($args['product_ids'])) {
			return '';
		}

		$template = "
			SELECT term_id, COUNT(DISTINCT product_id) as term_count
			FROM {$lookup_table->get_table_name()}
			WHERE (
				product_id IN (" . implode(',', $args['product_ids']) . ")
				AND
				taxonomy = %s
			)
			GROUP BY term_id
		";

		return $wpdb->prepare($template, $this->attributes['taxonomy']);
	}

	private function get_layered_nav($query_string) {
		$layered_nav_chosen = [];

		foreach ($query_string as $key => $value) {
			if (0 !== strpos($key, 'filter_')) {
				continue;
			}

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

			$taxonomy = wc_sanitize_taxonomy_name(
				str_replace('filter_', '', $key)
			);

			$filter_terms = ! empty($value)
				? explode(',', wc_clean(wp_unslash($value)))
				: array();

			if (
				empty($filter_terms)
				||
				! taxonomy_exists($taxonomy)
				||
				! in_array($taxonomy, $all_taxonomies)
			) {
				continue;
			}

			$all_terms = [];

			foreach ($filter_terms as $term) {
				$term_obj = get_term_by('id', $term, $taxonomy);

				if (! $term_obj) {
					$term_obj = get_term_by('slug', $term, $taxonomy);
				}

				if ($term_obj) {
					$all_terms[] = $term_obj->slug;
				}
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
