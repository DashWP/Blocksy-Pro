<?php

namespace Blocksy\Extensions\PostTypesExtra;

class DynamicData {
	public function __construct() {
		add_action('init', [$this, 'init']);
	}

	public function init() {
		// Options

		add_filter(
			'blocksy:options:page-title:design:before_breadcrumbs',
			function ($opts, $prefix) {
				return $this->add_design_options($opts, $prefix, 'hero_elements');
			},
			10, 2
		);

		add_filter(
			'blocksy:options:posts-listing:design:before_card_background',
			function ($opts, $prefix) {
				return $this->add_design_options($opts, $prefix, 'archive_order');
			},
			10, 2
		);

		add_filter(
			'blocksy:options:meta:meta_default_elements',
			function ($layers, $prefix, $computed_cpt) {
				$opt = $this->complement_layers_option(
					[
						'value' => [],
						'settings' => []
					],
					$computed_cpt,
					[
						'has_icon' => true,
						'has_label_option' => false,
						'has_spacing' => false
					]
				);

				foreach ($opt['value'] as $layer) {
					$layers[] = $layer;
				}

				return $layers;
			},
			10, 3
		);

		add_filter(
			'blocksy:options:meta:meta_elements',
			function ($layers, $prefix, $computed_cpt) {
				foreach ($this->complement_layers_option(
					[
						'value' => [],
						'settings' => []
					],
					$computed_cpt,
					[
						'has_icon' => true,
						'has_label_option' => false,
						'has_spacing' => false
					]
				)['settings'] as $id => $layer) {
					$layers[$id] = $layer;
				}

				return $layers;
			},
			10, 3
		);

		add_filter(
			'blocksy:options:page-title:hero-elements',
			function ($option, $prefix) {
				if (
					$prefix !== 'single_blog_post'
					&&
					$prefix !== 'single_page'
					&&
					$prefix !== 'product'
					&&
					strpos($prefix, '_single') === false
				) {
					return $option;
				}

				return $this->complement_layers_option($option, $prefix);
			},
			10, 2
		);

		add_filter(
			'blocksy:options:posts-listing-archive-order',
			function ($option, $prefix) {
				return $this->complement_layers_option(
					$option,
					$prefix,
					[
						'has_spacing' => true
					]
				);
			},
			10, 2
		);

		add_filter(
			'blocksy_woo_card_options_layers:defaults',
			[$this, 'get_product_layer_default'],
			10, 1
		);

		add_filter(
			'blocksy_woo_single_options_layers:defaults',
			[$this, 'get_product_layer_default'],
			10, 1
		);

		add_filter(
			'blocksy_woo_single_right_options_layers:defaults',
			[$this, 'get_product_layer_default'],
			10, 1
		);

		add_filter(
			'blocksy_woo_card_options_layers:extra',
			[$this, 'get_product_layer_extra'],
			10, 1
		);

		add_filter(
			'blocksy_woo_single_options_layers:extra',
			[$this, 'get_product_layer_extra'],
			10, 1
		);

		// Rendering

		add_action(
			'blocksy:woocommerce:product:custom:layer',
			function ($atts) {
				$this->render_dynamic_field_layer($atts);
			}
		);

		add_action(
			'blocksy:woocommerce:product-card:custom:layer',
			function ($atts) {
				$this->render_dynamic_field_layer($atts);
			}
		);

		add_action(
			'blocksy:post-meta:render-meta',
			[$this, 'render_acf_meta'],
			10, 3
		);

		add_action(
			'blocksy:hero:element:render',
			[$this, 'render_dynamic_field_layer']
		);

		add_filter(
			'blocksy:archive:render-card-layer',
			function ($output, $atts) {
				$maybe_layer = $this->render_dynamic_field_layer($atts, false);

				if (! empty($maybe_layer)) {
					return $maybe_layer;
				}

				return $output;
			},
			10, 2
		);
	}

	public function get_product_layer_default($layers) {
		$opt = $this->complement_layers_option(
			[
				'value' => [],
				'settings' => []
			],
			'product',
			[
				'has_icon' => false,
				'has_label_option' => true,
				'has_spacing' => true
			]
		);

		foreach ($opt['value'] as $layer) {
			$layers[] = $layer;
		}

		return $layers;
	}

	public function get_product_layer_extra($layers) {
		foreach ($this->complement_layers_option(
			[
				'value' => [],
				'settings' => []
			],
			'product',
			[
				'has_icon' => false,
				'has_label_option' => true,
				'has_spacing' => true
			]
		)['settings'] as $id => $layer) {
			$layers[$id] = $layer;
		}

		return $layers;
	}

	public function retrieve_dynamic_data_fields($args = []) {
		$args = wp_parse_args($args, [
			'prefix' => 'single_blog_post',
			'post_type' => null,
			'provider' => 'acf',
			'allow_images' => false
		]);

		$post_type = null;

		if ($args['prefix']) {
			$post_type = 'post';

			if ($args['prefix'] === 'product') {
				$post_type = 'product';
			}

			if ($args['prefix'] === 'single_page') {
				$post_type = 'page';
			}

			$post_types = blocksy_manager()->post_types->get_supported_post_types();

			foreach ($post_types as $single_post_type) {
				if (
					$args['prefix'] === $single_post_type . '_archive'
					||
					$args['prefix'] === $single_post_type . '_single'
				) {
					$post_type = $single_post_type;
				}
			}
		}

		if ($args['post_type']) {
			$post_type = $args['post_type'];
		}

		$result = [];

		if ($args['provider'] === 'acf') {
			if (! function_exists('acf_get_field_groups')) {
				return null;
			}

			$acf_fields = [];

			foreach (acf_get_field_groups([
				'post_type' => $post_type
			]) as $acf_group) {
				$fields = acf_get_fields($acf_group['key']);

				foreach ($fields as $field) {
					$acf_fields[] = $field;
				}
			}

			foreach (acf_get_raw_field_groups() as $acf_group) {
				if (! isset($acf_group['location'])) {
					continue;
				}

				$has_matching_location = false;

				foreach ($acf_group['location'] as $single_location) {
					foreach ($single_location as $rule) {
						if (
							$rule['param'] === 'post_type'
							&&
							$rule['operator'] === '=='
							&&
							$rule['value'] === $post_type
						) {
							$has_matching_location = true;
							continue;
						}

						if (
							$rule['param'] !== 'post_taxonomy'
							&&
							$rule['param'] !== 'post_category'
						) {
							continue;
						}

						$tax = explode(':', $rule['value'])[0];

						$all_tax = get_object_taxonomies($post_type);

						if (! in_array($tax, $all_tax)) {
							continue;
						}

						$has_matching_location = true;
					}
				}

				if (! $has_matching_location) {
					continue;
				}

				$fields = acf_get_fields($acf_group['key']);

				foreach ($fields as $field) {
					$acf_fields[] = $field;
				}
			}

			foreach ($acf_fields as $field) {
				if ($field['type'] === 'repeater') {
					continue;
				}

				if (! $args['allow_images'] && $field['type'] === 'image') {
					continue;
				}

				if (
					$field['type'] === 'group'
					&&
					! empty($field['sub_fields'])
				) {
					foreach ($field['sub_fields'] as $sub_field) {
						$result[
							$field['name'] . '_' . $sub_field['name']
						] = $field['label'] . ' - ' . $sub_field['label'];
					}

					continue;
				}

				$result[$field['name']] = $field['label'];
			}

			$result = apply_filters(
				'blocksy:pro:post-types-extra:acf:collect-fields',
				$result,
				$acf_fields
			);
		}

		if ($args['provider'] === 'metabox') {
			if (! function_exists('rwmb_get_object_fields')) {
				return null;
			}

			foreach (array_values(rwmb_get_object_fields($post_type)) as $f) {
				$result[$f['id']] = $f['name'];
			}
		}

		if ($args['provider'] === 'custom') {
			$result = blc_dynamic_data_get_custom_fields($post_type);
		}

		if ($args['provider'] === 'toolset') {
			if (! function_exists('types_render_field')) {
				return null;
			}

			foreach (array_values(wpcf_admin_fields_get_active_fields_by_post_type(
				$post_type
			)) as $f) {
				if (! is_array($f)) {
					continue;
				}

				$result[$f['id']] = $f['name'];
			}
		}

		if ($args['provider'] === 'jetengine') {
			if (! function_exists('jet_engine')) {
				return null;
			}

			foreach (jet_engine()->meta_boxes->meta_fields as $cpt => $meta_fields) {
				if ($cpt !== $post_type) {
					continue;
				}

				foreach ($meta_fields as $jet_field) {
					$result[$jet_field['name']] = $jet_field['title'];
				}
			}
		}

		if ($args['provider'] === 'pods') {
			if (! function_exists('pods')) {
				return null;
			}

			$pods = pods($post_type);
			$fields = pods_config_get_all_fields($pods->data->pod_data);;

			foreach ($fields as $key => $value) {
				$result[$key] = $value['label'];
			}
		}

		return $result;
	}

	public function render_acf_meta($id, $meta, $args) {
		$field = $this->get_field_to_render($meta);

		if (! $field) {
			return;
		}

		$value_fallback = blocksy_akg('value_fallback', $meta, '');

		$value = $field['value'];

		$has_fallback = false;

		if (empty($value) && ! empty($value_fallback)) {
			$has_fallback = true;
			$value = do_shortcode($value_fallback);
		}

		if (! is_string($value)) {
			return;
		}

		if (empty(trim($value))) {
			return;
		}

		$value_after = blocksy_akg('value_after', $meta, '');
		$value_before = blocksy_akg('value_before', $meta, '');

		if (! empty($value_after) && ! $has_fallback) {
			$value .= $value_after;
		}

		if (! empty($value_before) && ! $has_fallback) {
			$value = $value_before . $value;
		}

		if ($args['meta_type'] === 'label') {
			$value = '<span>' . $field['label'] . '</span>' . $value;
		}

		if ($args['meta_type'] === 'icons' || $args['force_icons']) {
			$value = blc_get_icon([
				'icon_descriptor' => blocksy_akg('icon', $meta, [
					'icon' => 'blc blc-heart'
				]),
				'icon_container' => false
			]) . $value;
		}

		$value = apply_filters(
			'blocksy:pro:post-types-extra:post-meta:rendered-value',
			$value,
			$field,
			$meta
		);

		echo blocksy_html_tag(
			'li',
			[
				'class' => 'meta-custom-field',
				'data-field' => $field['name']
			],
			$value
		);
	}

	public function complement_layers_option($option, $prefix, $args = []) {
		$args = wp_parse_args($args, [
			'has_icon' => false,
			'has_label_option' => true,
			'has_spacing' => false
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'acf',
			'provider_label' => 'ACF',

			'prefix' => $prefix
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'metabox',
			'provider_label' => 'MetaBox',

			'prefix' => $prefix
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'toolset',
			'provider_label' => 'Toolset',

			'prefix' => $prefix
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'jetengine',
			'provider_label' => 'Jet Engine',

			'prefix' => $prefix
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'pods',
			'provider_label' => 'Pods',

			'prefix' => $prefix
		]);

		$option = $this->complement_option_for($option, [
			'has_icon' => $args['has_icon'],
			'has_label_option' => $args['has_label_option'],
			'has_spacing' => $args['has_spacing'],

			'provider' => 'custom',
			'provider_label' => __('Custom', 'blocksy-companion'),

			'prefix' => $prefix
		]);

		return $option;
	}

	public function complement_option_for($option, $args = []) {
		$args = wp_parse_args($args, [
			'provider' => 'acf',
			'provider_label' => 'ACF',

			'has_icon' => false,
			'has_label_option' => true,
			'has_spacing' => false,

			'prefix' => ''
		]);

		$fields = $this->retrieve_dynamic_data_fields([
			'prefix' => $args['prefix'],
			'provider' => $args['provider']
		]);

		if (! $fields) {
			return $option;
		}

		$option['value'][] = [
			'id' => $args['provider'] . '_field',
			'enabled' => false
		];

		$options = [
			'text' => [
				'label' => ' ',
				'type' => 'html',
				'html' => blc_safe_sprintf(
					__(
						'You have no %s fields declared for this custom post type.',
						'blocksy-companion'
					),
					$args['provider_label']
				)
			]
		];

		if (count($fields) > 0) {
			$options = [
				'field' => [
					'label' => __('Field', 'blocksy-companion'),
					'type' => 'ct-select',
					'view' => 'text',
					'value' => array_keys($fields)[0],
					'design' => 'inline',
					'choices' => $fields,
				]
			];

			if ($args['has_label_option']) {
				$options['label'] = [
					'type' => 'ct-switch',
					'label' => __('Label', 'blocksy-companion'),
					'design' => 'inline',
					'value' => 'no'
				];
			}

			if ($args['has_icon']) {
				$options[blocksy_rand_md5()] = [
					'type' => 'ct-condition',
					'condition' => [ 'meta_type' => 'icons' ],
					'values_source' => 'parent',
					'options' => [
						'icon' => [
							'type' => 'icon-picker',
							'label' => __('Icon', 'blocksy-companion'),
							'design' => 'inline',
							'value' => [
								'icon' => 'blc blc-heart'
							]
						]
					],
				];
			}

			$options['value_before'] = [
				'type' => 'text',
				'label' => __('Before', 'blocksy-companion'),
				'design' => 'inline',
				'value' => '',
				'sync' => [
					'prefix' => $args['prefix'],
					'id' => $args['prefix'] . '_dynamic_data_sync',
				]
			];

			$options['value_after'] = [
				'type' => 'text',
				'label' => __('After', 'blocksy-companion'),
				'design' => 'inline',
				'value' => '',

				'sync' => [
					'prefix' => $args['prefix'],
					'id' => $args['prefix'] . '_dynamic_data_sync',
				]
			];

			$options['value_fallback'] = [
				'type' => 'text',
				'label' => __('Fallback', 'blocksy-companion'),
				'design' => 'inline',
				'value' => '',
				'sync' => [
					'prefix' => $args['prefix'],
					'id' => $args['prefix'] . '_dynamic_data_sync',
				]
			];

			if ($args['has_spacing']) {
				$options['spacing'] = [
					'label' => __( 'Bottom Spacing', 'blocksy' ),
					'type' => 'ct-slider',
					'min' => 0,
					'max' => 100,
					'value' => $args['prefix'] === 'product' ? 10 : 20,
					'responsive' => true,

					'sync' => [
						'id' => 'woo_card_layout_skip'
					]
				];
			}
		}

		$option['settings'][$args['provider'] . '_field'] = [
			'label' => blc_safe_sprintf(
				__('%s Field', 'blocksy-companion'),
				$args['provider_label']
			) . ' INDEX',
			'options' => $options,
			'clone' => 15
		];

		return $option;
	}

	public function render_dynamic_field_layer($atts, $echo = true) {
		$field = $this->get_field_to_render($atts);

		if (! $field) {
			return '';
		}

		$output = $field['value'];

		$value_fallback = blocksy_akg('value_fallback', $atts, '');

		$has_fallback = false;

		if (empty($output) && ! empty($value_fallback)) {
			$has_fallback = true;
			$output = do_shortcode($value_fallback);
		}

		if (empty($output)) {
			return '';
		}

		$value_after = blocksy_akg('value_after', $atts, '');
		$value_before = blocksy_akg('value_before', $atts, '');

		if (! empty($value_after) && ! $has_fallback) {
			$output .= $value_after;
		}

		if (! empty($value_before) && ! $has_fallback) {
			$output = $value_before . $output;
		}

		if (blocksy_akg('label', $atts, 'no') === 'yes') {
			$output = '<span>' . $field['label'] . '</span>' . $output;
		}

		$attr = [
			'class' => 'ct-dynamic-data-layer'
		];

		$attr['data-field'] = $field['name'];

		if (isset($atts['__id'])) {
			$attr['data-field'] .= ':' . substr($atts['__id'], 0, 6);
		}

		$layer = blocksy_html_tag('div', $attr, $output);

		if ($echo) {
			echo $layer;
		}

		return $layer;
	}

	public function get_field_to_render($atts, $args = []) {
		$args = wp_parse_args($args, [
			'post_id' => get_the_ID(),
			'post_type' => get_post_type(),

			'allow_images' => false
		]);

		$provider = null;

		if ($atts['id'] === 'acf_field') {
			$provider = 'acf';
		}

		if ($atts['id'] === 'metabox_field') {
			$provider = 'metabox';
		}

		if ($atts['id'] === 'toolset_field') {
			$provider = 'toolset';
		}

		if ($atts['id'] === 'jetengine_field') {
			$provider = 'jetengine';
		}

		if ($atts['id'] === 'custom_field') {
			$provider = 'custom';
		}

		if ($atts['id'] === 'pods_field') {
			$provider = 'pods';
		}

		if (! $provider) {
			return null;
		}

		$fields = $this->retrieve_dynamic_data_fields([
			'post_type' => $args['post_type'],
			'provider' => $provider,
			'allow_images' => $args['allow_images']
		]);

		if (empty($fields)) {
			return null;
		}

		$field = null;

		if (! isset($atts['field']) && ! empty($fields)) {
			$atts['field'] = array_keys($fields)[0];
		}

		if (
			isset($atts['field'])
			&&
			$atts['field']
			&&
			isset($fields[$atts['field']])
			&&
			$fields[$atts['field']]
		) {
			$field = $atts['field'];
		}

		if (! $field) {
			return null;
		}

		if ($provider === 'acf') {
			if (! function_exists('get_field_object')) {
				return null;
			}

			$field = [
				'name' => $field,
				'label' => $fields[$field]
			];

			if (strpos($field['name'], '_') !== false) {
				$maybe_group = get_field_object(
					explode('_', $field['name'])[0],
					$args['post_id']
				);

				if (
					$maybe_group
					&&
					isset($maybe_group['sub_fields'])
				) {
					foreach ($maybe_group['sub_fields'] as $sub_field) {
						if ($maybe_group['name'] . '_' . $sub_field['name'] === $field['name']) {
							$field['label'] = $sub_field['label'];
						}
					}
				}
			}

			$field_value = get_field($field['name'], $args['post_id']);

			if (! is_array($field_value)) {
				$field_value = [$field_value];
			}

			// TODO: maybe introduce arg for raw value of the field.
			// Also, this file is too big. It needs to be refactored.

			if (is_array($field_value)) {
				$field_descriptor = get_field_object(
					$field['name'],
					$args['post_id']
				);

				if (! $field_descriptor) {
					$field_descriptor = acf_get_field($field['name']);
				}

				if (
					$field_descriptor
					&&
					$field_descriptor['type'] === 'image'
				) {
					if (! $args['allow_images']) {
						$field_value = '';

						if (
							is_string($field_descriptor['value'])
							||
							(
								is_array($field_descriptor['value'])
								&&
								isset($field_descriptor['value']['url'])
								&&
								! empty($field_descriptor['value']['url'])
							)
						) {
							$field_value = $field_descriptor['value'];
						}
					} else {
						$field_value = $field_descriptor;

						if (isset($field_value[0])) {
							$field_value = array_merge(
								$field_descriptor,
								is_array($field_value[0]) ? $field_value[0] : []
							);
						} else {
							$field_value = $field_descriptor;
						}
					}
				} else {
					$mapped_value = [];

					foreach ($field_value as $single_field) {
						if (is_object($single_field) && get_class($single_field) === 'WP_Term') {
							$mapped_value[] = blocksy_html_tag(
								'a',
								[
									'href' => get_term_link($single_field, $single_field->taxonomy)
								],
								$single_field->name
							);
						} else {
							$mapped_value[] = $single_field;
						}
					}

					$field_value = $mapped_value;

					if (
						$field_descriptor
						&&
						isset($field_descriptor['choices'])
						&&
						! empty($field_descriptor['choices'])
					) {
						$mapped_value = [];

						foreach (array_values($field_value) as $single_field) {
							if (
								isset($field_descriptor['choices'][$single_field])
							) {
								$mapped_value[] = $field_descriptor[
									'choices'
								][$single_field];
							} else {
								$mapped_value[] = $single_field;
							}
						}

						$field_value = $mapped_value;
					}

					$field_value_result = [];

					foreach ($field_value as $index => $single_field_value) {
						if (
							is_string($single_field_value)
							&&
							! empty($single_field_value)
						) {
							$field_value_result[] = $single_field_value;
						}
					}

					$field_value = implode(', ', array_values($field_value_result));
				}
			}

			$value = apply_filters(
				'blocksy:pro:post-types-extra:acf:field-value-render',
				$field_value,
				$field['name'],
				$field
			);

			return [
				'name' => $field['name'],
				'value' => $value,
				'label' => $field['label'],
				'provider' => $provider
			];
		}

		if ($provider === 'metabox') {
			if (! function_exists('rwmb_get_field_settings')) {
				return null;
			}

			$field = rwmb_get_field_settings($field, null, $args['post_id']);

			if (! $field) {
				return null;
			}

			$value = apply_filters(
				'blocksy:pro:post-types-extra:metabox:field-value-render',
				rwmb_the_value($field['id'], [], $args['post_id'], false),
				$field['name'],
				$field
			);

			return [
				'name' => $field['name'],
				'value' => $value,
				'label' => $field['name'],
				'provider' => $provider
			];
		}

		if ($provider === 'toolset') {
			if (! function_exists('types_render_field')) {
				return null;
			}

			$all_fields = wpcf_admin_fields_get_active_fields_by_post_type(
				$args['post_type']
			);

			if (isset($all_fields[$field])) {
				$field = $all_fields[$field];
			} else {
				$field = null;
			}

			if (! $field) {
				return null;
			}

			$value = apply_filters(
				'blocksy:pro:post-types-extra:toolset:field-value-render',
				types_render_field($field['id'], [
					'post_id' => $args['post_id']
				]),
				$field['name'],
				$field
			);

			return [
				'name' => $field['name'],
				'value' => $value,
				'label' => $field['name'],
				'provider' => $provider
			];
		}

		if ($provider === 'jetengine') {
			if (! function_exists('jet_engine')) {
				return null;
			}

			$post_type = $args['post_type'];

			$all_fields = [];

			foreach (jet_engine()->meta_boxes->meta_fields as $cpt => $meta_fields) {
				if ($cpt !== $post_type) {
					continue;
				}

				foreach ($meta_fields as $jet_field) {
					$all_fields[$jet_field['name']] = $jet_field;
				}
			}

			if (isset($all_fields[$field])) {
				$field = $all_fields[$field];
			} else {
				$field = null;
			}

			if (! $field) {
				return null;
			}

			$value = apply_filters(
				'blocksy:pro:post-types-extra:jetengine:field-value-render',
				get_post_meta($args['post_id'], $field['name'], true),
				$field['title'],
				$field
			);

			return [
				'name' => $field['name'],
				'value' => $value,
				'label' => $field['name'],
				'provider' => $provider
			];
		}

		if ($provider === 'custom') {
			$all_fields = blc_dynamic_data_get_custom_fields($args['post_type']);

			if (! isset($all_fields[$field])) {
				return null;
			}

			$field = [
				'id' => $field,
				'name' => $all_fields[$field]
			];

			$value = apply_filters(
				'blocksy:pro:post-types-extra:custom:field-value-render',
				get_post_meta($args['post_id'], $field['id'], true),
				$field['name'],
				$field
			);

			return [
				'name' => $field['name'],
				'value' => $value,
				'label' => $field['name'],
				'provider' => $provider
			];
		}

		if ($provider === 'pods') {
			if (! function_exists('pods')) {
				return null;
			}

			$pods = pods($args['post_type'], $args['post_id']);
			$all_fields = pods_config_get_all_fields($pods->data->pod_data);

			if (! isset($all_fields[$field])) {
				return null;
			}

			return [
				'name' => $field,
				'value' => $pods->display( $field ),
				'label' => $all_fields[$field]['label'],
				'provider' => $provider
			];
		}

		return [
			'name' => $field,
			'value' => $field,
			'label' => $field,
			'provider' => $provider
		];
	}

	public function add_design_options($opts, $prefix, $key) {
		$fields = [
			[
				'id' => 'acf_field',
				'title' => 'ACF'
			],

			[
				'id' => 'metabox_field',
				'title' => 'MetaBox'
			],

			[
				'id' => 'toolset_field',
				'title' => 'Toolset'
			],

			[
				'id' => 'jetengine_field',
				'title' => 'Jet Engine'
			],

			[
				'id' => 'pods_field',
				'title' => 'Pods'
			],

			[
				'id' => 'custom_field',
				'title' => __('Custom Field', 'blocksy-companion')
			]
		];

		foreach ($fields as $single_field) {
			$opts[$single_field['id'] . '_' . $key] = [
				'type' => 'ct-layers-mirror',
				'layers' => $prefix . '_' . $key,
				'field' => $single_field['id'],
				'value' => '',
				'inner-options' => [
					'typography' => [
						'type' => 'ct-typography',
						'label' => blc_safe_sprintf(
							__('%s %s Font', 'blocksy-companion'),
							$single_field['title'],
						   __('Field', 'blocksy-companion') . ' INDEX'
						),
						'divider' => 'top:full',
						'sync' => 'live',
						'value' => blocksy_typography_default_values([]),
					],

					'color' => [
						'label' => blc_safe_sprintf(
							__('%s %s Color', 'blocksy-companion'),
							$single_field['title'],
						   __('Field', 'blocksy-companion') . ' INDEX'
						),
						'type'  => 'ct-color-picker',
						'design' => 'inline',
						'noColor' => [ 'background' => 'var(--theme-text-color)'],
						'sync' => 'live',
						'value' => [
							'default' => [
								'color' => \Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],

							'hover' => [
								'color' => \Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],
						],

						'pickers' => [
							[
								'title' => __( 'Initial', 'blocksy-companion' ),
								'id' => 'default',
								'inherit' => 'var(--theme-text-color)'
							],

							[
								'title' => __( 'Hover', 'blocksy-companion' ),
								'id' => 'hover',
								'inherit' => 'var(--theme-link-hover-color)'
							],
						],
					],
				]
			];
		}

		return $opts;
	}
}

function blc_dynamic_data_get_custom_fields($post_type) {
	$all_fields = apply_filters(
		'blocksy:pro:post-types-extra:custom:collect-fields',
		[],
		$post_type
	);

	return $all_fields;
}
