<?php

namespace Blocksy\CustomPostType\Integrations;

class UltimateBlocks extends \Blocksy\CustomPostTypeRenderer {
	public function get_content($args = []) {
		return \Blocksy\CustomPostTypeRenderer::NOT_IMPLEMENTED;
	}

	public function pre_output() {
		add_action( 'enqueue_block_assets', function () {
			ub_load_assets();
		});

		add_action('wp_head', function () {
			global $post;
			$post = get_post($this->id);
			setup_postdata($post);
			ub_include_block_attribute_css();
			wp_reset_postdata();
		});
	}
}


