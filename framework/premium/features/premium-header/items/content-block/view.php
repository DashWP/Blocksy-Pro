<?php

if (!isset($device)) {
	$device = 'desktop';
}

$class = 'ct-header-content-block';

$hook_id = blocksy_default_akg('hook_id', $atts, '');

$hook_id = apply_filters('wpml_object_id', $hook_id, 'post', true);

$content = '';

if (
	$hook_id
	&&
	\Blocksy\Plugin::instance()
		->premium
		->content_blocks
		->is_hook_eligible_for_display($hook_id, [
			'match_conditions' => false
		])
) {
	$content = \Blocksy\Plugin::instance()
		->premium
		->content_blocks
		->output_hook($hook_id, [
			'layout' => false
		]);
}

if (! empty($content)) {
	echo blocksy_html_tag(
		'div',
		array_merge(
			[
				'class' => $class,
				'data-hook-id' => $hook_id,
			],
			$attr
		),
		$content
	);
}
