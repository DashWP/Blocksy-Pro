<?php

namespace Blocksy\CustomPostType\Integrations;

class Stackable extends \Blocksy\CustomPostTypeRenderer {
	public function get_content($args = []) {
		return \Blocksy\CustomPostTypeRenderer::NOT_IMPLEMENTED;
	}

	public function pre_output() {
		$post = get_post($this->id);

		if ($post) {
			$contentpost = $post->post_content;

			$contentpost = str_replace(
				'<!-- wp:post-content /-->',
				'',
				$contentpost
			);

			if (has_blocks($contentpost)) {
				$blocks = parse_blocks($contentpost);

				foreach ($blocks as $block) {
					render_block($block);
				}
			}
		}
	}
}

