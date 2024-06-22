<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class FiltersUtils {
	static public function get_query_params() {
		$url = blocksy_current_url();

		return [
			'params' => $_GET,
			'url' => $url
		];
	}
}

