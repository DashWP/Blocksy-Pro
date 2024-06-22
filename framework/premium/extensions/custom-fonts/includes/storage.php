<?php

namespace Blocksy\Extensions\CustomFonts;

class Storage {
	private $option_name = 'blocksy_ext_custom_fonts_settings';

	public function get_settings() {
		$custom_fonts = apply_filters('blocksy_ext_custom_fonts:dynamic_fonts', []);

		$result = get_option($this->option_name, [
			'fonts' => [
                /*
				[
					'name' => 'ProximaNova',
					'variations' => [
						[
							'variation' => 'n4',
							'attachment_id' => 2828,
						],

						[
							'variation' => 'n7',
							'attachment_id' => 2829,
						]
					],
					'preloads' => [
						'variations' => ['n4'],
					]
				]
                 */
			],
		]);

		foreach ($custom_fonts as $index => $custom_font) {
			$custom_fonts[$index]['__custom'] = true;
			$result['fonts'][] = $custom_fonts[$index];
		}

		return $result;
	}

	public function set_settings($value) {
		$fonts = [];

		foreach ($value['fonts'] as $font) {
			if (! isset($font['__custom'])) {
				$fonts[] = $font;
			}
		}

		update_option($this->option_name, [
			'fonts' => $fonts
		]);
	}

	public function get_normalized_fonts_list() {
		$settings = $this->get_settings();
	
		$fonts = [];

		foreach ($settings['fonts'] as $font) {
			foreach ($font['variations'] as $variation_index => $variation) {
				if (
					isset($variation['attachment_id'])
					&&
					! isset($variation['url'])
				) {
					$font['variations'][$variation_index]['url'] = wp_get_attachment_url(
						$variation['attachment_id']
					);
				} else {
					if (empty(
						$font['variations'][$variation_index]['url']
					)) {
						$font['variations'][$variation_index]['url'] = '';
					}
				}
			}

			$fonts[] = $font;
		}

		return $fonts;
	}
}

