<?php

global $TRP_LANGUAGE;

$settings = new TRP_Settings();

$settings_array = $settings->get_settings();

$trp = TRP_Translate_Press::get_trp_instance();

$trp_lang_switcher = new TRP_Language_Switcher(
	$settings->get_settings(),
	TRP_Translate_Press::get_trp_instance()
);

$trp_languages = $trp->get_component('languages');

$url_converter = $trp->get_component('url_converter');

if (current_user_can(apply_filters(
	'trp_translating_capability',
	'manage_options'
))) {
	$languages_to_display = $settings_array['translation-languages'];
} else {
	$languages_to_display = $settings_array['publish-languages'];
}

$published_languages = $trp_languages->get_language_names(
	$languages_to_display
);

$current_language = array();

foreach ($published_languages as $code => $name) {
	if ($code == $TRP_LANGUAGE) {
		$current_language['code'] = $code;
		$current_language['name'] = $name;
	}
}

$current_language = apply_filters(
	'trp_ls_shortcode_current_language',
	$current_language,
	$published_languages,
	$TRP_LANGUAGE,
	$settings_array
);

$list_class = 'class="ct-language trp-language-switcher-container"';

if ($ls_type === 'dropdown') {
	$list_class = 'class="trp-language-switcher-container"';

	echo '<div class="ct-language ct-active-language" tabindex="0">';

	if (
		isset($top_level_language_type['custom_icon'])
		&&
		$top_level_language_type['custom_icon']
	) {
		echo $top_level_icon;
	}

	if ($top_level_language_type['icon']) {
		echo $trp_lang_switcher->add_flag(
			$current_language['code'],
			$current_language['name']
		);
	}

	if ($top_level_language_type['label']) {
		echo '<span>';

		if ($top_level_language_label === 'long') {
			echo $current_language['name'];
		} else {
			echo strtoupper($url_converter->get_url_slug(
				$current_language['code'],
				false
			));
		}

		echo '</span>';
	}

	if ($has_arrow) {
		echo '<svg class="ct-icon ct-dropdown-icon" width="8" height="8" viewBox="0 0 15 15"><path d="M2.1,3.2l5.4,5.4l5.4-5.4L15,4.3l-7.5,7.5L0,4.3L2.1,3.2z"></path></svg>';
	}

	echo '</div>';
}

echo '<ul ' . $list_class . '>';

foreach ($published_languages as $code => $lang) {
	if ($current_language['code'] === $code) {
		if ($hide_current_language) {
			continue;
		}

		echo '<li class="current-lang">';
	} else {
		echo '<li>';
	}

	$url = $url_converter->get_url_for_language($code, false);

	echo '<a href="' . $url . '" aria-label="' . $lang . '">';

	if ($language_type['icon']) {
		echo $trp_lang_switcher->add_flag($code, $lang);
	}

	if ($language_type['label']) {
		echo '<span>';

		if ($language_label === 'long') {
			echo $lang;
		} else {
			echo strtoupper($url_converter->get_url_slug($code, false));
		}

		echo '</span>';
	}

	echo '</a>';

	echo '</li>';
}

echo '</ul>';
