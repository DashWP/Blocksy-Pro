<?php

$class = 'ct-shortcuts-bar';

if (! isset($only_item)) {
	$only_item = null;
}

$items = blocksy_get_theme_mod('shortcuts_bar_items', [
	[
		'id' => 'home',
		'enabled' => true,
		'label' => __('Home', 'blocksy-companion'),
		'icon' => [
			'icon' => 'blc blc-home'
		]
	],

	[
		'id' => 'phone',
		'enabled' => true,
		'label' => __('Phone', 'blocksy-companion'),
		'icon' => [
			'icon' => 'blc blc-phone'
		]
	]
]);

$items_output = [];

$icons = [
	'home' => 'blc blc-home',
	'phone' => 'blc blc-phone',
	'email' => 'blc blc-email',
	'scroll_top' => 'blc blc-arrow-up-circle',
	'wishlist' => 'blc blc-heart',
	'filters_canvas' => 'blc blc-filter',
	'compare' => 'blc blc-compare',
	'cart' => 'blc blc-cart',
	'shop' => 'blc blc-shop',
	'custom_link' => ''
];

$label_defaults = [
	'home' => __('Home', 'blocksy-companion'),
	'phone' => __('Phone', 'blocksy-companion'),
	'email' => __('Email', 'blocksy-companion'),
	'scroll_top' => __('Scroll Top', 'blocksy-companion'),
	'cart' => __('Cart', 'blocksy-companion'),
	'shop' => __('Products', 'blocksy-companion'),
	'wishlist' => __('Wish List', 'blocksy-companion'),
	'filters_canvas' => __('Filters', 'blocksy-companion'),
	'compare' => __('Compare', 'blocksy-companion'),
	'custom_link' => 'Label'
];

foreach ($items as $single_item) {
	if ($only_item && $single_item['id'] !== $only_item) {
		continue;
	}

	if (isset($single_item['enabled']) && ! $single_item['enabled']) {
		continue;
	}

	if (
		! class_exists('WooCommerce')
		&&
		(
			$single_item['id'] === 'cart'
			||
			$single_item['id'] === 'shop'
			||
			$single_item['id'] === 'wishlist'
			||
			$single_item['id'] === 'compare'
			||
			$single_item['id'] === 'filters_canvas'
		)
	) {
		continue;
	}

	$shortcut_attrs = [];

	$item_i18n_id_prefix = 'shortcuts:' . $single_item['id'];

	if ($single_item['id'] === 'custom_link' && isset($single_item['__id'])) {
		$item_i18n_id_prefix = 'shortcuts:' . $single_item['__id'];
	}

	if (! isset($label_defaults[$single_item['id']])) {
		continue;
	}

	$link = '#';
	$label = blocksy_translate_dynamic(
		blocksy_akg(
			'label',
			$single_item,
			$label_defaults[$single_item['id']]
		),
		$item_i18n_id_prefix . ':label'
	);

	$icon = blc_get_icon([
		'icon_descriptor' => blocksy_akg('icon', $single_item, [
			'icon' => $icons[$single_item['id']],
		]),
		'icon_container' => false
	]);

	if (isset($single_item['link'])) {
		$link = blocksy_akg('link', $single_item, '');
	}

	if ($single_item['id'] === 'home') {
		$link = get_home_url();
	}

	if ($single_item['id'] === 'phone') {
		if (! empty($single_item['phone_number'])) {
			$link = 'tel:' . $single_item['phone_number'];
		}
	}

	if ($single_item['id'] === 'email') {
		if (! empty($single_item['email'])) {
			$link = 'mailto:' . $single_item['email'];
		}
	}

	if ($single_item['id'] === 'cart' && function_exists('wc_get_cart_url')) {
		$link = wc_get_cart_url();
	}

	if ($single_item['id'] === 'shop' && function_exists('wc_get_cart_url')) {
		$link = get_permalink(wc_get_page_id('shop'));
	}

	if (
		$single_item['id'] === 'wishlist'
		&&
		function_exists('wc_get_endpoint_url')
	) {
		$link = wc_get_endpoint_url(
			apply_filters(
				'blocksy:pro:woocommerce-extra:wish-list:slug',
				'woo-wish-list'
			),
			'',
			get_permalink(get_option('woocommerce_myaccount_page_id'))
		);

		if (
			! is_user_logged_in()
			&&
			blocksy_get_theme_mod('product_wishlist_display_for', 'logged_users') === 'all_users'
		) {
			$maybe_page_id = blocksy_get_theme_mod('woocommerce_wish_list_page');

			if (! empty($maybe_page_id)) {
				$maybe_permalink = get_permalink($maybe_page_id);

				if ($maybe_permalink) {
					$link = $maybe_permalink;
				}
			}
		}
	}

	if ($single_item['id'] === 'filters_canvas') {
		if (
			(
				! is_shop()
				&&
				! is_product_category()
				&&
				! is_product_tag()
			)
			||
			blocksy_get_theme_mod('has_woo_offcanvas_filter', 'no') === 'no'
		) {
			continue;
		}

		$link = '#';

		$ariaExpanded = blocksy_get_theme_mod(
			'filter_panel_behaviour',
			'no'
		) === 'no' ? 'false' : 'true';

		$filter_ajax_reveal = blocksy_get_theme_mod('filter_ajax_reveal', 'no');

		if (blocksy_get_theme_mod(
			'filter_panel_behaviour',
			'no'
		) === 'yes') {
			$filter_ajax_reveal = 'no';
		}

		$has_filter_ajax_reveal = $filter_ajax_reveal === 'yes';

		if (blocksy_get_theme_mod('woocommerce_filter_type', 'type-1') === 'type-2') {
			$shortcut_attrs = [
				'class' => 'ct-shortcut-toggle-filter-panel' . (! $has_filter_ajax_reveal ? ' ct-expandable-trigger' : ''),
				'data-behaviour' => 'drop-down',
				'data-target' => '#woo-filters-panel',
				'aria-expanded' => $has_filter_ajax_reveal === 'yes' ? 'false' : $ariaExpanded,
				'role' => 'button'
			];
		} else {
			$shortcut_attrs = [
				'class' => 'ct-shortcut-toggle-filter-panel ct-offcanvas-trigger',
				'data-toggle-panel' => '#woo-filters-panel'
			];
		}		
	}

	if (
		$single_item['id'] === 'compare'
		&&
		function_exists('wc_get_endpoint_url')
	) {
		$link = '#';

		$maybe_page_id = blocksy_get_theme_mod('woocommerce_compare_page');

		if (!empty($maybe_page_id)) {
			$maybe_permalink = get_permalink($maybe_page_id);

			if ($maybe_permalink) {
				$link = $maybe_permalink;
			}
		}

		if ( blocksy_get_theme_mod('compare_table_placement', 'modal') === 'modal' ) {
			$shortcut_attrs = [
				'data-behaviour' => blocksy_get_theme_mod('compare_table_placement', 'modal'),
			];
		}
	}

	if ($single_item['id'] === 'search') {
		// TODO: target search modal
	}

	$link_args = array_merge(
		[
			'href' => blocksy_translate_dynamic(
				apply_filters('wpml_permalink', do_shortcode($link)),
				$item_i18n_id_prefix . ':link'
			),
			'data-shortcut' => $single_item['id'],
			'data-label' => blocksy_get_theme_mod('shortcuts_label_position', 'bottom'),
			'aria-label' => $label
		],
		$shortcut_attrs
	);

	if (blocksy_akg('link_target', $single_item, 'no') === 'yes') {
		$link_args['target'] = '_blank';
	}

	if (blocksy_akg('link_nofollow', $single_item, 'no') === 'yes') {
		$link_args['rel'] = 'nofollow';
	}

	$item_class = blocksy_visibility_classes(
		blocksy_akg('item_visibility', $single_item, [
			'desktop' => true,
			'tablet' => true,
			'mobile' => true,
		])
	);

	if (! empty($item_class)) {
		$link_args['class'] = $item_class;
	}

	$custom_class = blocksy_akg('class', $single_item, '');

	if (! empty($custom_class)) {
		if (! isset($link_args['class'])) {
			$link_args['class'] = '';
		}

		$link_args['class'] = trim($link_args['class'] . ' ' . $custom_class);
	}

	$count_output = '';

	if ($single_item['id'] === 'cart') {
		$current_count = 0;

		if (WC()->cart) {
			$current_count = WC()->cart->get_cart_contents_count();
		}

		$count_output = blocksy_html_tag(
			'span',
			[
				'class' => 'ct-dynamic-count-cart',
				'data-count' => $current_count
			],
			$current_count
		);
	}

	if (
		$single_item['id'] === 'wishlist'
		&&
		blc_get_ext('woocommerce-extra')
		&&
		blc_get_ext('woocommerce-extra')->get_wish_list()
	) {
		$current_count = count(
			blc_get_ext('woocommerce-extra')->get_wish_list()->get_current_wish_list()
		);

		$count_output = blocksy_html_tag(
			'span',
			[
				'class' => 'ct-dynamic-count-wishlist',
				'data-count' => $current_count,
				'aria-hidden' => 'true'
			],
			$current_count
		);
	}

	if (
		$single_item['id'] === 'compare'
		&&
		blc_get_ext('woocommerce-extra')
		&&
		blc_get_ext('woocommerce-extra')->get_compare()
	) {
		$current_count = count(
			blc_get_ext('woocommerce-extra')->get_compare()->get_current_compare_list()
		);

		$count_output = blocksy_html_tag(
			'span',
			[
				'class' => 'ct-dynamic-count-compare',
				'data-count' => $current_count,
				'aria-hidden' => 'true'
			],
			$current_count
		);
	}

	if (! empty($link)) {
		$label_class = 'ct-label';

		$label_class .= ' ' . blocksy_visibility_classes(blocksy_get_theme_mod('shortcuts_label_visibility', [
			'desktop' => false,
			'tablet' => false,
			'mobile' => false,
		]));

		$additional_output = '<span class="ct-icon-container">' . $count_output . $icon . '</span>';

		$output = blocksy_html_tag(
			'a',
			$link_args,
			'<span class="' . trim($label_class) . '">' . do_shortcode($label) . '</span>' . $additional_output
		);

		if ($single_item['id'] === 'compare') {
			$output = blocksy_action_button(
				[
					'button_html_attributes' => $link_args,
					'icon' => $count_output . $icon,
					'content' => blocksy_html_tag(
						'span',
						[
							'class' => 'ct-hidden-md ct-hidden-sm',
						],
						'<span class="' . trim($label_class) . '">' . do_shortcode($label) . '</span>'
					)
				]
		 	);
		}

		if ($single_item['id'] === 'filters_canvas') {
			$output = blocksy_action_button(
				[
					'button_html_attributes' => $link_args,
					'icon' => $icon,
					'content' => blocksy_html_tag(
						'span',
						[
							'class' => 'ct-hidden-md ct-hidden-sm',
						],
						'<span class="' . trim($label_class) . '">' . do_shortcode($label) . '</span>'
					)
				]
		 	);
		}

		$items_output[] = $output;
	}
}

$items_output = apply_filters(
	'blocksy:pro:ext:shortcuts:bar-items',
	$items_output
);

if (empty($items_output)) {
	return;
}

$class .= ' ' . blocksy_visibility_classes(
	blocksy_get_theme_mod('shortcuts_bar_visibility', [
		'desktop' => true,
		'tablet' => true,
		'mobile' => true,
	])
);

if ($only_item) {
	echo implode(' ', $items_output);
	return;
}

?>

<div
	class="<?php echo esc_attr(trim($class)) ?>"
	data-type="<?php echo blocksy_get_theme_mod('shortcuts_bar_type', 'type-1') ?>"
	<?php
		if (
			is_customize_preview()
			&&
			function_exists('blocksy_attr_to_html')
		) {
			echo blocksy_attr_to_html([
				'data-shortcut' => 'border',
				'data-shortcut-location' => 'shortcuts_ext'
			]);
		}
	?>>
	<div class="ct-shortcuts-bar-items">
		<?php echo implode(' ', $items_output); ?>
	</div>
</div>
