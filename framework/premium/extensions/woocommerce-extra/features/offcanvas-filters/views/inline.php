<?php

$ariaHidden = blocksy_get_theme_mod(
	'filter_panel_behaviour',
	'no'
) === 'no' ? 'true' : 'false';

if (! woocommerce_products_will_display()) {
	$ariaHidden = 'true';
}

$content = '';

$filter_source = isset($_GET['filter_source']) ? $_GET['filter_source'] : blocksy_get_theme_mod('filter_source', 'sidebar-woocommerce-offcanvas-filters');

if (
	! $has_filter_ajax_reveal
	||
	(
		$has_filter_ajax_reveal
		&&
		blc_is_xhr()
	)
) {
	ob_start();
	dynamic_sidebar($filter_source);
	$content = ob_get_clean();

	ob_start();
	do_action('blocksy:pro:woo-extra:inline-filters:top');
	$content = ob_get_clean() . $content;

	ob_start();
	do_action('blocksy:pro:woo-extra:inline-filters:bottom');
	$content = $content . ob_get_clean();

	$without_container = blocksy_html_tag(
		'div',
		[
			'class' => 'ct-filter-content',
		],
		$content
	);

	$content = $without_container;
}

echo blocksy_html_tag(
	'div',
	[
		'id' => 'woo-filters-panel',
		'data-behaviour' => 'drop-down',
		'data-height' => blocksy_get_theme_mod(
			'filter_panel_height_type',
			'auto'
		),
		'aria-hidden' => $ariaHidden,
	],
	$content
);

