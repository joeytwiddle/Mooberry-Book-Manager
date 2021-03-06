<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Activation
 * 
 * Runs on plugin activation
 * - Creates custom tables
 * - Inserts default images
 * - Running the init() functions
 * - flushing the rewrite rules
 *
 * @since 1.0
 * @return void
 */
// NOTE: DO NOT change the name of this function because it is required for
// the add ons to check dependency
register_activation_hook( MBDB_PLUGIN_FILE, 'mbdb_activate'  );
function mbdb_activate() {
	MBDB()->books->create_table();

	mbdb_set_up_roles();

	// insert defaults
	$mbdb_options = get_option('mbdb_options');
	
	mbdb_insert_default_retailers( $mbdb_options );
	mbdb_insert_default_formats( $mbdb_options );
	mbdb_insert_default_edition_formats( $mbdb_options );
	mbdb_insert_default_social_media ( $mbdb_options );
	
	mbdb_insert_image( 'coming-soon', 'coming_soon_blue.jpg', $mbdb_options );
	mbdb_insert_image( 'goodreads', 'goodreads.png', $mbdb_options );
	
	update_option( 'mbdb_options', $mbdb_options );
	
	// SET DEFAULT OPTIONS FOR GRID SLUGS
	mbdb_set_default_tax_grid_slugs();
	
	
	mbdb_init();

	flush_rewrite_rules();
}

/**
 * Deactivation
 * 
 * Runs on plugin deactivation
 * - flushing the rewrite rules
 *
 * @since 1.0
 * @return void
 */
register_deactivation_hook( MBDB_PLUGIN_FILE, 'mbdb_deactivate' );
function mbdb_deactivate() {
	flush_rewrite_rules();
}

/**
 * Loads the plugin language files
 *
 * @access public
 * @since 1.0
 * @return void
 */
add_action( 'plugins_loaded', 'mbdb_load_textdomain' );
function mbdb_load_textdomain() {

	load_plugin_textdomain( 'mooberry-book-manager', FALSE, basename( MBDB_PLUGIN_DIR ) . '/languages/' );
}

/**
 * Init
 *
 * Registers Custom Post Types and Taxonomies
 * Verifies Tax Grid is installed correctly
 * Does upgrade routines
 *
 * @access public
 * @since 1.0
 * @return void
 */
add_action( 'init', 'mbdb_init' );	
function mbdb_init() {
	
	mbdb_register_cpts();
	mbdb_register_taxonomies();
	
	mbdb_add_tax_grid();
	
	mbdb_upgrade_versions();
}

/**
 * Widget Init
 *
 * Registers Book Widget
 *
 * @access public
 * @since 1.0
 * @return widget
 */
add_action( 'widgets_init', 'mbdb_register_widgets' );
function mbdb_register_widgets() {
	return register_widget( 'mbdb_book_widget' );
}


/**
 * Displays book grid if necessary
 *
 *
 * @access public
 * @since 1.0
 * @since 1.1 Added tc_post_list_content filter for Customizr theme
 * @since 2.3 Added check for !in_the_loop or !is_main_query
 * @since 3.0 Removed book pages and tax grids into shortcodes
 *
 * @param string $content 
 * @return string content to display
 */
// because the Customizr theme doesn't use the standard WP set up and
// is automatically considering the tax grids a post list type (archive),
// add an additional filter handler for the content of the Customizr theme
// tc_post_list_content should be unique enough to the Customizr theme
// that it doesn't affect anything else?
add_filter( 'tc_post_list_content', 'mbdb_content' );
add_filter( 'the_content', 'mbdb_content' );
function mbdb_content( $content ) {
	
	global $post;
	
	// this weeds out content in the sidebar and other odd places
	// thanks joeytwiddle for this update
	// added in version 2.3
	if ( !in_the_loop() || !is_main_query() ) {
		return $content;
	}
	
	if ( get_post_type() == 'page' && is_main_query() && !is_admin() ) {
		
		$display_grid = get_post_meta( $post->ID, '_mbdb_book_grid_display', true );
		
		if ( $display_grid != 'yes' ) {
			return apply_filters( 'mbdb_book_grid_display_grid_no', $content );
		} else {
			$content .= mbdb_bookgrid_content();
			return apply_filters( 'mbdb_book_grid_content', $content );
		}
	}
	
	// return what we got in
	return $content;
}
	
	
/**
 * Displays tax grid if necessary. Truncates excerpt if necessary
 *
 *
 * Forces the display of the whole content, not the excerpt, in the case of 
 * users set to use except on archives, for the tax query. This was found
 * by the Generate theme
 * 
 * On the admin page for the Books CPT truncates the excerpt to 50 characters
 * 
 * @access public
 * @since 2.0
 * @since 2.3 Added check for !in_the_loop or !is_main_query
 * @since 3.0 Returns post_content because tax grids now use a short code
 *
 * @param string $content
 *
 * @return string content to display
 */	
add_filter( 'the_excerpt', 'mbdb_excerpt' );
function mbdb_excerpt( $content ) {
	
	// if on a tax grid and there's query vars set, display the special grid
	if ( get_post_type() == 'mbdb_tax_grid' && is_main_query() && !is_admin() ) {
		
		// this weeds out content in the sidebar and other odd places
		// thanks joeytwiddle for this update
		// added in version 2.3
		if ( !in_the_loop() || !is_main_query() ) {
			return $content;
		}
	
		global $post;
		return $post->post_content;
	}

	// if we're in the admin side and the post type is mbdb_book then we're showign the list of books
	// truncate the excerpt
	if ( is_admin() && get_post_type() == 'mbdb_book' ) {
		$content = trim( substr( $content, 0, 50 ) );
		if ( strlen( $content ) > 0 ) {
			$content .= '...';
		}
	}
	
	return $content;
}	
	
/**
 * Grab the template set in the options for the book page and tax grid
 *
 *
 * Attempts to pull the template from the options 
 *
 * In the case that the options aren't set or the template selected
 * doesn't exist, default to the theme's single template
 * 
 * 
 * @access public
 * @since 2.1
 * @since 3.0 Added support for tax grid template as well. Changed from single_template to template_include filter
 *
 * @param string $template
 * @return string $template
 */
add_filter( 'template_include', 'mbdb_single_template' );
function mbdb_single_template( $template ) {

	// if not a book or tax grid, return what we got in
	if ( get_post_type() != 'mbdb_book' && get_post_type() != 'mbdb_tax_grid' ) {
		return $template;
	}
	
	// make sure it's the main query and not on the admin
	if ( is_main_query() && !is_admin() ) {
		$mbdb_options = get_option( 'mbdb_options' );
		
		// if it's a book, get the default template for book pages
		if ( get_post_type() == 'mbdb_book' ) {
			if ( array_key_exists( 'mbdb_default_template', $mbdb_options ) ) {
				$default_template = $mbdb_options['mbdb_default_template'];
			} 	
		} else {
			// otherwise it's a tax grid so get the default template for tax grids
			if ( array_key_exists( 'mbdb_tax_grid_template', $mbdb_options ) ) {
				$default_template = $mbdb_options['mbdb_tax_grid_template'];
			}
		}
	} else {
		return $template;
	}
	
	// if it's the default template, use the single.php template
	if ( $default_template == 'default' ) {
		$default_template = 'single.php';
	}
	
	// now get the file
	if ( isset($default_template) && $default_template != '' && $default_template != 'default' ) {
		
		// first check if there's one in the child theme
		$child_theme = get_stylesheet_directory();
	
		if ( file_exists( $child_theme . '/' . $default_template ) ) {
			return $child_theme . '/' . $default_template;
		} else {
			// if not get the parent theme
			$parent_theme = get_template_directory();

			if ( file_exists( $parent_theme . '/' . $default_template ) ) {
				return $parent_theme . '/' . $default_template;
			}
		}
	}
	
	// if everything fails, just return whatever came in
	return $template;
}


add_action( 'admin_notices', 'mbdb_admin_import_notice', 0 );
function mbdb_admin_import_notice(){
	$import_books = get_option('mbdb_import_books');
	if (!$import_books || $import_books == null) {
		// only need to migrate if there are books
		$args = array('posts_per_page' => -1,
					'post_type' => 'mbdb_book',
		);
		
		$posts = get_posts( $args  );
		
		if (count($posts) > 0) {
			
			$m = __('Upgrading to Mooberry Book Manager version 3.0 requires some data migration before Mooberry Book Manager will operate properly.', 'mooberry-book-manager');
			$m2 = __('Migrate Data Now', 'mooberry-book-manager');
			echo '<div id="message" class="error"><p>' . $m . '</p><p><a href="admin.php?page=mbdb_migrate" class="button">' . $m2 . '</a></p></div>';
		} else {
			update_option('mbdb_import_books', true);
		}
		wp_reset_postdata();
	}
}
  



//****************************** term meta *************************************/

		
function mbdb_new_series_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'series' );
}

function mbdb_new_genre_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'genre' );
}

function mbdb_new_tag_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'tag' );
}

function mbdb_new_editor_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'editor' );
}

function mbdb_new_cover_artist_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'cover_artist' );
}

function mbdb_new_illustrator_grid_description_field() {
	mbdb_taxonomy_grid_description_field( 'illustrator' );
}


function mbdb_save_series_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'series' );
}

function mbdb_save_genre_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'genre' );
}

function mbdb_save_tag_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'tag' );
}

function mbdb_save_illustrator_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'illustrator' );
}

function mbdb_save_cover_artist_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'cover_artist' );
}

function mbdb_save_editor_book_grid_description( $termid ) {
	mbdb_save_taxonomy_book_grid_description( $termid, 'editor' );
}

function mbdb_edit_series_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'series' );
}

function mbdb_edit_genre_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'genre' );
}

function mbdb_edit_tag_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'tag' );
}

function mbdb_edit_editor_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'editor' );
}

function mbdb_edit_illustrator_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'illustrator' );
}

function mbdb_edit_cover_artist_grid_description_field( $term ) {
	mbdb_edit_taxonomy_grid_description_field( $term, 'cover_artist' );
}

function mbdb_taxonomy_grid_description_field( $taxonomy ) {
	 wp_nonce_field( basename( __FILE__ ), 'mbdb_ ' . $taxonomy .'_grid_description_nonce' ); 
	 $mbdb_options = get_option('mbdb_options');
	if (array_key_exists('mbdb_book_grid_mbdb_' . $taxonomy . '_slug', $mbdb_options) ) {
		$slug = $mbdb_options['mbdb_book_grid_mbdb_' . $taxonomy . '_slug'];
	} else {
		$slug = $taxonomy;
	}
	 ?>

    <div class="form-field">
        <label for="mbdb_<?php echo $taxonomy; ?>_book_grid_description"><?php _e( 'Book Grid Description', 'mooberry-book-manager' ); ?></label>
		<?php wp_editor( '', 'mbdb_' . $taxonomy . '_book_grid_description', array('textarea_rows'=>5)); ?>
		<p><?php _e('The Book Grid Description is displayed above the auto-generated grid for this page, ex. ', 'mooberry-book-manager'); ?><?php echo home_url($slug . '/' . $taxonomy . '-slug'); ?>
        
    </div>
	
	<div class="form-field">
        <label for="mbdb_<?php echo $taxonomy; ?>_book_grid_description_bottom"><?php _e( 'Book Grid Description (Bottom)', 'mooberry-book-manager' ); ?></label>
		<?php wp_editor( '', 'mbdb_' . $taxonomy . '_book_grid_description_bottom', array('textarea_rows'=>5)); ?>
		<p><?php _e('The bottom Book Grid Description is displayed below the auto-generated grid for this page, ex. ', 'mooberry-book-manager'); ?><?php echo home_url($slug . '/' . $taxonomy . '-slug'); ?>
        
    </div>
<?php 
}



function mbdb_edit_taxonomy_grid_description_field( $term, $taxonomy ) {

	
		$description = get_term_meta( $term->term_id, 'mbdb_' . $taxonomy .'_book_grid_description', true );
		$description_bottom = get_term_meta( $term->term_id, 'mbdb_' . $taxonomy .'_book_grid_description_bottom', true );
		
		$mbdb_options = get_option('mbdb_options');
		if (array_key_exists('mbdb_book_grid_mbdb_' . $taxonomy . '_slug', $mbdb_options) ) {
			$slug = $mbdb_options['mbdb_book_grid_mbdb_' . $taxonomy . '_slug'];
		} else {
			$slug = $taxonomy;
		}
		
	  ?>

		<tr class="form-field">
			<th scope="row"><label for="mbdb_<?php echo $taxonomy; ?>_book_grid_description"><?php _e( 'Book Grid Description', 'mooberry-book-manager' ); ?></label></th>
			<td>
				<?php 
				wp_nonce_field( basename( __FILE__ ), 'mbdb_' . $taxonomy . '_grid_description_nonce' ); 
				
				wp_editor( $description, 'mbdb_' . $taxonomy . '_book_grid_description', array('textarea_rows' => 5));
				?>
				<p class="description"><?php _e('The Book Grid Description is displayed above the auto-generated grid for this page, ', 'mooberry-book-manager'); ?><A target="_new" href="<?php echo home_url($slug . '/' . $term->slug); ?>"><?php echo home_url($slug . '/' . $term->slug); ?></a></p>
			</td>
		</tr>
		
		<tr class="form-field">
			<th scope="row"><label for="mbdb_<?php echo $taxonomy; ?>_book_grid_description_bottom"><?php _e( 'Book Grid Description (Bottom)', 'mooberry-book-manager' ); ?></label></th>
			<td>
				<?php 
			
				wp_editor( $description_bottom, 'mbdb_' . $taxonomy . '_book_grid_description_bottom', array('textarea_rows' => 5));
				?>
				<p class="description"><?php _e('The bottom Book Grid Description is displayed below the auto-generated grid for this page, ', 'mooberry-book-manager' ); ?><A target="_new" href="<?php echo home_url($slug . '/' . $term->slug); ?>"><?php echo home_url($slug . '/' . $term->slug); ?></a></p>
			</td>
		</tr>
<?php }



function mbdb_save_taxonomy_book_grid_description( $term_id, $taxonomy ) {

    if ( ! isset( $_POST['mbdb_' . $taxonomy . '_grid_description_nonce'] ) || ! wp_verify_nonce( $_POST['mbdb_' . $taxonomy . '_grid_description_nonce'], basename( __FILE__ ) ) )
        return;

	$old_description = get_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description', true );
    $new_description = isset( $_POST['mbdb_' . $taxonomy . '_book_grid_description'] ) ?  $_POST['mbdb_' . $taxonomy . '_book_grid_description']  : '';

   if ( $old_description && '' === $new_description )
       delete_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description' );

   else if ( $old_description !== $new_description )
        update_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description', $new_description );
	
	$old_description_bottom = get_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description_bottom', true );
    $new_description_bottom = isset( $_POST['mbdb_' . $taxonomy . '_book_grid_description_bottom'] ) ?  $_POST['mbdb_' . $taxonomy . '_book_grid_description_bottom']  : '';

   if ( $old_description_bottom && '' === $new_description_bottom )
       delete_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description_bottom' );

   else if ( $old_description_bottom !== $new_description_bottom )
        update_term_meta( $term_id, 'mbdb_' . $taxonomy . '_book_grid_description_bottom', $new_description_bottom );
}

//********************* end term meta ****************************************/


/**
 * Register Custom Post Types
 *
 * @access public
 * @since 1.0
 * @since 2.0 Added comments support to mbdb_book, capabilities for new roles
 * @since 2.4 Added author support to mbdb_book, Added filter and item_list labels
 * @since 3.0 moved to separate function, added editor, illustrator, cover artist taxonomies
 *
 */
function mbdb_register_cpts() {
	// create Book Post Type
	register_post_type( 'mbdb_book', apply_filters( 'mbdb_book_cpt', array(	
			'label' => _x( 'Books', 'noun', 'mooberry-book-manager' ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_icon' => 'dashicons-book-alt',
			'menu_position' => 20,
			'show_in_nav_menus' => true,
			'has_archive' => false,
			'capability_type' => array( 'mbdb_book', 'mbdb_books' ),
			'map_meta_cap' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'book' ),
			'query_var' => true,
			'supports' => array( 'title', 'comments', 'author' ),
			'taxonomies' => array( 'mbdb_tag', 'mbdb_genre', 'mbdb_series', 'mbdb_editor', 'mbdb_illustator', 'mbdb_cover_artist' ),
			'labels' => array (
				'name' => _x( 'Books', 'noun', 'mooberry-book-manager' ),
				'singular_name' => _x( 'Book', 'noun', 'mooberry-book-manager' ),
				'menu_name' => _x( 'Books', 'noun', 'mooberry-book-manager' ),
				'all_items' => __( 'All Books', 'mooberry-book-manager' ),
				'add_new' => __( 'Add New', 'mooberry-book-manager' ),
				'add_new_item' => __( 'Add New Book', 'mooberry-book-manager' ),
				'edit' => __( 'Edit', 'mooberry-book-manager' ),
				'edit_item' => __( 'Edit Book', 'mooberry-book-manager' ),
				'new_item' => __( 'New Book', 'mooberry-book-manager' ),
				'view' => __( 'View Book', 'mooberry-book-manager' ),
				'view_item' => __( 'View Book', 'mooberry-book-manager' ),
				'search_items' => __( 'Search Books', 'mooberry-book-manager' ),
				'not_found' => __( 'No Books Found', 'mooberry-book-manager' ),
				'not_found_in_trash' => __( 'No Books Found in Trash', 'mooberry-book-manager' ),
				'parent' => __( 'Parent Book', 'mooberry-book-manager' ),
				'filter_items_list'     => __( 'Filter Book List', 'mooberry-book-manager' ),
				'items_list_navigation' => __( 'Book List Navigation', 'mooberry-book-manager' ),
				'items_list'            => __( 'Book List', 'mooberry-book-manager' ),
				),
			) )
		);
		
		register_post_type( 'mbdb_tax_grid', apply_filters( 'mbdb_tax_grid_cpt', array(	
				'label' => 'Tax Grid',
				'public' => true,
				'show_in_menu' => false,
				'show_ui' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => true,
				'show_in_nav_menus' => false,
				'show_in_admin_bar'	=> false,
				'has_archive' => false,
				'capability_type' => 'post',
				'hierarchical' => false,
				'rewrite' => array( 'slug' => 'mbdb_tax_grid' ),
				'query_var' => true,
				'supports' => array( 'title' ),
				) 
			)
		);
	
}

/**
 * Register Custom Taxonomies
 *
 * @access public
 * @since 1.0
 * @since 2.0 Added capabilities for new roles, moved tags to mbdb_tags
 * @since 2.4 Added filter and item_list labels
 * @since 3.0 moved to separate function, added editor, illustrator, cover artist taxonomies
 *
 */
function mbdb_register_taxonomies() {
	register_taxonomy( 'mbdb_genre', 'mbdb_book', apply_filters( 'mdbd_genre_taxonomy', array(
				//'rewrite' => false, 
				'rewrite' => array(	'slug' => 'mbdb_genres' ),
				'public' => true, //false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Genres', 'mooberry-book-manager' ),
					'singular_name' => __( 'Genre', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Genres' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Genres' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Genre' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Genre:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Genre' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Genre' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Genre' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Genre Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Genres' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Genres', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate genres with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove genres', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used genres', 'mooberry-book-manager' ),
					'not_found' => __( 'No genres found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Genre navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Genre list', 'mooberry-book-manager' ),

				)
			)
		)
	);

	register_taxonomy( 'mbdb_tag', 'mbdb_book', apply_filters( 'mdbd_tag_taxonomy', array(
				'rewrite' => array(	'slug' => 'mbdb_tags' ),
			//	'rewrite'	=>	false,
				'public'	=> true, //false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Tags', 'mooberry-book-manager' ),
					'singular_name' => __( 'Tag', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Tags' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Tags' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Tag' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Tag:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Tag' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Tag' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Tag' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Tag Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Tags' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Tags', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate tags with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove tags', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used tags', 'mooberry-book-manager' ),
					'not_found' => __( 'No tags found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Tag navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Tag list', 'mooberry-book-manager' ),
				)
			)
		)
	);  


	register_taxonomy( 'mbdb_series', 'mbdb_book', apply_filters( 'mbdb_series_taxonomy', array( 
				'rewrite' =>  array( 'slug' => 'mbdb_series' ),
				'public' => true, // false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Series', 'mooberry-book-manager' ),
					'singular_name' => __( 'Series', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Series' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Series' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Series' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Series:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Series' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Series' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Series' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Series Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Series' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Series', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate series with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove series', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used series', 'mooberry-book-manager' ),
					'not_found' => __( 'No Series found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Series navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Series list', 'mooberry-book-manager' ),
				)
			)
		)
	);
		
	register_taxonomy( 'mbdb_editor', 'mbdb_book', apply_filters( 'mbdb_editor_taxonomy', array(
				//'rewrite' => false, 
				'rewrite' => array(	'slug' => 'mbdb_editors' ),
				'public' => true, //false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Editors', 'mooberry-book-manager' ),
					'singular_name' => __( 'Editor', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Editors' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Editors' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Editor' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Editor:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Editor' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Editor' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Editor' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Editor Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Editors' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Editors', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate Editors with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove Editors', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used Editors', 'mooberry-book-manager' ),
					'not_found' => __( 'No Editors found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Edtior navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Editor list', 'mooberry-book-manager' ),
				)
			)
		)
	);
		
	register_taxonomy( 'mbdb_illustrator', 'mbdb_book', apply_filters( 'mbdb_illustrator_taxonomy', array(
				//'rewrite' => false, 
				'rewrite' => array(	'slug' => 'mbdb_illustrators' ),
				'public' => true, //false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Illustrators', 'mooberry-book-manager' ),
					'singular_name' => __( 'Illustrator', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Illustrators' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Illustrators' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Illustrator' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Illustrator:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Illustrator' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Illustrator' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Illustrator' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Illustrator Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Illustrators' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Illustrators', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate Illustrators with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove Illustrators', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used Illustrators', 'mooberry-book-manager' ),
					'not_found' => __( 'No Illustrators found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Illustrator navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Illustrator list', 'mooberry-book-manager' ),
				)
			)
		)
	);
		
	register_taxonomy( 'mbdb_cover_artist', 'mbdb_book', apply_filters( 'mbdb_cover_artist_taxonomy', array(
				//'rewrite' => false, 
				'rewrite' => array(	'slug' => 'mbdb_cover_artists' ),
				'public' => true, //false,
				'show_admin_column' => true,
				'update_count_callback' => '_update_post_term_count',
				'capabilities'	=> array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'manage_mbdb_books',				
				),
				'labels' => array(
					'name' => __( 'Cover Artists', 'mooberry-book-manager' ),
					'singular_name' => __( 'Cover Artist', 'mooberry-book-manager' ),
					'search_items' => __( 'Search Cover Artists' , 'mooberry-book-manager' ),
					'all_items' =>  __( 'All Cover Artists' , 'mooberry-book-manager' ),
					'parent_item' =>  __( 'Parent Cover Artist' , 'mooberry-book-manager' ),
					'parent_item_colon' =>  __( 'Parent Cover Artist:' , 'mooberry-book-manager' ),
					'edit_item' =>  __( 'Edit Cover Artist' , 'mooberry-book-manager' ),
					'update_item' =>  __( 'Update Cover Artist' , 'mooberry-book-manager' ),
					'add_new_item' =>  __( 'Add New Cover Artist' , 'mooberry-book-manager' ),
					'new_item_name' =>  __( 'New Cover Artist Name' , 'mooberry-book-manager' ),
					'menu_name' =>  __( 'Cover Artists' , 'mooberry-book-manager' ),
					'popular_items' => __( 'Popular Cover Artists', 'mooberry-book-manager' ),
					'separate_items_with_commas' => __( 'Separate Cover Artists with commas', 'mooberry-book-manager' ),
					'add_or_remove_items' => __( 'Add or remove Cover Artists', 'mooberry-book-manager' ),
					'choose_from_most_used' => __( 'Choose from the most used Cover Artists', 'mooberry-book-manager' ),
					'not_found' => __( 'No Cover Artists found', 'mooberry-book-manager' ),
					'items_list_navigation' => __( 'Cover Artist navigation', 'mooberry-book-manager' ),
					'items_list'            => __( 'Cover Artist list', 'mooberry-book-manager' ),
				)
			)
		)
	);
	
	
	// ************ term meta *********************************/
	if (  function_exists( 'get_term_meta' ) ) {
		$taxonomies = get_object_taxonomies( 'mbdb_book', 'objects' );
		foreach($taxonomies as $name => $taxonomy) {
			
			$pretty_name = str_replace('mbdb_', '', $name);
			
			register_meta( 'term',  $name . '_book_grid_descripion', 'mbdb_sanitize_book_grid_description' );
			register_meta( 'term', $name . '_book_grid_description_bottom', 'mbdb_sanitize_book_grid_description' );
			
		
			add_action(  $name . '_add_form_fields', 'mbdb_new_' . $pretty_name . '_grid_description_field' );
			add_action( 'edit_' . $name,   'mbdb_save_' . $pretty_name . '_book_grid_description' );
			add_action( 'create_' . $name, 'mbdb_save_' . $pretty_name . '_book_grid_description' );
			add_action( $name . '_edit_form_fields', 'mbdb_edit_' . $pretty_name . '_grid_description_field' );

			
			
			
		}
	}
}

function mbdb_sanitize_book_grid_description( $description) {
	return balanceTags(wp_kses_post($description), true);
}

/******************* end term meta ******************************************/

/**
 * Add Tax Grid Post
 *
 * add tax grid post if necessary
 * this is never seen and just needed for the series/tag/genre shortcut URLS (hack)
 *	
 * @access public
 * @since 1.0
 * @since 3.0 added [mbdb_tax_grid] shortcode
 *
 */
function mbdb_add_tax_grid() {

	$tax_grids = get_posts( array(
				'posts_per_page' => -1,
				'post_type' => 'mbdb_tax_grid',
				'post_status' => 'publish' 
				)
			);

	// if there's more than one already in the database, delete them all but one
	if ( count( $tax_grids > 1 ) ) {
		for ( $x=1; $x < count( $tax_grids ); $x++ ) {
			wp_delete_post( $tax_grids[ $x ]->ID, true );
		}
	}
	
	// if there aren't any, add one
	if ( count( $tax_grids ) == 0 ) { 
				$tax_grid_id = wp_insert_post( apply_filters( 'mbdb_insert_tax_grid_args', array(
						'post_title' => wp_title(),
						'post_type' => 'mbdb_tax_grid',
						'post_status' => 'publish',
						'name' => 'test',
						'comment_status' => 'closed',
						'post_content' => '[mbdb_tax_grid]',
						)
					)
				);
	} else {
		$tax_grid_id = $tax_grids[0]->ID;
	}
	
	// check that the tax grid has the short code
	$tax_grid = get_post( $tax_grid_id );
	if ( $tax_grid->post_content != '[mbdb_tax_grid]' ) {
		wp_update_post( array( 'ID' => $tax_grid->ID, 
								'post_content' => '[mbdb_tax_grid]' ) 
						);
	}
}
	