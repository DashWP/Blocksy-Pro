<?php

$product = wc_get_product($_GET['product_id']);

$behaviour = isset($_GET['size_guide_side_panel_position']) ?
	$_GET['size_guide_side_panel_position'] . '-side' :
	blocksy_get_theme_mod('size_guide_side_panel_position', 'right') . '-side';

$close_button_type = blocksy_get_theme_mod('size_guide_close_button_type', 'type-1');

$panel_attr = [
	'id' => 'ct-size-guide-modal',
	'class' => 'ct-panel',
	'data-behaviour' => $behaviour
];

$panel_heading = blocksy_html_tag(
	'div',
	[
		'class' => 'ct-panel-actions'
	],
	blocksy_html_tag(
		'span',
		[
			'class' => 'ct-panel-heading'
		],
		$product->get_title() . ' - ' . __('Size Guide', 'blocksy-companion')
	) .
	blocksy_html_tag(
		'button',
		[
			'class' => 'ct-toggle-close',
			'data-type' => $close_button_type,
			'aria-label' => __('Close Sizes Modal', 'blocksy-companion'),
		],
		'<svg class="ct-icon" width="12" height="12" viewBox="0 0 15 15">
		<path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path>
		</svg>'
	)
);

$panel_content = blocksy_html_tag(
	'div',
	[
		'class' => 'ct-panel-content',
	],
	blocksy_html_tag(
		'div',
		[
			'class' => 'ct-panel-content-inner',
		],
		blocksy_html_tag(
			'div',
			[
				'class' => 'ct-size-guide-content',
			],
			blocksy_html_tag(
				'div',
				[
					'class' => 'entry-content',
				],
				$table_html
			)
		)
	)
);

$panel_content = blocksy_html_tag(
	'div',
	[
		'class' => 'ct-panel-inner',
	],
	$panel_heading . $panel_content
);

echo blocksy_html_tag('div', $panel_attr, $panel_content);
