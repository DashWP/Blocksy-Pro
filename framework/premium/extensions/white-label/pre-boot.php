<?php

class BlocksyExtensionWhiteLabelPreBoot {
	public function __construct() {
		add_action('admin_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (! function_exists('blocksy_is_dashboard_page')) return;
			if (! blocksy_is_dashboard_page()) return;

			wp_enqueue_script(
				'blocksy-ext-white-label-admin-dashboard-scripts',
				BLOCKSY_URL . 'framework/premium/extensions/white-label/dashboard-static/bundle/main.js',
				['ct-options-scripts', 'ct-dashboard-scripts'],
				$data['Version']
			);
		});
	}

	public function ext_data() {
		$data = get_option('blocksy_ext_white_label_settings', [
			'locked' => false,
			'hide_demos' => false,
			'hide_billing_account' => false,

			'hide_plugins_tab' => false,
			'hide_changelogs_tab' => false,
			'hide_support_section' => false,
			'hide_docs_section' => false,
			'hide_video_section' => false,

			'author' => [
				'name' => '',
				'url' => '',
				'support' => ''
			],

			'theme' => [
				'name' => '',
				'description' => '',
				'screenshot' => '',
				'icon' => '',
				'gutenberg_icon' => ''
			],

			'plugin' => [
				'name' => '',
				'description' => '',
				'thumbnail' => ''
			]
		]);

		if (defined('BLOCKSY_WHITE_LABEL_LOCKED')) {
			$data['locked'] = BLOCKSY_WHITE_LABEL_LOCKED;
		}

		return apply_filters('blocksy:ext:white-label:settings', $data);
	}
}

