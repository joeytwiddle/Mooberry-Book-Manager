<?php

add_action( 'admin_head', 'mbdb_register_admin_styles' );	 
function mbdb_register_admin_styles() {
	wp_register_style( 'mbdb-admin-styles', MBDB_PLUGIN_URL .  'css/admin-styles.css', '', mbdb_get_enqueue_version()  );
	wp_enqueue_style( 'mbdb-admin-styles' );
}

add_action( 'admin_footer', 'mbdb_register_script');
function mbdb_register_script() {
	$current_screen = get_current_screen();
	if (!$current_screen) {
		return;
	}
	
	$parent_base = $current_screen->parent_base;
	$post_type = $current_screen->post_type;
	$base = $current_screen->base;
	
	if ($parent_base == 'edit' && $post_type == 'page' && $base == 'post') {
		// admin-book-grid
		$group_by_options = mbdb_book_grid_group_by_options();
		$text_to_translate = array(
							'label1' => __('Group Books Within', 'mooberry-book-manager'),
							'label2' => __('By', 'mooberry-book-manager'),
							'groupby' => $group_by_options);
		wp_register_script( 'mbdb-admin-book-grid', MBDB_PLUGIN_URL .  'includes/js/admin-book-grid.js', array('jquery'), mbdb_get_enqueue_version()); 
		wp_localize_script( 'mbdb-admin-book-grid', 'text_to_translate', $text_to_translate );
		wp_enqueue_script( 'mbdb-admin-book-grid' ); 
	}

	if ($base == 'widgets') {
		// admin-widget
		wp_enqueue_script( 'mbdb-admin-widget',  MBDB_PLUGIN_URL . 'includes/js/admin-widget.js', '', mbdb_get_enqueue_version());		
		
	}
	
	if ($post_type == 'mbdb_book' && $base == 'post') {
		// admin-book
		wp_register_script( 'mbdb-admin-book', MBDB_PLUGIN_URL .   'includes/js/admin-book.js', '', mbdb_get_enqueue_version());
		wp_localize_script( 'mbdb-admin-book', 'display_editions', 'no');
		wp_enqueue_script( 'mbdb-admin-book');
		
	}
	
	if ($parent_base == 'mbdb_options') {
		// admin-settings
		wp_enqueue_script('mbdb-admin-options', MBDB_PLUGIN_URL . 'includes/js/admin-options.js', '', mbdb_get_enqueue_version());
		
		wp_localize_script( 'mbdb-admin-options', 
							'mbdb_admin_options_ajax', 
							array( 
								'translation'	=>	__('Are you sure you want to reset the Book Edit page?', 'mooberry-book-manager'),
								'ajax_url' => admin_url( 'admin-ajax.php' ),
								'ajax_nonce' => wp_create_nonce('mbdb_admin_options_ajax_nonce'),
							) 
						);
	}
	
}

// woocommerce
// add_filter('woocommerce_screen_ids', 'mbdb_woocommerce_screens');
// function mbdb_woocommerce_screens( $screens) {
	// $screens[] = 'edit-mbdb_book';
	// $screens[] = 'mbdb_book';
	// return $screens;
// }

add_action( 'wp_enqueue_scripts', 'mbdb_register_styles', 15 );
function mbdb_register_styles() {
	wp_register_style( 'mbdb-styles', MBDB_PLUGIN_URL .  'css/styles.css', '', mbdb_get_enqueue_version() ) ;
	wp_enqueue_style( 'mbdb-styles' );
	wp_enqueue_script('single-book', MBDB_PLUGIN_URL . 'includes/js/single-book.js', array('jquery'), mbdb_get_enqueue_version());
	
	
}

add_action('wp_head', 'mbdb_grid_styles');
function mbdb_grid_styles() {
	global $post;
	if ($post) {
		$grid = get_post_meta($post->ID, '_mbdb_book_grid_display', true);
		if ( (get_post_type() == 'mbdb_tax_grid' || $grid == 'yes') && is_main_query() && !is_admin() ) {
			$mbdb_book_grid_cover_height = mbdb_get_grid_cover_height($post->ID);
			include MBDB_PLUGIN_DIR . 'css/grid-styles.php';
		}
	}
}