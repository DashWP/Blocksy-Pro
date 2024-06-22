<?php

$wish_list = blc_get_ext('woocommerce-extra')->get_wish_list()->get_current_wish_list();

$has_custom_user = isset($_GET['wish_list_id']);

add_filter('wsa_sample_should_add_button', '__return_false');

if (class_exists('EPOFW_Front')) {
	$instance = \EPOFW_Front::instance();

	remove_action(
		'woocommerce_before_add_to_cart_button',
		array(
			$instance,
			'epofw_before_add_to_cart_button',
		),
		10
	);

	remove_action(
		'woocommerce_after_add_to_cart_button',
		array($instance, 'epofw_after_add_to_cart_button'),
		10
	);
}

if (empty($wish_list)) {
	echo blocksy_render_view(dirname(__FILE__) . '/views/table-no-results.php');
	return;
}

?>

<div class="ct-woocommerce-wishlist-table">
	<table class="shop_table">
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'Product', 'blocksy-companion' ); ?></th>
				<th class="wishlist-product-actions"><?php esc_html_e( 'Add to cart', 'blocksy-companion' ); ?></th>
				<?php if (! $has_custom_user) { ?>
				<th class="wishlist-product-remove">&nbsp;</th>
				<?php } ?>
			</tr>
		</thead>

		<tbody>
			<?php

				foreach ($wish_list as $single_product) {
					echo blocksy_render_view(
						dirname(__FILE__) . '/views/table-single-product-row.php',
						[
							'single_product' => $single_product,
							'has_custom_user' => $has_custom_user,
						]
					);
				}

			?>
		</tbody>
	</table>
</div>

<?php
	if (
		blocksy_get_theme_mod('product_wishlist_display_for', 'logged_users') === 'all_users'
		&&
		blocksy_get_theme_mod('woocommerce_wish_list_page')
		&&
		is_user_logged_in()
		&&
		blocksy_get_theme_mod('wish_list_has_share_box', 'no') === 'yes'
	) {
		echo blocksy_get_social_share_box([
			'html_atts' => [
				'data-type' => 'type-3'
			],
			'links_wrapper_attr' => [
				'data-icons-type' => 'simple'
			],
			'custom_share_url' => add_query_arg(
				'wish_list_id',
				get_current_user_id(),
				get_permalink(blocksy_get_theme_mod('woocommerce_wish_list_page'))
			),
			'strategy' => [
				'strategy' => 'customizer',
				'prefix' => 'wish_list'
			]
		]);
	}
?>
