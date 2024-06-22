<?php

$options = blocksy_akg(
	'options',
	blocksy_get_variables_from_file(
		dirname(__FILE__) . '/header.php',
		['options' => []]
	)
);

