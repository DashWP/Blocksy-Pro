<?php

class BlocksyExtensionColorModeSwitch {
	public function __construct() {
		new \Blocksy\Extensions\ColorModeSwitch\LogoEnhancements();

		add_action(
			'customize_preview_init',
			function () {
				if (! function_exists('get_plugin_data')) {
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_enqueue_script(
					'blocksy-ext-color-mode-customizer-sync',
					BLOCKSY_URL . 'framework/premium/extensions/color-mode-switch/static/bundle/sync.js',
					[ 'customize-preview', 'ct-scripts'],
					$data['Version'],
					true
				);
			}
		);

		add_filter('blocksy:general:html-attr', function ($attr) {
			$theme = 'light';

			$render = new \Blocksy_Header_Builder_Render();

			if ($render->contains_item('color-mode-switcher')) {
				$atts = $render->get_item_data_for('color-mode-switcher');

				$default_color_mode = blocksy_akg(
					'default_color_mode',
					$atts,
					'light'
				);

				if ($default_color_mode === 'dark') {
					$theme = 'dark';
				}

				if ($default_color_mode === 'system') {
					$theme = 'os-default';
				}
			}

			if (isset($_COOKIE['blocksy_current_theme'])) {
				if ($_COOKIE['blocksy_current_theme'] === 'dark') {
					$theme = 'dark';
				}

				if ($_COOKIE['blocksy_current_theme'] === 'light') {
					$theme = 'light';
				}
			}

			$attr['data-color-mode'] = $theme;

			return $attr;
		});

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			$chunks[] = [
				'id' => 'blocksy_dark_mode',
				'selector' => '.ct-color-switch',
				'trigger' => 'click',
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/extensions/color-mode-switch/static/bundle/main.js'
				)
			];

			$cache_manager = new \Blocksy\CacheResetManager();

			if ($cache_manager->is_there_any_page_caching()) {
				$chunks[] = [
					'id' => 'blocksy_dark_mode',
					'selector' => '.ct-color-switch',
					'url' => blocksy_cdn_url(
						BLOCKSY_URL . 'framework/premium/extensions/color-mode-switch/static/bundle/main.js'
					)
				];
			}

			return $chunks;
		});

		add_filter('blocksy:header:items-paths', function ($paths) {
			$paths[] = dirname(__FILE__) . '/header-items';
			return $paths;
		});

		add_filter('blocksy:options:colors:palette:after', function ($options) {
			$options['darkColorPalette'] = [
				'label' => __( 'Dark Mode Color Palette', 'blocksy-companion' ),
				'type'  => 'ct-color-palettes-mirror',
				'divider' => 'top',
				'wrapperAttr' => [
					'data-label' => 'heading-label'
				],

				'value' => [
					'color1' => [
						'color' => '#006466',
					],

					'color2' => [
						'color' => '#065A60',
					],

					'color3' => [
						'color' => '#7F8C9A',
					],

					'color4' => [
						'color' => '#ffffff',
					],

					'color5' => [
						'color' => '#0E141B',
					],

					'color6' => [
						'color' => '#141b22',
					],

					'color7' => [
						'color' => '#1B242C',
					],

					'color8' => [
						'color' => '#1B242C',
					],
				],

				'sync' => 'live'
			];

			return $options;
		});

		add_action('wp_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (is_admin()) return;

			if (! class_exists('Blocksy_Header_Builder_Render')) {
				return;
			}

			$render = new \Blocksy_Header_Builder_Render();

			if (
				$render->contains_item('color-mode-switcher')
				||
				is_customize_preview()
			) {
				wp_enqueue_style(
					'blocksy-ext-color-mode-switcher-styles',
					BLOCKSY_URL . 'framework/premium/extensions/color-mode-switch/static/bundle/main.min.css',
					['ct-main-styles'],
					$data['Version']
				);
			}
		}, 50);
	}
}

