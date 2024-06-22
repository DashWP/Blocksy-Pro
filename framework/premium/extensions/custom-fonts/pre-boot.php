<?php

class BlocksyExtensionCustomFontsPreBoot {
	public function __construct() {
		add_action('admin_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (! function_exists('blocksy_is_dashboard_page')) return;
			if (! blocksy_is_dashboard_page()) return;

			wp_enqueue_script(
				'blocksy-ext-custom-fonts-admin-dashboard-scripts',
				BLOCKSY_URL . 'framework/premium/extensions/custom-fonts/dashboard-static/bundle/main.js',
				['ct-options-scripts', 'ct-dashboard-scripts'],
				$data['Version']
			);
		});
	}

	public function ext_action($payload) {
		$storage = new \Blocksy\Extensions\CustomFonts\Storage();

		if (
			! isset($payload['type'])
			||
			! isset($payload['settings'])
			||
			$payload['type'] !== 'update-settings'
		) {
			return null;
		}

		if (isset($payload['settings']['urls'])) {
			$performance_storage = new \Blocksy\PerformanceTypography();

			$performance_storage->set_settings([
				'custom' => blocksy_akg('urls', $payload['settings'], [])
			]);

			unset($payload['settings']['urls']);
		}

		$storage->set_settings($payload['settings']);

		return [
			'settings' => $payload['settings']
		];
	}

	public function ext_data() {
		$storage = new \Blocksy\Extensions\CustomFonts\Storage();

		return [
			'settings' => [
				'fonts' => $storage->get_normalized_fonts_list(),
			]
		];
	}
}

