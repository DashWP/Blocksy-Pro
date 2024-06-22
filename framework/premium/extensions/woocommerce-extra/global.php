<?php

if (! function_exists('is_woocommerce')) {
	return;
}

// Floating bar
$position = blocksy_expand_responsive_value(blocksy_get_theme_mod('floating_bar_position', 'top'));

$visibility = blocksy_expand_responsive_value(
	blocksy_get_theme_mod(
		'floatingBarVisibility',
		[
			'desktop' => true,
			'tablet' => true,
			'mobile' => true,
		]
	)
);

$floating_bar_height = [
	'desktop' => '70',
	'tablet' => '70',
	'mobile' => '70'
];

if (! $visibility['desktop']) {
	$floating_bar_height['desktop'] = '0';
}

if (! $visibility['tablet']) {
	$floating_bar_height['tablet'] = '0';
}

if (! $visibility['mobile']) {
	$floating_bar_height['mobile'] = '0';
}

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-drawer-canvas[data-floating-bar]',
	'variableName' => 'floating-bar-height',
	'value' => $floating_bar_height
]);

if (
	$position['desktop'] !== 'top'
	||
	$position['tablet'] !== 'top'
	||
	$position['mobile'] !== 'top'
) {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-floating-bar',
		'variableName' => 'top-position-override',
		'value' => [
			'desktop' => $position['desktop'] === 'top' ? 'var(--top-position)' : 'var(--false)',
			'tablet' => $position['tablet'] === 'top' ? 'var(--top-position)' : 'var(--false)',
			'mobile' => $position['mobile'] === 'top' ? 'var(--top-position)' : 'var(--false)'
		],
		'unit' => '',
	]);

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-floating-bar',
		'variableName' => 'translate-offset',
		'value' => [
			'desktop' => $position['desktop'] === 'top' ? '-70px' : '70px',
			'tablet' => $position['tablet'] === 'top' ? '-70px' : '70px',
			'mobile' => $position['mobile'] === 'top' ? '-70px' : '70px'
		],
		'unit' => '',
	]);
}

blocksy_output_colors([
	'value' => blocksy_get_theme_mod('floatingBarFontColor'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'responsive' => true,
	'variables' => [
		'default' => [
			'selector' => '.ct-floating-bar .product-title, .ct-floating-bar .price',
			'variable' => 'theme-text-color'
		],
	],
]);

blocksy_output_background_css([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'responsive' => true,
	'selector' => '.ct-floating-bar',
	'value' => blocksy_get_theme_mod('floatingBarBackground',
		blocksy_background_default_value([
			'backgroundColor' => [
				'default' => [
					'color' => 'var(--theme-palette-color-8)'
				],
			],
		])
	)
]);

blocksy_output_box_shadow([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-floating-bar',
	'should_skip_output' => false,
	'value' => blocksy_get_theme_mod(
		'floatingBarShadow',
		blocksy_box_shadow_value([
			'enable' => true,
			'h_offset' => 0,
			'v_offset' => 10,
			'blur' => 20,
			'spread' => 0,
			'inset' => false,
			'color' => [
				'color' => 'rgba(44,62,80,0.15)',
			],
		])
	),
	'responsive' => true
]);


// filter canvas
blocksy_output_font_css([
	'font_value' => blocksy_get_theme_mod( 'filter_panel_widgets_font',
		blocksy_typography_default_values([
			// 'size' => '18px',
		])
	),
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '#woo-filters-panel .ct-widget > *:not(.widget-title)',
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('filter_panel_widgets_font_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'link_initial' => [ 'color' => 'var(--theme-text-color)' ],
		'link_hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'variables' => [
		'default' => [
			'selector' => '#woo-filters-panel .ct-sidebar > *',
			'variable' => 'theme-text-color'
		],

		'link_initial' => [
			'selector' => '#woo-filters-panel .ct-sidebar',
			'variable' => 'theme-link-initial-color'
		],

		'link_hover' => [
			'selector' => '#woo-filters-panel .ct-sidebar',
			'variable' => 'theme-link-hover-color'
		],
	],
	'responsive' => true
]);


$vertical_alignment = blocksy_get_theme_mod( 'filter_panel_content_vertical_alignment', 'flex-start' );

if ($vertical_alignment !== 'flex-start') {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#woo-filters-panel',
		'variableName' => 'vertical-alignment',
		'unit' => '',
		'value' => $vertical_alignment,
	]);
}


$woocommerce_filter_type = blocksy_get_theme_mod( 'woocommerce_filter_type', 'type-1' );

// filter type - off-canvas
if ($woocommerce_filter_type === 'type-1') {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#woo-filters-panel[data-behaviour*="side"]',
		'variableName' => 'side-panel-width',
		'responsive' => true,
		'unit' => '',
		'value' => blocksy_get_theme_mod('filter_panel_width', [
			'desktop' => '500px',
			'tablet' => '65vw',
			'mobile' => '90vw',
		])
	]);


	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '#woo-filters-panel[data-behaviour*="side"] .ct-panel-inner',
		'value' => blocksy_get_theme_mod('filter_panel_background',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => '#ffffff'
					],
				],
			])
		)
	]);


	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '#woo-filters-panel[data-behaviour*="side"]',
		'value' => blocksy_get_theme_mod('filter_panel_backgrop',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'rgba(18, 21, 25, 0.6)'
					],
				],
			])
		)
	]);


	$close_button_type = blocksy_get_theme_mod('filter_panel_close_button_type', 'type-1');

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('filter_panel_close_button_color'),
		'default' => [
			'default' => [ 'color' => 'rgba(0, 0, 0, 0.5)' ],
			'hover' => [ 'color' => 'rgba(0, 0, 0, 0.8)' ],
		],
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'variables' => [
			'default' => [
				'selector' => '#woo-filters-panel .ct-toggle-close',
				'variable' => 'theme-icon-color'
			],

			'hover' => [
				'selector' => '#woo-filters-panel .ct-toggle-close:hover',
				'variable' => 'theme-icon-color'
			]
		],
	]);


	if ($close_button_type === 'type-2') {
		blocksy_output_colors([
			'value' => blocksy_get_theme_mod('filter_panel_close_button_border_color'),
			'default' => [
				'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
				'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			],
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'responsive' => true,
			'variables' => [
				'default' => [
					'selector' => '#woo-filters-panel .ct-toggle-close[data-type="type-2"]',
					'variable' => 'toggle-button-border-color'
				],

				'hover' => [
					'selector' => '#woo-filters-panel .ct-toggle-close[data-type="type-2"]:hover',
					'variable' => 'toggle-button-border-color'
				]
			],
		]);
	}


	if ($close_button_type === 'type-3') {
		blocksy_output_colors([
			'value' => blocksy_get_theme_mod('filter_panel_close_button_shape_color'),
			'default' => [
				'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
				'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			],
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'responsive' => true,
			'variables' => [
				'default' => [
					'selector' => '#woo-filters-panel .ct-toggle-close[data-type="type-3"]',
					'variable' => 'toggle-button-background'
				],

				'hover' => [
					'selector' => '#woo-filters-panel .ct-toggle-close[data-type="type-3"]:hover',
					'variable' => 'toggle-button-background'
				]
			],
		]);
	}


	if ($close_button_type !== 'type-1') {
		$filter_panel_close_button_border_radius = blocksy_get_theme_mod( 'filter_panel_close_button_border_radius', 5 );

		if ($filter_panel_close_button_border_radius !== 5) {
			$css->put(
				'#woo-filters-panel .ct-toggle-close',
				'--toggle-button-radius: ' . $filter_panel_close_button_border_radius . 'px'
			);
		}
	}


	$filter_panel_close_button_icon_size = blocksy_get_theme_mod( 'filter_panel_close_button_icon_size', 12 );

	if ($filter_panel_close_button_icon_size !== 12) {
		$css->put(
			'#woo-filters-panel .ct-toggle-close',
			'--theme-icon-size: ' . $filter_panel_close_button_icon_size . 'px'
		);
	}


	blocksy_output_box_shadow([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#woo-filters-panel[data-behaviour*="side"]',
		'value' => blocksy_get_theme_mod('filter_panel_shadow', blocksy_box_shadow_value([
			'enable' => true,
			'h_offset' => 0,
			'v_offset' => 0,
			'blur' => 70,
			'spread' => 0,
			'inset' => false,
			'color' => [
				'color' => 'rgba(0, 0, 0, 0.35)',
			],
		])),
		'responsive' => true
	]);

	$panel_widgets_spacing = blocksy_get_theme_mod( 'panel_widgets_spacing', 60 );

	if ($panel_widgets_spacing !== 60) {
		blocksy_output_responsive([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#woo-filters-panel .ct-sidebar',
			'variableName' => 'sidebar-widgets-spacing',
			'value' => $panel_widgets_spacing,
		]);
	}
}


// filter type - drop-down
if ($woocommerce_filter_type === 'type-2') {

	$filter_panel_height_type = blocksy_get_theme_mod( 'filter_panel_height_type', 'auto' );

	if ( $filter_panel_height_type === 'custom') {
		blocksy_output_responsive([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#woo-filters-panel[data-behaviour="drop-down"]',
			'variableName' => 'filter-panel-height',
			'responsive' => true,
			'unit' => '',
			'value' => blocksy_get_theme_mod('filter_panel_height', [
				'desktop' => '250px',
				'tablet' => '250px',
				'mobile' => '250px',
			])
		]);
	}


	$filter_panel_columns = blocksy_expand_responsive_value(blocksy_get_theme_mod(
		'filter_panel_columns',
		[
			'desktop' => 4,
			'tablet' => 2,
			'mobile' => 1
		]
	));

	$columns_for_output = [
		'desktop' => 'repeat(' . $filter_panel_columns['desktop'] . ', 1fr)',
		'tablet' => 'repeat(' . $filter_panel_columns['tablet'] . ', 1fr)',
		'mobile' => 'repeat(' . $filter_panel_columns['mobile'] . ', 1fr)'
	];

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#woo-filters-panel[data-behaviour="drop-down"]',
		'variableName' => 'grid-template-columns',
		'value' => $columns_for_output,
		'unit' => ''
	]);
}


// single product share box
blocksy_output_colors([
	'value' => blocksy_get_theme_mod('product_share_items_icon_color', []),
	'default' => [
		'default' => [ 'color' => 'var(--theme-text-color)' ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => blocksy_prefix_selector('.ct-share-box', 'product'),
			'variable' => 'theme-icon-color'
		],
		'hover' => [
			'selector' => blocksy_prefix_selector('.ct-share-box', 'product'),
			'variable' => 'theme-icon-hover-color'
		],
	],
]);


// Single product type 2
$product_view_stacked_columns = blocksy_get_theme_mod('product_view_stacked_columns', 2);

if ($product_view_stacked_columns !== 2) {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-stacked-gallery .ct-stacked-gallery-container',
		'variableName' => 'columns',
		'value' => $product_view_stacked_columns,
		'unit' => ''
	]);
}


$product_view_columns_top = blocksy_get_theme_mod('product_view_columns_top', 3);

if ($product_view_columns_top !== 3) {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-columns-top-gallery .woocommerce-product-gallery',
		'variableName' => 'columns',
		'value' => $product_view_columns_top,
		'unit' => ''
	]);
}


// variation swatches
$default_product_layout = blocksy_get_woo_archive_layout_defaults();

$render_layout_config = blocksy_get_theme_mod(
	'woo_card_layout',
	$default_product_layout
);

if (function_exists('blocksy_normalize_layout')) {
	$render_layout_config = blocksy_normalize_layout(
		$render_layout_config,
		$default_product_layout
	);
}

$has_swatches = false;

foreach ($render_layout_config as $layer) {
	if (! $layer['enabled']) {
		continue;
	}

	if ($layer['id'] === 'product_swatches') {
		$has_swatches = true;
	}
}

if ($has_swatches) {
	$archive_color_swatch_size = blocksy_get_theme_mod('archive_color_swatch_size', 25);

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-card-variation-swatches [data-swatches-type="color"]',
		'variableName' => 'swatch-size',
		'value' => $archive_color_swatch_size
	]);


	$archive_image_swatch_size = blocksy_get_theme_mod('archive_image_swatch_size', 25);

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-card-variation-swatches [data-swatches-type="image"]',
		'variableName' => 'swatch-size',
		'value' => $archive_image_swatch_size
	]);


	$archive_button_swatch_size = blocksy_get_theme_mod('archive_button_swatch_size', 25);

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-card-variation-swatches [data-swatches-type="button"]',
		'variableName' => 'swatch-size',
		'value' => $archive_button_swatch_size
	]);

	$archive_mixed_swatch_size = blocksy_get_theme_mod('archive_mixed_swatch_size', 25);

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-card-variation-swatches [data-swatches-type="mixed"]',
		'variableName' => 'swatch-size',
		'value' => $archive_mixed_swatch_size
	]);
}


$single_color_swatch_size = blocksy_get_theme_mod('single_color_swatch_size', 30);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.variations_form [data-swatches-type="color"]',
	'variableName' => 'swatch-size',
	'value' => $single_color_swatch_size
]);


$filter_widget_color_swatch_size = blocksy_get_theme_mod('filter_widget_color_swatch_size', 25);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-filter-widget[data-swatches-type="color"]',
	'variableName' => 'swatch-size',
	'value' => $filter_widget_color_swatch_size
]);


$single_image_swatch_size = blocksy_get_theme_mod('single_image_swatch_size', 35);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.variations_form [data-swatches-type="image"]',
	'variableName' => 'swatch-size',
	'value' => $single_image_swatch_size
]);


$filter_widget_image_swatch_size = blocksy_get_theme_mod('filter_widget_image_swatch_size', 35);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-filter-widget[data-swatches-type="image"]',
	'variableName' => 'swatch-size',
	'value' => $filter_widget_image_swatch_size
]);


$single_button_swatch_size = blocksy_get_theme_mod('single_button_swatch_size', 35);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.variations_form [data-swatches-type="button"]',
	'variableName' => 'swatch-size',
	'value' => $single_button_swatch_size
]);


$filter_widget_button_swatch_size = blocksy_get_theme_mod('filter_widget_button_swatch_size', 30);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-filter-widget[data-swatches-type="button"]',
	'variableName' => 'swatch-size',
	'value' => $filter_widget_button_swatch_size
]);


$single_mixed_swatch_size = blocksy_get_theme_mod('single_mixed_swatch_size', 30);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.variations_form [data-swatches-type="mixed"]',
	'variableName' => 'swatch-size',
	'value' => $single_mixed_swatch_size
]);


$filter_widget_mixed_swatch_size = blocksy_get_theme_mod('filter_widget_mixed_swatch_size', 25);

blocksy_output_responsive([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => '.ct-filter-widget[data-swatches-type="mixed"]',
	'variableName' => 'swatch-size',
	'value' => $filter_widget_mixed_swatch_size
]);



blocksy_output_colors([
	'value' => blocksy_get_theme_mod('color_swatch_border_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => 'rgba(0, 0, 0, 0.2)' ],
		'active' => [ 'color' => 'rgba(0, 0, 0, 0.2)' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="color"] .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="color"] > *:hover .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="color"] > *.active .ct-swatch',
			'variable' => 'swatch-border-color'
		]
	],
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('image_swatch_border_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => 'var(--theme-palette-color-1)' ],
		'active' => [ 'color' => 'var(--theme-palette-color-1)' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="image"] .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="image"] > *:hover .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="image"] > *.active .ct-swatch',
			'variable' => 'swatch-border-color'
		]
	],
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('button_swatch_text_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'active' => [ 'color' => '#ffffff' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="button"] .ct-swatch',
			'variable' => 'swatch-button-text-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="button"] > *:hover .ct-swatch',
			'variable' => 'swatch-button-text-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="button"] > *.active .ct-swatch',
			'variable' => 'swatch-button-text-color'
		]
	],
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('button_swatch_border_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => 'var(--theme-palette-color-1)' ],
		'active' => [ 'color' => 'var(--theme-palette-color-1)' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="button"] .ct-swatch',
			'variable' => 'swatch-button-border-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="button"] > *:hover .ct-swatch',
			'variable' => 'swatch-button-border-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="button"] > *.active .ct-swatch',
			'variable' => 'swatch-button-border-color'
		]
	],
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('button_swatch_background_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'active' => [ 'color' => 'var(--theme-palette-color-1)' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="button"] .ct-swatch',
			'variable' => 'swatch-button-background-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="button"] > *:hover .ct-swatch',
			'variable' => 'swatch-button-background-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="button"] > *.active .ct-swatch',
			'variable' => 'swatch-button-background-color'
		]
	],
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod('mixed_swatch_border_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => 'rgba(0, 0, 0, 0.2)' ],
		'active' => [ 'color' => 'rgba(0, 0, 0, 0.2)' ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => '[data-swatches-type="mixed"] .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'hover' => [
			'selector' => '[data-swatches-type="mixed"] > *:hover .ct-swatch',
			'variable' => 'swatch-border-color'
		],

		'active' => [
			'selector' => '[data-swatches-type="mixed"] > *.active .ct-swatch',
			'variable' => 'swatch-border-color'
		]
	],
]);


// shipping progress bar
blocksy_output_colors([
	'value' => blocksy_get_theme_mod('shipping_progress_bar_color'),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'active' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	// 'responsive' => true,
	'variables' => [
		'default' => [
			'selector' => '[class*="ct-shipping-progress"]',
			'variable' => 'shipping-progress-bar-initial-color'
		],
		'active' => [
			'selector' => '[class*="ct-shipping-progress"]',
			'variable' => 'shipping-progress-bar-active-color'
		],
	],
]);


// new badge
blocksy_output_colors([
	'value' => blocksy_get_theme_mod('newBadgeColor'),
	'default' => [
		'text' => [ 'color' => '#ffffff' ],
		'background' => [ 'color' => '#35a236' ],
	],
	'css' => $css,
	'variables' => [
		'text' => [
			'selector' => '.ct-woo-badge-new',
			'variable' => 'badge-text-color'
		],

		'background' => [
			'selector' => '.ct-woo-badge-new',
			'variable' => 'badge-background-color'
		],
	],
]);


// featured badge
blocksy_output_colors([
	'value' => blocksy_get_theme_mod('featuredBadgeColor'),
	'default' => [
		'text' => [ 'color' => '#ffffff' ],
		'background' => [ 'color' => '#de283f' ],
	],
	'css' => $css,
	'variables' => [
		'text' => [
			'selector' => '.ct-woo-badge-featured',
			'variable' => 'badge-text-color'
		],

		'background' => [
			'selector' => '.ct-woo-badge-featured',
			'variable' => 'badge-background-color'
		],
	],
]);


// quick view
$storage = new \Blocksy\Extensions\WoocommerceExtra\Storage();
$has_quick_view = $storage->get_settings()['features']['quick-view'];

if ($has_quick_view) {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card',
		'variableName' => 'theme-normal-container-max-width',
		'value' => blocksy_get_theme_mod('woocommerce_quick_view_width', 1050),
		'unit' => 'px'
	]);

	blocksy_output_font_css([
		'font_value' => blocksy_get_theme_mod(
			'quickViewProductTitleFont',
			blocksy_typography_default_values([
				// 'size' => '30px',
			])
		),
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card .product_title'
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_title_color'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .product_title',
				'variable' => 'theme-heading-color'
			],
		],
	]);

	blocksy_output_font_css([
		'font_value' => blocksy_get_theme_mod(
			'quickViewProductPriceFont',
			blocksy_typography_default_values([
				// 'size' => '30px',
			])
		),
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card .entry-summary .price'
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_price_color'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .price',
				'variable' => 'theme-text-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_description_color'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .woocommerce-product-details__short-description',
				'variable' => 'theme-text-color'
			],
		],
	]);



	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_add_to_cart_text'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .single_add_to_cart_button',
				'variable' => 'theme-button-text-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .single_add_to_cart_button',
				'variable' => 'theme-button-text-hover-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_add_to_cart_background'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .single_add_to_cart_button',
				'variable' => 'theme-button-background-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .single_add_to_cart_button',
				'variable' => 'theme-button-background-hover-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_view_cart_button_text'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-cart-actions .added_to_cart',
				'variable' => 'theme-button-text-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-cart-actions .added_to_cart',
				'variable' => 'theme-button-text-hover-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_view_cart_button_background'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-cart-actions .added_to_cart',
				'variable' => 'theme-button-background-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-cart-actions .added_to_cart',
				'variable' => 'theme-button-background-hover-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_product_page_button_text'),
		'default' => [
			'default' => [ 'color' => 'var(--theme-text-color)' ],
			'hover' => [ 'color' => 'var(--theme-text-color)' ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-quick-more',
				'variable' => 'theme-button-text-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-quick-more',
				'variable' => 'theme-button-text-hover-color'
			],
		],
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('quick_view_product_page_button_background'),
		'default' => [
			'default' => [ 'color' => 'rgba(224,229,235,0.6)' ],
			'hover' => [ 'color' => 'rgba(224,229,235,1)' ],
		],
		'css' => $css,
		'variables' => [
			'default' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-quick-more',
				'variable' => 'theme-button-background-initial-color'
			],

			'hover' => [
				'selector' => '.ct-quick-view-card .entry-summary .ct-quick-more',
				'variable' => 'theme-button-background-hover-color'
			],
		],
	]);



	blocksy_output_box_shadow([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card',
		'value' => blocksy_get_theme_mod('quick_view_shadow', blocksy_box_shadow_value([
			'enable' => true,
			'h_offset' => 0,
			'v_offset' => 50,
			'blur' => 100,
			'spread' => 0,
			'inset' => false,
			'color' => [
				'color' => 'rgba(18, 21, 25, 0.5)',
			],
		])),
	]);

	blocksy_output_spacing([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card',
		'property' => 'theme-border-radius',
		'value' => blocksy_get_theme_mod( 'quick_view_radius',
			blocksy_spacing_value([
				'top' => '7px',
				'left' => '7px',
				'right' => '7px',
				'bottom' => '7px',
			])
		)
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-quick-view-card',
		'value' => blocksy_get_theme_mod('quick_view_background',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'var(--theme-palette-color-8)'
					],
				],
			])
		)
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.quick-view-modal',
		'value' => blocksy_get_theme_mod('quick_view_backdrop',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'rgba(18, 21, 25, 0.8)'
					],
				],
			])
		)
	]);
}


// compare view
$has_compare = $storage->get_settings()['features']['compareview'];

if ($has_compare) {
	blocksy_output_box_shadow([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#ct-compare-modal .ct-container',
		'value' => blocksy_get_theme_mod('compare_modal_shadow', blocksy_box_shadow_value([
			'enable' => true,
			'h_offset' => 0,
			'v_offset' => 50,
			'blur' => 100,
			'spread' => 0,
			'inset' => false,
			'color' => [
				'color' => 'rgba(18, 21, 25, 0.5)',
			],
		])),
		'responsive' => true
	]);

	blocksy_output_spacing([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '#ct-compare-modal .ct-container',
		'property' => 'theme-border-radius',
		'value' => blocksy_get_theme_mod( 'compare_modal_radius',
			blocksy_spacing_value([
				'top' => '7px',
				'left' => '7px',
				'right' => '7px',
				'bottom' => '7px',
			])
		)
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '#ct-compare-modal .ct-container',
		'value' => blocksy_get_theme_mod('compare_modal_background',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'var(--theme-palette-color-8)'
					],
				],
			])
		)
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '#ct-compare-modal',
		'value' => blocksy_get_theme_mod('compare_modal_backdrop',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'rgba(18, 21, 25, 0.8)'
					],
				],
			])
		)
	]);
}

// compare bar
$has_product_compare_bar = blocksy_get_theme_mod('product_compare_bar', 'no');

if ($has_product_compare_bar === 'yes') {
	$product_compare_bar_height = blocksy_expand_responsive_value(
		blocksy_get_theme_mod('product_compare_bar_height', 70)
	);

	$product_compare_bar_visibility = blocksy_get_theme_mod(
		'product_compare_bar_visibility',
		[
			'desktop' => true,
			'tablet' => true,
			'mobile' => true,
		]
	);

	if (! $product_compare_bar_visibility['desktop']) {
		$product_compare_bar_height['desktop'] = '0';
	}

	if (! $product_compare_bar_visibility['tablet']) {
		$product_compare_bar_height['tablet'] = '0';
	}

	if (! $product_compare_bar_visibility['mobile']) {
		$product_compare_bar_height['mobile'] = '0';
	}

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.ct-drawer-canvas[data-compare-bar]',
		'variableName' => 'compare-bar-height',
		'value' => $product_compare_bar_height
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('product_compare_bar_button_font_color'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'variables' => [
			'default' => [
				'selector' => '.ct-compare-bar',
				'variable' => 'theme-button-text-initial-color'
			],

			'hover' => [
				'selector' => '.ct-compare-bar',
				'variable' => 'theme-button-text-hover-color'
			],
		],
		'responsive' => true
	]);

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('product_compare_bar_button_background_color'),
		'default' => [
			'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		],
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'variables' => [
			'default' => [
				'selector' => '.ct-compare-bar',
				'variable' => 'theme-button-background-initial-color'
			],

			'hover' => [
				'selector' => '.ct-compare-bar',
				'variable' => 'theme-button-background-hover-color'
			],
		],
		'responsive' => true
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '.ct-compare-bar',
		'value' => blocksy_get_theme_mod('product_compare_bar_background',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'var(--theme-palette-color-4)',
					],
				],
			])
		)
	]);
}

// size guide
$has_size_guide = $storage->get_settings()['features']['product-size-guide'];

if ($has_size_guide) {

	$size_guide_placement = blocksy_get_theme_mod('size_guide_placement', 'modal');

	$size_guide_background_selector = '#ct-size-guide-modal .ct-container';

	// modal
	if ($size_guide_placement === 'modal') {

		blocksy_output_box_shadow([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#ct-size-guide-modal .ct-container',
			'value' => blocksy_get_theme_mod('size_guide_modal_shadow', blocksy_box_shadow_value([
				'enable' => true,
				'h_offset' => 0,
				'v_offset' => 50,
				'blur' => 100,
				'spread' => 0,
				'inset' => false,
				'color' => [
					'color' => 'rgba(18, 21, 25, 0.5)',
				],
			])),
			'responsive' => true
		]);

		blocksy_output_spacing([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#ct-size-guide-modal .ct-container',
			'property' => 'theme-border-radius',
			'value' => blocksy_get_theme_mod( 'size_guide_modal_radius',
				blocksy_spacing_value([
					'top' => '7px',
					'left' => '7px',
					'right' => '7px',
					'bottom' => '7px',
				])
			)
		]);
	}


	// panel
	if ($size_guide_placement === 'panel') {

		$size_guide_background_selector = '#ct-size-guide-modal .ct-panel-inner';

		blocksy_output_responsive([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#ct-size-guide-modal',
			'variableName' => 'side-panel-width',
			'unit' => '',
			'value' => blocksy_get_theme_mod('size_guide_side_panel_width', [
				'desktop' => '700px',
				'tablet' => '65vw',
				'mobile' => '90vw',
			])
		]);

		blocksy_output_box_shadow([
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'selector' => '#ct-size-guide-modal .ct-panel-inner',
			'value' => blocksy_get_theme_mod('size_guide_panel_shadow', blocksy_box_shadow_value([
				'enable' => true,
				'h_offset' => 0,
				'v_offset' => 0,
				'blur' => 70,
				'spread' => 0,
				'inset' => false,
				'color' => [
					'color' => 'rgba(0, 0, 0, 0.35)',
				],
			])),
			'responsive' => true
		]);
	}

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => $size_guide_background_selector,
		'value' => blocksy_get_theme_mod('size_guide_modal_background',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'var(--theme-palette-color-8)'
					],
				],
			])
		)
	]);

	blocksy_output_background_css([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'selector' => '#ct-size-guide-modal',
		'value' => blocksy_get_theme_mod('size_guide_modal_backdrop',
			blocksy_background_default_value([
				'backgroundColor' => [
					'default' => [
						'color' => 'rgba(18, 21, 25, 0.8)'
					],
				],
			])
		)
	]);

	// close button
	$size_guide_close_button_icon_size = blocksy_get_theme_mod( 'size_guide_close_button_icon_size', 12 );

	if ($size_guide_close_button_icon_size !== 12) {
		$css->put(
			'#ct-size-guide-modal .ct-toggle-close',
			'--theme-icon-size: ' . $size_guide_close_button_icon_size . 'px'
		);
	}

	$close_button_type = blocksy_get_theme_mod('size_guide_close_button_type', 'type-1');

	blocksy_output_colors([
		'value' => blocksy_get_theme_mod('size_guide_close_button_color'),
		'default' => [
			'default' => [ 'color' => 'rgba(0, 0, 0, 0.5)' ],
			'hover' => [ 'color' => 'rgba(0, 0, 0, 0.8)' ],
		],
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'responsive' => true,
		'variables' => [
			'default' => [
				'selector' => '#ct-size-guide-modal .ct-toggle-close',
				'variable' => 'theme-icon-color'
			],

			'hover' => [
				'selector' => '#ct-size-guide-modal .ct-toggle-close:hover',
				'variable' => 'theme-icon-color'
			]
		],
	]);

	if ($close_button_type === 'type-2') {
		blocksy_output_colors([
			'value' => blocksy_get_theme_mod('size_guide_close_button_border_color'),
			'default' => [
				'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
				'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			],
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'responsive' => true,
			'variables' => [
				'default' => [
					'selector' => '#ct-size-guide-modal .ct-toggle-close[data-type="type-2"]',
					'variable' => 'toggle-button-border-color'
				],

				'hover' => [
					'selector' => '#ct-size-guide-modal .ct-toggle-close[data-type="type-2"]:hover',
					'variable' => 'toggle-button-border-color'
				]
			],
		]);
	}

	if ($close_button_type === 'type-3') {
		blocksy_output_colors([
			'value' => blocksy_get_theme_mod('size_guide_close_button_shape_color'),
			'default' => [
				'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
				'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
			],
			'css' => $css,
			'tablet_css' => $tablet_css,
			'mobile_css' => $mobile_css,
			'responsive' => true,
			'variables' => [
				'default' => [
					'selector' => '#ct-size-guide-modal .ct-toggle-close[data-type="type-3"]',
					'variable' => 'toggle-button-background'
				],

				'hover' => [
					'selector' => '#ct-size-guide-modal .ct-toggle-close[data-type="type-3"]:hover',
					'variable' => 'toggle-button-background'
				]
			],
		]);
	}

	if ($close_button_type !== 'type-1') {
		$size_guide_close_button_border_radius = blocksy_get_theme_mod( 'size_guide_close_button_border_radius', 5 );

		if ($size_guide_close_button_border_radius !== 5) {
			$css->put(
				'#ct-size-guide-modal .ct-toggle-close',
				'--toggle-button-radius: ' . $size_guide_close_button_border_radius . 'px'
			);
		}
	}
}

// product archive additional action buttons
blocksy_output_colors([
	'value' => blocksy_get_theme_mod('additional_actions_button_icon_color'),
	'default' => [
		'default' => [ 'color' => 'var(--theme-text-color)' ],
		'hover' => [ 'color' => '#ffffff' ],

		'default_2' => [ 'color' => 'var(--theme-text-color)' ],
		'hover_2' => [ 'color' => 'var(--theme-palette-color-1)' ],
	],
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'responsive' => true,
	'variables' => [
		'default' => [
			'selector' => '.ct-woo-card-extra[data-type="type-1"]',
			'variable' => 'theme-button-text-initial-color'
		],
		'hover' => [
			'selector' => '.ct-woo-card-extra[data-type="type-1"]',
			'variable' => 'theme-button-text-hover-color'
		],

		'default_2' => [
			'selector' => '.ct-woo-card-extra[data-type="type-2"]',
			'variable' => 'theme-button-text-initial-color'
		],
		'hover_2' => [
			'selector' => '.ct-woo-card-extra[data-type="type-2"]',
			'variable' => 'theme-button-text-hover-color'
		],
	],
]);

blocksy_output_colors([
	'value' => blocksy_get_theme_mod('additional_actions_button_background_color'),
	'default' => [
		'default' => [ 'color' => '#ffffff' ],
		'hover' => [ 'color' => 'var(--theme-palette-color-1)' ],

		'default_2' => [ 'color' => '#ffffff' ],
		'hover_2' => [ 'color' => '#ffffff' ],
	],
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'responsive' => true,
	'variables' => [
		'default' => [
			'selector' => '.ct-woo-card-extra[data-type="type-1"]',
			'variable' => 'theme-button-background-initial-color'
		],
		'hover' => [
			'selector' => '.ct-woo-card-extra[data-type="type-1"]',
			'variable' => 'theme-button-background-hover-color'
		],

		'default_2' => [
			'selector' => '.ct-woo-card-extra[data-type="type-2"]',
			'variable' => 'theme-button-background-initial-color'
		],
		'hover_2' => [
			'selector' => '.ct-woo-card-extra[data-type="type-2"]',
			'variable' => 'theme-button-background-hover-color'
		],
	],
]);

// related slideshow columns
if (blocksy_get_theme_mod('woocommerce_related_products_slideshow', 'default') === 'slider') {
	$related_slideshow_columns = blocksy_get_theme_mod('woocommerce_related_products_slideshow_columns', [
		'desktop' => 4,
		'tablet' => 3,
		'mobile' => 1,
	]);

	$related_slideshow_columns['desktop'] = 'calc(100% / ' . $related_slideshow_columns['desktop'] . ')';
	$related_slideshow_columns['tablet'] = 'calc(100% / ' . $related_slideshow_columns['tablet'] . ')';
	$related_slideshow_columns['mobile'] = 'calc(100% / ' . $related_slideshow_columns['mobile'] . ')';

	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => '.related [data-products], .upsells [data-products]',
		'variableName' => 'grid-columns-width',
		'value' => $related_slideshow_columns,
		'unit' => ''
	]);
}
