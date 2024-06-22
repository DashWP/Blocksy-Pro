<?php

echo blocksy_render_view(
	get_template_directory() . '/inc/panel-builder/footer/menu/view.php',
	[
		'atts' => $atts,
		'attr' => $attr,
		'class' => 'footer-menu-inline',
		'id' => 'footer-menu-2',
		'location' => 'footer_2'
	]
);

