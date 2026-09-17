<?php
namespace MineCloudvod\LMS\Blocks;

class BlockTemplates{

    private $templates_directory;
    private $template_parts_directory;
    const PLUGIN_SLUG = 'mine-cloudvod/mine-cloudvod';

    public function __construct( ) {
        $this->templates_directory      = MINECLOUDVOD_PATH . '/templates/blocks/templates';
        $this->template_parts_directory = MINECLOUDVOD_PATH . '/templates/blocks/parts';
        $this->init();
    }

    protected function init() {
        add_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
        add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
    }

	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( !mcv_current_theme_is_fse_theme() ) {
			return $query_result;
		}

		$post_type      = $query['post_type'] ?? '';
		$slugs          = $query['slug__in'] ?? array();
		$template_files = $this->get_block_templates( $slugs, $template_type );

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {

			// If we have a template which is eligible for a fallback, we need to explicitly tell Gutenberg that
			// it has a theme file (because it is using the fallback template file). And then `continue` to avoid
			// adding duplicates.
			if ( BlockTemplateUtils::set_has_theme_file_if_fallback_is_available( $query_result, $template_file ) ) {
				continue;
			}

			// If the current $post_type is set (e.g. on an Edit Post screen), and isn't included in the available post_types
			// on the template file, then lets skip it so that it doesn't get added. This is typically used to hide templates
			// in the template dropdown on the Edit Post page.
			if ( $post_type &&
				isset( $template_file->post_types ) &&
				! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			// It would be custom if the template was modified in the editor, so if it's not custom we can load it from
			// the filesystem.
			if ( 'custom' !== $template_file->source ) {
				$template = BlockTemplateUtils::build_template_result_from_file( $template_file, $template_type );
			} else {
				$template_file->title       = BlockTemplateUtils::get_block_template_title( $template_file->slug );
				$template_file->description = BlockTemplateUtils::get_block_template_description( $template_file->slug );
				$query_result[]             = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
				! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query =
				! isset( $query['area'] ) || $template_file->area === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		// We need to remove theme (i.e. filesystem) templates that have the same slug as a customised one.
		// This only affects saved templates that were saved BEFORE a theme template with the same slug was added.
		$query_result = BlockTemplateUtils::remove_theme_templates_with_custom_alternative( $query_result );

		/**
		 * WC templates from theme aren't included in `$this->get_block_templates()` but are handled by Gutenberg.
		 * We need to do additional search through all templates file to update title and description for WC
		 * templates that aren't listed in theme.json.
		 */
		$query_result = array_map(
			function( $template ) {
				if ( 'theme' === $template->origin ) {
					return $template;
				}
				if ( $template->title === $template->slug ) {
					$template->title = BlockTemplateUtils::get_block_template_title( $template->slug );
				}
				if ( ! $template->description ) {
					$template->description = BlockTemplateUtils::get_block_template_description( $template->slug );
				}
				return $template;
			},
			$query_result
		);

		return $query_result;
	}

	public function get_block_templates( $slugs = array(), $template_type = 'wp_template' ) {
		$templates_from_db  = $this->get_block_templates_from_db( $slugs, $template_type );
		$templates_from_mcv = $this->get_block_templates_from_mcv( $slugs, $templates_from_db, $template_type );
		$templates          = array_merge( $templates_from_db, $templates_from_mcv );
		return  $templates;
	}

	public function get_block_templates_from_db( $slugs = array(), $template_type = 'wp_template' ) {
		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( BlockTemplateUtils::PLUGIN_SLUG, get_stylesheet() ),
				),
			),
		);

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query         = new \WP_Query( $check_query_args );
		$saved_mcv_templates = $check_query->posts;

		return array_map(
			function( $saved_mcv_template ) {
				return BlockTemplateUtils::build_template_result_from_post( $saved_mcv_template );
			},
			$saved_mcv_templates
		);
	}

	public function get_block_templates_from_mcv( $slugs, $already_found_templates, $template_type = 'wp_template' ) {
		$directory      = $this->get_templates_directory( $template_type );
		$template_files = BlockTemplateUtils::get_template_paths( $directory );
		$templates      = array();

		foreach ( $template_files as $template_file ) {
			// Skip the template if it's blockified, and we should only use classic ones.
			// Until the blockified Product Grid Block is implemented, we need to always skip the blockified templates.
			// phpcs:ignore Squiz.PHP.CommentedOutCode
			if ( strpos( $template_file, 'blockified' ) !== false ) {
				continue;
			}

			$template_slug = BlockTemplateUtils::generate_template_slug_from_path( $template_file );

			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the theme already has a template, or the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				BlockTemplateUtils::theme_has_template( $template_slug ) ||
				count(
					array_filter(
						$already_found_templates,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0 ) {
				continue;
			}
			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$templates[] = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug );
		}

		return $templates;
	}

	public function get_block_file_template( $template, $id, $template_type ) {
		$template_name_parts = explode( '//', $id );

		if ( count( $template_name_parts ) < 2 ) {
			return $template;
		}

		list( $template_id, $template_slug ) = $template_name_parts;

		// If we are not dealing with a WooCommerce template let's return early and let it continue through the process.
		if ( BlockTemplateUtils::PLUGIN_SLUG !== $template_id ) {
			return $template;
		}

		// If we don't have a template let Gutenberg do its thing.
		if ( ! $this->block_template_is_available( $template_slug, $template_type ) ) {
			return $template;
		}

		$directory          = $this->get_templates_directory( $template_type );
		$template_file_path = $directory . '/' . $template_slug . '.html';
		$template_object    = BlockTemplateUtils::create_new_block_template_object( $template_file_path, $template_type, $template_slug );
		$template_built     = BlockTemplateUtils::build_template_result_from_file( $template_object, $template_type );

		if ( null !== $template_built ) {
			return $template_built;
		}

		// Hand back over to Gutenberg if we can't find a template.
		return $template;
	}

	public function block_template_is_available( $template_name, $template_type = 'wp_template' ) {
		if ( ! $template_name ) {
			return false;
		}
		$directory = $this->get_templates_directory( $template_type ) . '/' . $template_name . '.html';

		return is_readable(
			$directory
		) || $this->get_block_templates( array( $template_name ), $template_type );
	}

    
	protected function get_templates_directory( $template_type = 'wp_template' ) {
		if ( 'wp_template_part' === $template_type ) {
			return $this->template_parts_directory;
		}

		return $this->templates_directory;
	}


}