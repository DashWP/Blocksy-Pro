<?php
/**
 * The template for putting the site in maintenance mode.
 *
 * @package Blocksy
 */

get_header();

$maybe_content_block = blc_get_content_block_that_matches([
	'template_type' => 'maintenance',
	'match_conditions' => true,
]);

echo blc_render_content_block($maybe_content_block);

get_footer();
