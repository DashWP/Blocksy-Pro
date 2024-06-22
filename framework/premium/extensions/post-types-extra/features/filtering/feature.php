<?php

namespace Blocksy\Extensions\PostTypesExtra;

require_once dirname(__FILE__) . '/helpers.php';

class Filtering {
	public function __construct() {
		add_filter(
			'blocksy_posts_home_page_elements_end',
			function ($options, $prefix, $post_type) {
				$options[$prefix . '_has_archive_filtering'] = blocksy_get_options(
					dirname(__FILE__) . '/customizer.php',
					[
						'prefix' => $prefix,
						'post_type' => $post_type
					], false
				);

				return $options;
			},
			10, 3
		);

		add_action('blocksy:global-dynamic-css:enqueue', function ($args) {
			blocksy_theme_get_dynamic_styles(array_merge([
				'path' => dirname(__FILE__) . '/global.php',
				'chunk' => 'global',
				'prefixes' => blocksy_manager()->screen->get_archive_prefixes([
					'has_categories' => true,
					'has_author' => false,
					'has_search' => false
				])
			], $args));
		}, 10, 3);

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			$prefix = blocksy_manager()->screen->get_prefix([
				'allowed_prefixes' => [
					'blog'
				],
				'default_prefix' => 'blog'
			]);

			if (blocksy_get_theme_mod($prefix . '_filter_behavior', 'ajax') !== 'ajax') {
				return $chunks;
			}

			$chunks[] = [
				'id' => 'blocksy_adv_cpt_filtering',
				'selector' => '.ct-dynamic-filter a',
				'trigger' => 'click',
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/post-types-extra/static/bundle/filtering.js'
				),
			];

			$chunks[] = [
				'id' => 'blocksy_adv_cpt_filtering',
				'selector' => '.ct-dynamic-filter, .ct-dynamic-filter + .entries',
				'trigger' => 'hover',
				'skipOnTouchDevices' => true,
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/post-types-extra/static/bundle/filtering.js'
				),
			];


			return $chunks;
		});

		add_action(
			'blocksy:loop:before',
			function () {
				if (
					! is_tax()
					&&
					! is_category()
					&&
					! is_tag()
					&&
					! is_home()
					&&
					! is_post_type_archive()
				) {
					return;
				}

				blc_cpt_extra_filtering_output();
			}
		);

		add_action('pre_get_posts', function ($query) {
			if (! $query->is_main_query()) {
				return;
			}

			if (
				! is_tax()
				&&
				! is_category()
				&&
				! is_tag()
			) {
				return;
			}

			if (! function_exists('blocksy_manager')) {
				return;
			}

			$post_type = $query->get('post_type');

			if (is_tag() || is_category()) {
				$post_type = 'post';
			}

			$prefix = blocksy_manager()->screen->get_prefix([
				'allowed_prefixes' => [
					'blog'
				],
				'default_prefix' => 'blog'
			]);

			$maybe_current_tax = null;

			if (isset($_GET['blocksy_term_id'])) {
				$maybe_current_tax = $_GET['blocksy_term_id'];
			}

			if (! $maybe_current_tax) {
				return;
			}

			$maybe_tax = blocksy_get_theme_mod(
				$prefix . '_filter_source',
				blocksy_maybe_get_matching_taxonomy($post_type)
			);

			$current_tax_query = [
				'taxonomy' => $maybe_tax,
				'field' => 'term_id',
				'terms' => [$maybe_current_tax]
			];

			$query->tax_query->queries[] = $current_tax_query;

			$query->set('tax_query', $query->tax_query);
			$query->query_vars['tax_query'] = $query->tax_query->queries;
		});
	}
}

