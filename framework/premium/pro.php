<?php

namespace Blocksy;

class Premium {
	public $content_blocks = null;
	public $premium_header = null;
	public $premium_footer = null;
	public $code_editor = null;

	public function __construct() {
		$this->load_premium_translations();

		$this->code_editor = new CodeEditor();

		require BLOCKSY_PATH . '/framework/premium/helpers/helpers.php';
		require BLOCKSY_PATH . '/framework/premium/helpers/content-blocks.php';

		$this->content_blocks = new ContentBlocks();
		new ContentBlocksLayer();
		new CopyOptions();

		new MaintenanceMode();

		$this->premium_header = new PremiumHeader();
		$this->premium_footer = new PremiumFooter();

		new Local_Gravatars_Init();

		new CloneCPT();
		new CaptchaToolsIntegration();
		new MediaVideo();

		new SocialsExtra();

		new PerformanceTypography();

		register_block_type(
			'blocksy-companion-pro/code-editor',
			[
				'api_version' => 3,
				'render_callback' => function ($attributes, $content) {
					if (is_admin()) {
						return '';
					}

					if (! empty($content)) {
						$inline_code = str_replace(
							'<pre class="wp-block-code"><code>',
							'',
							str_replace(
								'</code></pre>',
								'',
								html_entity_decode(htmlspecialchars_decode($content))
							)
						);

						$ending = '<?php ';

						if (strpos($inline_code, '<?php') !== false) {
							if (strpos($inline_code, '?>') === false) {
								$ending = '';
							}
						}

						ob_start();
						eval('?' . '>' . $inline_code . $ending);
						return ob_get_clean();
					}

					if (empty($attributes['code'])) {
						return '';
					}

					$inline_code = $attributes['code'];

					$ending = '<?php ';

					if (strpos($inline_code, '<?php') !== false) {
						if (strpos($inline_code, '?>') === false) {
							$ending = '';
						}
					}

					ob_start();
					eval('?' . '>' . $inline_code . $ending);
					$result = ob_get_clean();

					return $result;
				}
			]
		);

		add_filter(
			'plugin_row_meta',
			function ($plugin_meta, $plugin_file, $plugin_data, $status) {
				if (! isset($plugin_data['slug'])) {
					return $plugin_meta;
				}

				if ($plugin_data['slug'] === 'blocksy-companion') {
					unset($plugin_meta[2]);
				}

				return $plugin_meta;
			},
			10,4
		);

		add_filter('blocksy_extensions_paths', function ($p) {
			$p[] = BLOCKSY_PATH . 'framework/premium/extensions';
			return $p;
		});

		add_action(
			'customize_preview_init',
			function () {
				if (! function_exists('get_plugin_data')) {
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_enqueue_script(
					'blocksy-pro-customizer',
					BLOCKSY_URL . 'framework/premium/static/bundle/sync.js',
					['ct-customizer'],
					$data['Version'],
					true
				);
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				if (! function_exists('get_plugin_data')) {
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}

				global $wp_customize;

				$data = get_plugin_data(BLOCKSY__FILE__);

				$deps = ['ct-options-scripts'];

				$current_screen = get_current_screen();

				if ($current_screen && $current_screen->id === 'customize') {
					$deps = ['ct-customizer-controls'];
				}

				wp_enqueue_script(
					'blocksy-premium-admin-scripts',
					BLOCKSY_URL . 'framework/premium/static/bundle/options.js',
					$deps,
					$data['Version'],
					true
				);

				$hooks_manager = new HooksManager();

				$localize = array_merge(
					[
						'all_hooks' => $hooks_manager->get_all_hooks(),
						'ajax_url' => admin_url('admin-ajax.php'),
						'rest_url' => get_rest_url(),
						'content_blocks' => blc_get_content_blocks(),
						'admin_url' => get_dashboard_url()
					],
					$this->code_editor->get_admin_localizations(),
					$this->premium_footer->get_admin_localizations(),
				);

				wp_localize_script(
					'blocksy-premium-admin-scripts',
					'blocksy_premium_admin',
					$localize
				);

				wp_enqueue_style(
					'blocksy-premium-styles',
					BLOCKSY_URL . 'framework/premium/static/bundle/options.min.css',
					[],
					$data['Version']
				);
			},
			50
		);

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			$data['dynamic_styles_selectors'][] = [
				'selector' => '.ct-media-container[data-media-id], .ct-dynamic-media[data-media-id]',
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'framework/premium/static/bundle/video-lazy.min.css'
				)
			];

			return $data;
		});

		add_action('wp_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (! isset($_GET['blocksy_preview_hooks'])) {
				return;
			}

			wp_enqueue_script(
				'blocksy-pro-scripts',
				BLOCKSY_URL . 'framework/premium/static/bundle/frontend.js',
				['ct-scripts'],
				$data['Version'],
				true
			);
		});
	}

	public function load_premium_translations() {
		if (! class_exists('WP_Translation_Controller')) {
			return;
		}

		$locale = determine_locale();

		$i18n_controller = \WP_Translation_Controller::get_instance();

		$preferred_format = apply_filters(
			'translation_file_format',
			'php',
			'blocksy-companion'
		);

		if (! in_array($preferred_format, array('php', 'mo'), true)) {
			$preferred_format = 'php';
		}

		$mofile = 'blocksy-companion' . '-' . $locale;

		$file_extension = 'mo';

		if ($preferred_format === 'php') {
			// $file_extension = 'l10n.php';
		}

		$mofile = $mofile . '.' . $file_extension;

		$file_path = BLOCKSY_PATH . 'languages/' . $mofile;

		if (! file_exists($file_path)) {
			return;
		}

		$res = $i18n_controller->load_file(
			$file_path,
			'blocksy-companion'
		);
	}
}
