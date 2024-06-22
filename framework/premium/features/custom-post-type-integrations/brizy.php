<?php

namespace Blocksy\CustomPostType\Integrations;

class Brizy extends \Blocksy\CustomPostTypeRenderer {
	public function get_content($args = []) {
		try {
			if (
				in_array(
					get_post_type($this->id),
					\Brizy_Editor::get()->supported_post_types()
				)
				&&
				\Brizy_Editor_Post::get($this->id)->uses_editor()
			) {
				$brizy = \Brizy_Editor_Post::get($this->id);

				return apply_filters(
					'brizy_content',
					do_shortcode($brizy->get_compiled_page()->get_body()),
					\Brizy_Editor_Project::get(),
					$brizy->getWpPost()
				);
			}
		} catch (\Exception $e) {
		}

		return \Blocksy\CustomPostTypeRenderer::NOT_IMPLEMENTED;
	}

	public function pre_output() {
		if (
			!  in_array(
				get_post_type($this->id),
				\Brizy_Editor::get()->supported_post_types()
			) || ! \Brizy_Editor_Post::get($this->id)->uses_editor()
		) {
			return;
		}

		try {
			$brizy_post = \Brizy_Editor_Post::get($this->id);
		} catch (\Brizy_Editor_Exceptions_Exception | \Exception $e) {
			return;
		}

		global $post;

		$post = get_post($this->id);

		setup_postdata($post);

		$is_preview = is_preview() || isset($_GET['preview']);
		$needs_compile = !$brizy_post->isCompiledWithCurrentVersion() || $brizy_post->get_needs_compile();
		$autosaveId = null;

		if ($is_preview) {
			$user_id = get_current_user_id();
			$postParentId = $brizy_post->getWpPostId();
			$autosaveId = \Brizy_Editor_AutoSaveAware::getAutoSavePost($postParentId, $user_id);

			if ($autosaveId) {
				$brizy_post = \Brizy_Editor_Post::get($autosaveId);
				$needs_compile = !$brizy_post->isCompiledWithCurrentVersion() || $brizy_post->get_needs_compile();
			} else {
				// we make this false because the page was saved.
				$is_preview = false;
			}
		}

		try {
			if ($is_preview || $needs_compile) {
				$brizy_post->compile_page();
			}

			if (!$is_preview && $needs_compile || $autosaveId) {
				$brizy_post->saveStorage();
				$brizy_post->savePost();
			}
		} catch (\Exception $e) {
			\Brizy_Logger::instance()->exception($e);
		}

		try {
			if (
				in_array(get_post_type($this->id), \Brizy_Editor::get()->supported_post_types())
				&&
				\Brizy_Editor_Post::get($this->id)->uses_editor()
			) {
				$brizy_element = \Brizy_Editor_Post::get($this->id);

				$brizy_class = \Brizy_Public_Main::get($brizy_element);

				add_action('wp_head', array($brizy_class, 'insert_page_head'));

				add_action(
					'wp_enqueue_scripts',
					[$brizy_class, '_action_enqueue_preview_assets'],
					9999
				);

				add_filter(
					'body_class',
					function ($classes) {
						$classes[] = 'brz';
						$classes[] = ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) ? 'brz-is-mobile' : '';

						return $classes;
					}
				);

				add_action('wp_head', function() use ($brizy_element) {
					$brizy_project = \Brizy_Editor_Project::get();

					$brizy_html = new \Brizy_Editor_CompiledHtml(
						$brizy_element->get_compiled_html()
					);

					echo apply_filters(
						'brizy_content',
						$brizy_html->get_head(),
						$brizy_project,
						$brizy_element->get_wp_post()
					);
				});
			}
		} catch (\Exception $e) {
		}

		wp_reset_postdata();
	}
}

