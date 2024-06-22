<?php

$options = [

	'media_video_source' => [
		'label' => __( 'Video Source', 'blocksy-companion' ),
		'type' => 'ct-radio',
		'value' => 'upload',
		'view' => 'text',
		'design' => 'block',
		'setting' => [ 'transport' => 'postMessage' ],
		'choices' => [
			'upload' => __( 'Upload', 'blocksy-companion' ),
			'youtube' => __( 'YouTube', 'blocksy-companion' ),
			'vimeo' => __( 'Vimeo', 'blocksy-companion' ),
		],
	],

	blocksy_rand_md5() => [
		'type' => 'ct-condition',
		'condition' => [ 'media_video_source' => 'upload' ],
		'options' => [

			'media_video_upload' => [
				'label' => __( 'Upload Video', 'blocksy-companion' ),
				'type' => 'ct-file-uploader',
				'value' => '',
                'mediaType' => 'video',
                'desc' => __( 'Upload an MP4 file into the media library.', 'blocksy-companion' ),
			],

		],
	],

	blocksy_rand_md5() => [
		'type' => 'ct-condition',
		'condition' => [ 'media_video_source' => 'youtube' ],
		'options' => [

			'media_video_youtube_url' => [
				'type' => 'text',
				'label' => __( 'YouTube Url', 'blocksy-companion' ),
				'design' => 'block',
				'desc' => __( 'Enter a valid YouTube media URL.', 'blocksy-companion' ),
			],

		],
	],

	blocksy_rand_md5() => [
		'type' => 'ct-condition',
		'condition' => [ 'media_video_source' => 'vimeo' ],
		'options' => [

			'media_video_vimeo_url' => [
				'type' => 'text',
				'label' => __( 'Vimeo Url', 'blocksy-companion' ),
				'design' => 'block',
				'desc' => __( 'Enter a valid Vimeo media URL.', 'blocksy-companion' ),
			],

		],
	],

	blocksy_rand_md5() => [
		'type' => 'ct-condition',
		'condition' => [ 'media_video_source' => 'youtube' ],
		'options' => [

			'media_video_youtube_nocookies' => [
				'type'  => 'ct-switch',
				'label' => __( 'YouTube Privacy Enhanced Mode', 'blocksy-companion' ),
				'value' => 'no',
				'divider' => 'top',
				'desc' => blc_safe_sprintf(
					// translators: placeholder here means the actual URL.
					__( "YouTube won't store information about visitors on your website unless they play the video. More info about this can be found %shere%s.", 'blocksy-companion' ),
					blc_safe_sprintf(
						'<a href="%s" target="_blank">',
						'https://support.google.com/youtube/answer/171780?hl=en#zippy=%2Cturn-on-privacy-enhanced-mode'
					),
					'</a>'
				),
			],

		],
	],

	'media_video_autoplay' => [
		'type'  => 'ct-switch',
		'label' => __( 'Autoplay Video', 'blocksy-companion' ),
		'value' => 'no',
		'divider' => 'top',
		'desc' => __( 'Automatically start video playback after the gallery is loaded.', 'blocksy-companion' ),
	],

	'media_video_loop' => [
		'type'  => 'ct-switch',
		'label' => __( 'Loop Video', 'blocksy-companion' ),
		'value' => 'no',
		'divider' => 'top',
		'desc' => __( 'Start video again after it ends.', 'blocksy-companion' ),
	],

	'media_video_player' => [
		'type'  => 'ct-switch',
		'label' => __( 'Simplified Player', 'blocksy-companion' ),
		'value' => 'no',
		'divider' => 'top',
		'desc' => __( 'Display a minimalistic view of the video player.', 'blocksy-companion' ),
	],

	blocksy_rand_md5() => [
		'type' => 'ct-condition',
		'condition' => [ 'media_video_player' => 'yes' ],
		'options' => [

			'media_video_size' => [
				'label' => __( 'Video Size', 'blocksy-companion' ),
				'type' => 'ct-radio',
				'value' => 'contain',
				'view' => 'text',
				'design' => 'block',
				'divider' => 'top',
				'setting' => [ 'transport' => 'postMessage' ],
				'choices' => [
					'contain' => __( 'Contain', 'blocksy-companion' ),
					'cover' => __( 'Cover', 'blocksy-companion' ),
				],
				'desc' => blc_safe_sprintf(
					// translators: placeholder here means the actual URL.
					__( "Choose how the video will fill its container. More info about this can be found %shere%s.", 'blocksy-companion' ),
					blc_safe_sprintf(
						'<a href="%s" target="_blank">',
						'https://creativethemes.com/blocksy/docs/modules/featured-videos/#video-embedding-options'
					),
					'</a>'
				),
			],

		],
	],	
];

