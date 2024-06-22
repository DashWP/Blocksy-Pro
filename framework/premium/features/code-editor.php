<?php

namespace Blocksy;

class CodeEditor {
	private $post_types = ['ct_content_block', 'ct_product_tab', 'ct_size_guide'];

	public function __construct() {
		add_action('admin_body_class', function ($classes) {
			global $pagenow;
			global $post;

			$screen = get_current_screen();

			if ('post-new.php' !== $pagenow && 'post.php' !== $pagenow) {
				return $classes;
			}

			if (! in_array($screen->post_type, $this->post_types)) {
				return $classes;
			}

			$atts = blocksy_get_post_options($post->ID);

			if (blocksy_akg('has_inline_code_editor', $atts, 'no') === 'yes') {
				$classes .= ' blocksy-inline-code-editor';
			}

			return $classes;
		});
	}

	public function get_admin_localizations() {
		global $pagenow;
		global $post;

		$screen = get_current_screen();

		$localize = [];

		if ($pagenow === 'post-new.php' || $pagenow === 'post.php') {
			if (
				in_array($screen->post_type, $this->post_types)
				&&
				function_exists('wp_enqueue_code_editor')
			) {
				$localize['editor_settings'] = wp_enqueue_code_editor(
					[
						'type' => 'application/x-httpd-php',
						'codemirror' => [
							'indentUnit' => 2,
							'tabSize' => 2,
						]
					]
				);
			}
		}

		return $localize;
	}
}

