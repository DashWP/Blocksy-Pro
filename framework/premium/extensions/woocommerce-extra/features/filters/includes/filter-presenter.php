<?php

namespace Blocksy\Extensions\WoocommerceExtra;

class FilterPresenter {
	private $filter = null;

	public function __construct($filter) {
		$this->filter = $filter;
	}

	public function render() {
		$render_descriptor = $this->filter->render();

		if (is_wp_error($render_descriptor)) {
			return blocksy_html_tag(
				'div',
				[
					'class' => 'ct-filter-widget-wrapper'
				],
				$render_descriptor->get_error_message()
			);
		}

		$items = $render_descriptor['items'];

		$items = implode('', $items);

		if (empty($items)) {
			return '';
		}

		$icon = blocksy_html_tag(
			'span',
			[
				'class' => 'ct-filter-search-icon'
			],
			'<svg class="ct-filter-search-zoom-icon" width="13" height="13" fill="currentColor" aria-hidden="true" viewBox="0 0 15 15"><path d="M14.8,13.7L12,11c0.9-1.2,1.5-2.6,1.5-4.2c0-3.7-3-6.8-6.8-6.8S0,3,0,6.8s3,6.8,6.8,6.8c1.6,0,3.1-0.6,4.2-1.5l2.8,2.8c0.1,0.1,0.3,0.2,0.5,0.2s0.4-0.1,0.5-0.2C15.1,14.5,15.1,14,14.8,13.7z M1.5,6.8c0-2.9,2.4-5.2,5.2-5.2S12,3.9,12,6.8S9.6,12,6.8,12S1.5,9.6,1.5,6.8z"></path></svg>' .
			'<svg class="ct-filter-search-reset-icon" width="10" height="10" fill="currentColor" aria-hidden="true" viewBox="0 0 15 15"><path d="M1 15a1 1 0 01-.71-.29 1 1 0 010-1.41l5.8-5.8-5.8-5.8A1 1 0 011.7.29l5.8 5.8 5.8-5.8a1 1 0 011.41 1.41l-5.8 5.8 5.8 5.8a1 1 0 01-1.41 1.41l-5.8-5.8-5.8 5.8A1 1 0 011 15z"></path></svg>'
		);

		$data_filter_criteria = $this->filter->attributes['type'];

		if ($this->filter->attributes['type'] === 'categories') {
			$data_filter_criteria = 'taxonomy:' . $this->filter->attributes['taxonomy'];
		}

		$items = blocksy_html_tag(
			'ul',
			array_merge(
				[
					'class' => 'ct-filter-widget',
					'data-display-type' => $this->filter->attributes['viewType'],
					'data-filter-criteria' => $data_filter_criteria,
				],
				blocksy_akg('list_attr', $render_descriptor, []),
				$this->filter->attributes['limitHeight'] ? [
					'style' => 'max-height: ' . $this->filter->attributes['limitHeightValue'] . 'px'
				] : []
			),
			$items
		);

		$filter_name = __('category', 'blocksy-companion');

		if ($this->filter->attributes['type'] === 'attributes') {
			$maybe_taxonomy_name = wc_attribute_taxonomy_name($this->filter->attributes['attribute']);

			if (taxonomy_exists($maybe_taxonomy_name)) {
				$labels = get_taxonomy_labels(get_taxonomy($maybe_taxonomy_name));

				if (isset($labels->singular_name)) {
					$filter_name = strtolower($labels->singular_name);
				}
			}
		}

		return blocksy_html_tag(
			'div',
			[
				'class' => 'ct-filter-widget-wrapper'
			],
			(
				$this->filter->attributes['showSearch'] ? blocksy_html_tag(
					'div',
					[
						'class' => 'ct-filter-search',
					],
					blocksy_html_tag(
						'input',
						[
							'type' => 'search',
							'placeholder' => blc_safe_sprintf(
								__(
									'Find by %s',
									'blocksy-companion'
								),
								$filter_name
							)
						],
						''
					) .
					$icon
				) : ''
			) .
			$items .
			$this->get_reset_button()
		);
	}

	private function get_reset_button() {
		$params = FiltersUtils::get_query_params();

		if (
			! isset($params['params'][$this->filter->get_filter_name()])
			||
			! $this->filter->attributes['showResetButton']
		) {
			return '';
		}

		return blocksy_html_tag(
			'div',
			['class' => 'ct-filter-reset'],
			blocksy_html_tag(
				'a',
				[
					'href' => remove_query_arg(array_merge([
						$this->filter->get_filter_name()
					], $this->filter->additional_query_string_params())),
					'rel' => 'nofollow',
					'class' => 'ct-button-ghost',
				],
				'<svg width="12" height="12" viewBox="0 0 15 15" fill="currentColor"><path d="M8.5,7.5l4.5,4.5l-1,1L7.5,8.5L3,13l-1-1l4.5-4.5L2,3l1-1l4.5,4.5L12,2l1,1L8.5,7.5z"></path></svg>' .
				__('Reset Filter', 'blocksy-companion')
			)
		);
	}
}
