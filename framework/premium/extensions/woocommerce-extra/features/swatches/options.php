<?php

$options = [
	'label' => __('Variation Swatches', 'blocksy-companion'),
	'type' => 'ct-panel',
	'setting' => ['transport' => 'postMessage'],
	'inner-options' => [

		blocksy_rand_md5() => [
			'title' => __( 'General', 'blocksy-companion' ),
			'type' => 'tab',
			'options' => [

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Color Swatches', 'blocksy-companion' ),
				],

				'color_swatch_shape' => [
					'label' => __('Swatch Shape', 'blocksy-companion'),
					'type' => 'ct-radio',
					'value' => 'round',
					'view' => 'text',
					'design' => 'block',
					'choices' => [
						'round' => __('Round', 'blocksy-companion'),
						'square' => __('Square', 'blocksy-companion'),
					],
					'sync' => 'live',
				],

				'single_color_swatch_size' => [
					'label' => __('Single Page Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 30,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				'filter_widget_color_swatch_size' => [
					'label' => __('Widget Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 25,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				blocksy_rand_md5() => [
					'type' => 'ct-condition',
					'condition' => [ 'woo_card_layout:array-ids:product_swatches:enabled' => '!no' ],
					'values_source' => 'global',
					'options' => [

						'archive_color_swatch_size' => [
							'label' => __('Archive Cards Swatch Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 25,
							'min' => 10,
							'max' => 100,
							'sync' => 'live',
						],

					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Image Swatches', 'blocksy-companion' ),
				],

				'image_swatch_shape' => [
					'label' => __('Swatch Shape', 'blocksy-companion'),
					'type' => 'ct-radio',
					'value' => 'round',
					'view' => 'text',
					'design' => 'block',
					'choices' => [
						'round' => __('Round', 'blocksy-companion'),
						'square' => __('Square', 'blocksy-companion'),
					],
					'sync' => 'live',
				],

				'single_image_swatch_size' => [
					'label' => __('Single Page Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 35,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				'filter_widget_image_swatch_size' => [
					'label' => __('Widget Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 35,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				blocksy_rand_md5() => [
					'type' => 'ct-condition',
					'condition' => [ 'woo_card_layout:array-ids:product_swatches:enabled' => '!no' ],
					'values_source' => 'global',
					'options' => [

						'archive_image_swatch_size' => [
							'label' => __('Archive Cards Swatch Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 25,
							'min' => 10,
							'max' => 100,
							'sync' => 'live',
						],

					],
				],


				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Button Swatches', 'blocksy-companion' ),
				],

				'button_swatch_shape' => [
					'label' => __('Swatch Shape', 'blocksy-companion'),
					'type' => 'ct-radio',
					'value' => 'round',
					'view' => 'text',
					'design' => 'block',
					'choices' => [
						'round' => __('Round', 'blocksy-companion'),
						'square' => __('Square', 'blocksy-companion'),
					],
					'sync' => 'live',
				],

				'single_button_swatch_size' => [
					'label' => __('Single Page Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 35,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				'filter_widget_button_swatch_size' => [
					'label' => __('Widget Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 30,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				blocksy_rand_md5() => [
					'type' => 'ct-condition',
					'condition' => [ 'woo_card_layout:array-ids:product_swatches:enabled' => '!no' ],
					'values_source' => 'global',
					'options' => [

						'archive_button_swatch_size' => [
							'label' => __('Archive Cards Swatch Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 25,
							'min' => 10,
							'max' => 100,
							'sync' => 'live',
						],

					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Mixed Swatches', 'blocksy-companion' ),
				],

				'mixed_swatch_shape' => [
					'label' => __('Swatch Shape', 'blocksy-companion'),
					'type' => 'ct-radio',
					'value' => 'round',
					'view' => 'text',
					'design' => 'block',
					'choices' => [
						'round' => __('Round', 'blocksy-companion'),
						'square' => __('Square', 'blocksy-companion'),
					],
					'sync' => 'live',
				],

				'single_mixed_swatch_size' => [
					'label' => __('Single Page Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 30,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				'filter_widget_mixed_swatch_size' => [
					'label' => __('Widget Swatch Size', 'blocksy-companion'),
					'type' => 'ct-slider',
					'value' => 25,
					'min' => 10,
					'max' => 100,
					'sync' => 'live',
				],

				blocksy_rand_md5() => [
					'type' => 'ct-condition',
					'condition' => [ 'woo_card_layout:array-ids:product_swatches:enabled' => '!no' ],
					'values_source' => 'global',
					'options' => [

						'archive_mixed_swatch_size' => [
							'label' => __('Archive Cards Swatch Size', 'blocksy-companion'),
							'type' => 'ct-slider',
							'value' => 25,
							'min' => 10,
							'max' => 100,
							'sync' => 'live',
						],

					],
				],

				'has_swatches_url' => [
					'type'  => 'ct-switch',
					'label' => __( 'Generate Variation URL', 'blocksy-companion' ),
					'value' => 'no',
					'disableRevertButton' => true,
					'sync' => 'live',
					'divider' => 'top:full',
					// 'desc' => __( 'Generate a sharable url based on the selected attributes.', 'blocksy-companion' ),
					'desc' => __( 'Generate a shareable single product page URL with pre-selected variation attributes.', 'blocksy-companion' ),
				],
			],
		],

		blocksy_rand_md5() => [
			'title' => __( 'Design', 'blocksy-companion' ),
			'type' => 'tab',
			'options' => [
				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Color Swatches', 'blocksy-companion' ),
				],

				'color_swatch_border_color' => [
					'label' => __( 'Border Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => 'rgba(0, 0, 0, 0.2)',
						],

						'active' => [
							'color' => 'rgba(0, 0, 0, 0.2)',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'var(--theme-border-color)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Image Swatches', 'blocksy-companion' ),
				],

				'image_swatch_border_color' => [
					'label' => __( 'Border Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => 'var(--theme-palette-color-1)',
						],

						'active' => [
							'color' => 'var(--theme-palette-color-1)',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'var(--theme-border-color)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Button Swatches', 'blocksy-companion' ),
				],

				'button_swatch_text_color' => [
					'label' => __( 'Text Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'active' => [
							'color' => '#ffffff',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'var(--theme-text-color)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
							'inherit' => 'var(--theme-text-color)'
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],

				'button_swatch_border_color' => [
					'label' => __( 'Border Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => 'var(--theme-palette-color-1)',
						],

						'active' => [
							'color' => 'var(--theme-palette-color-1)',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'var(--theme-border-color)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],

				'button_swatch_background_color' => [
					'label' => __( 'Background Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'active' => [
							'color' => 'var(--theme-palette-color-1)',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'rgba(0, 0, 0, 0)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
							'inherit' => 'rgba(0, 0, 0, 0)'
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Mixed Swatches', 'blocksy-companion' ),
				],

				'mixed_swatch_border_color' => [
					'label' => __( 'Border Color', 'blocksy-companion' ),
					'type'  => 'ct-color-picker',
					'design' => 'inline',
					'setting' => [ 'transport' => 'postMessage' ],

					'value' => [
						'default' => [
							'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
						],

						'hover' => [
							'color' => 'rgba(0, 0, 0, 0.2)',
						],

						'active' => [
							'color' => 'rgba(0, 0, 0, 0.2)',
						],
					],

					'pickers' => [
						[
							'title' => __( 'Initial', 'blocksy-companion' ),
							'id' => 'default',
							'inherit' => 'var(--theme-border-color)'
						],

						[
							'title' => __( 'Hover', 'blocksy-companion' ),
							'id' => 'hover',
						],

						[
							'title' => __( 'Active', 'blocksy-companion' ),
							'id' => 'active',
						],
					],
				],
			],
		],

	],
];