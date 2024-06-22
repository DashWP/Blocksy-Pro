<?php

namespace Blocksy;

class MediaVideo {
	public function __construct() {
		add_action(
			'admin_enqueue_scripts',
			function () {
				$options = blocksy_get_options(
					dirname(__FILE__) . '/options.php',
					[],
					false
				);

				wp_localize_script(
					'blocksy-admin-scripts',
					'videoOptions',
					[
						'options' => $options,
					]
				);
			},
			999
		);

		add_action('wp_ajax_blocksy_update_video_meta_fields', function () {
			if (! current_user_can('manage_options')) {
				wp_send_json_error();
			}

			if (! isset($_POST['attachment_id'])) {
				wp_send_json_error();
			}

			update_post_meta(
				$_POST['attachment_id'],
				'blocksy_post_meta_options',
				json_decode(stripslashes($_POST['attachment_video']), true)
			);

			delete_post_meta($_POST['attachment_id'], 'blocksy_media_video');

			$maybe_new_meta = blocksy_get_post_options($_POST['attachment_id']);

			if ($maybe_new_meta) {
				wp_send_json_success(
					[
						'meta' => $maybe_new_meta
					]
				);
			}
		});

		add_action('wp_ajax_blocksy_get_video_meta_fields', function () {
			if (! isset($_GET['attachment_id'])) {
				wp_send_json_error();
			}

			$maybe_old_meta = get_post_meta(
				$_GET['attachment_id'],
				'blocksy_media_video',
				true
			);

			if ($maybe_old_meta) {
                if (
                    strpos($maybe_old_meta, 'youtube') !== false
                    ||
                    strpos($maybe_old_meta, 'youtu.be') !== false
                ) {
                    wp_send_json_success(
                        [
                            'meta' => [
                                'media_video_youtube_url' => $maybe_old_meta,
                                'media_video_source' => 'youtube'
                            ],
                        ]
                    );

                    return;
                }

                if ( strpos($maybe_old_meta, 'vimeo') !== false ) {
                    wp_send_json_success(
                        [
                            'meta' => [
                                'media_video_vimeo_url' => $maybe_old_meta,
                                'media_video_source' => 'vimeo'
                            ],
                        ]
                    );

                    return;
                }

                $maybe_old_attachment = attachment_url_to_postid($maybe_old_meta);

                if ( $maybe_old_attachment ) {
                    wp_send_json_success(
                        [
                            'meta' => [
                                'media_video_upload' => $maybe_old_meta,
                                'media_video_source' => 'upload'
                            ],
                        ]
                    );

                    return;
                }
			}

			$maybe_new_meta = blocksy_get_post_options($_GET['attachment_id']);

			if ($maybe_new_meta) {
				wp_send_json_success(
					[
						'meta' => $maybe_new_meta,
					]
				);
			}

			return;
		});
	}
}
