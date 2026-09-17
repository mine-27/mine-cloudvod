<?php
namespace MineCloudvod\LMS\Blocks;

class BlockTemplateUtils{

	const PLUGIN_SLUG = 'mine-cloudvod/mine-cloudvod';

	public static function build_template_result_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'mine-cloudvod' ) );
		}

		$theme          = $terms[0]->name;
		$has_theme_file = true;

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $theme . '//' . $post->post_name;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = $has_theme_file;
		$template->is_custom      = false;
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );
			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		// We are checking 'woocommerce' to maintain classic templates which are saved to the DB,
		// prior to updating to use the correct slug.
		// More information found here: https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5423.
		if ( self::PLUGIN_SLUG === $theme ) {
			$template->origin = 'plugin';
		}

		return $template;
	}


	public static function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = array(
			'slug'        => $template_slug,
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => self::get_block_template_title( $template_slug ),
			'description' => self::get_block_template_description( $template_slug ),
			'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
		);

		return (object) $new_template_item;
	}

	public static function flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}
	public static function inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = self::flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	public static function build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
		$template->content = self::inject_theme_attribute_in_content( $template_content );
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::get_block_template_title( $template_file->slug );
		$template->description    = ! empty( $template_file->description ) ? $template_file->description : self::get_block_template_description( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';
		return $template;
	}
	public static function get_template_paths( $base_directory ) {
		$path_list = array();
		if ( file_exists( $base_directory ) ) {
			$nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_directory ) );
			$nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
			foreach ( $nested_html_files as $path => $file ) {
				$path_list[] = $path;
			}
		}
		return $path_list;
	}
	public static function generate_template_slug_from_path( $path ) {
		$template_extension = '.html';

		return basename( $path, $template_extension );
	}
	public static function theme_has_template( $template_name ) {
		return ! ! self::get_theme_template_path( $template_name, 'wp_template' );
	}
	public static function get_theme_template_path( $template_slug, $template_type = 'wp_template' ) {
		$template_filename = $template_slug . '.html';

		// 可能的根目录名：按模板类型取区块主题目录，并兼容历史 Gutenberg 的 block-* 命名。
		$root_directories = 'wp_template_part' === $template_type
			? array( 'parts', 'block-parts' )
			: array( 'templates', 'block-templates' );

		// 优先子主题（stylesheet），其次父主题（template）。
		foreach ( $root_directories as $root_directory ) {
			$candidate_paths = array(
				get_stylesheet_directory() . DIRECTORY_SEPARATOR . $root_directory . DIRECTORY_SEPARATOR . $template_filename,
				get_template_directory() . DIRECTORY_SEPARATOR . $root_directory . DIRECTORY_SEPARATOR . $template_filename,
			);

			foreach ( $candidate_paths as $candidate_path ) {
				if ( is_readable( $candidate_path ) ) {
					return $candidate_path;
				}
			}
		}

		return null;
	}

	public static function set_has_theme_file_if_fallback_is_available( $query_result, $template ) {
		foreach ( $query_result as &$query_result_template ) {
			if (
				$query_result_template->slug === $template->slug
				&& $query_result_template->theme === $template->theme
			) {
				if ( self::template_is_eligible_for_product_archive_fallback( $template->slug ) ) {
					$query_result_template->has_theme_file = true;
				}

				return true;
			}
		}

		return false;
	}
	public static function template_is_eligible_for_product_archive_fallback( $template_slug ) {
		$eligible_for_fallbacks = array( 'taxonomy-course-category', 'taxonomy-course-tag' );

		return in_array( $template_slug, $eligible_for_fallbacks, true )
			&& ! self::theme_has_template( $template_slug )
			&& self::theme_has_template( 'archive-product' );
	}
	public static function remove_theme_templates_with_custom_alternative( $templates ) {

		// Get the slugs of all templates that have been customised and saved in the database.
		$customised_template_slugs = array_map(
			function( $template ) {
				return $template->slug;
			},
			array_values(
				array_filter(
					$templates,
					function( $template ) {
						// This template has been customised and saved as a post.
						return 'custom' === $template->source;
					}
				)
			)
		);

		// Remove theme (i.e. filesystem) templates that have the same slug as a customised one. We don't need to check
		// for `woocommerce` in $template->source here because woocommerce templates won't have been added to $templates
		// if a saved version was found in the db. This only affects saved templates that were saved BEFORE a theme
		// template with the same slug was added.
		return array_values(
			array_filter(
				$templates,
				function( $template ) use ( $customised_template_slugs ) {
					// This template has been customised and saved as a post, so return it.
					return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
				}
			)
		);
	}

	public static function get_block_template_title( $template_slug ) {
		$plugin_template_types = self::get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['title'];
		} else {
			// Human friendly title converted from the slug.
			return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}

	public static function get_block_template_description( $template_slug ) {
		$plugin_template_types = self::get_plugin_block_template_types();
		if ( isset( $plugin_template_types[ $template_slug ] ) ) {
			return $plugin_template_types[ $template_slug ]['description'];
		}
		return '';
	}

	public static function get_plugin_block_template_types() {
		$plugin_template_types = array(
			'single-mcv_lesson'                   => array(
				'title'       => _x( 'Single Lesson', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Displays a single lesson.', 'mine-cloudvod' ),
			),
			'single-mcv_course'                   => array(
				'title'       => _x( 'Single Course', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Displays a single course.', 'mine-cloudvod' ),
			),
			'archive-mcv_course'                  => array(
				'title'       => _x( 'Course Catalog', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Displays your courses.', 'mine-cloudvod' ),
			),
			'taxonomy-course-category'             => array(
				'title'       => _x( 'Courses by Category', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Displays courses filtered by a category.', 'mine-cloudvod' ),
			),
			'taxonomy-course-tag'             => array(
				'title'       => _x( 'Courses by Tag', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Displays courses filtered by a tag.', 'mine-cloudvod' ),
			),
			'page-mcv-checkout'             => array(
				'title'       => _x( 'Course Checkout', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Course checkout page.', 'mine-cloudvod' ),
			),
			'page-mcv-order-list'             => array(
				'title'       => _x( 'Order List', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Show user\'s order list.', 'mine-cloudvod' ),
			),
			'page-mcv-my-courses'             => array(
				'title'       => _x( 'User\'s courses', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Show the courses list of user enrolled.', 'mine-cloudvod' ),
			),
			'page-mcv-favorites'             => array(
				'title'       => _x( 'Favorites', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Show user\'s favorites course list.', 'mine-cloudvod' ),
			),
			'page-mcv-index'             => array(
				'title'       => _x( 'Courses Index', 'Template name', 'mine-cloudvod' ),
				'description' => __( 'Courses Index', 'mine-cloudvod' ),
			),
		);

		return $plugin_template_types;
	}
}