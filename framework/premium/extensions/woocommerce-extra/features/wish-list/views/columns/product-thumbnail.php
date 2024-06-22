<?php

$image_size = blocksy_get_theme_mod('wishlist_image_size', 'woocommerce_thumbnail');
$image_ratio = blocksy_get_theme_mod('wishlist_image_ratio', '1/1');

echo blocksy_media([
	'no_image_type' => 'woo',
	'attachment_id' => $product->get_image_id(),
	'post_id' => $product->get_id(),
	'size' => $image_size,
	'ratio' => $image_ratio,
	'tag_name' => 'a',
	'class' => 'product-thumb',
	'html_atts' => [
		'href' => esc_url($product->get_permalink()),
	],
]);
