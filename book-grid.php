<?php

/**
 *  This file handles the meta boxes for the book grid settings on pages
 *  as well as retrieving the daa for and displaying the book grid
 *  
 */



/*******************************************************************
	META BOXES
******************************************************************/


/**
 *  Creates metabox to be added to pages for book grids
 *  
 *  
 *  
 *  @since 1.0
 *  
 *  @access public
 */
add_filter( 'cmb2_meta_boxes', 'mbdb_book_grid_meta_boxes' );
function mbdb_book_grid_meta_boxes( ) {
	$mbdb_book_grid_metabox = new_cmb2_box( array(
		'id'			=> 'mbdb_book_grid_metabox',
		'title'			=> __('Book Grid Settings', 'mooberry-book-manager'),
		'object_types'	=> array( 'page' ),
		'context'		=> 'normal',
		'priority'		=> 'default',
		'show_names'	=> true)
	);
		
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Display Books on This Page?', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_display',
			'type'	=> 'select',
			'default'	=> 'no',
			'options'	=> array(
				'yes'	=> __('Yes', 'mooberry-book-manager'),
				'no'	=> __('No', 'mooberry-book-manager'),
			),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name' 	=> __('Books to Display', 'mooberry-book-manager'),
			'id' 	=> '_mbdb_book_grid_books',
			'type'	=> 'select',
			'options'	=> mbdb_book_grid_selection_options(),
		)
	);
			
	$mbdb_book_grid_metabox->add_field( array(
			'name' 	=> __('Select Books', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_custom',
			'type'	=> 'multicheck',
			'options' => mbdb_get_book_array(),
			
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Select Genres', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_genre',
			'type' 	=> 'multicheck',   
			'options' => mbdb_get_term_options('mbdb_genre'),
		)
	);

	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Select Series', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_series',
			'type' 	=> 'multicheck',   
			'options'	=>	mbdb_get_term_options('mbdb_series'),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Select Tags', 'mooberry-book-manager'),
			'id'	=>	'_mbdb_book_grid_tag',
			'type' 	=> 'multicheck',   
			'options'	=>	mbdb_get_term_options('mbdb_tag'),
		)
	);
		
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Select Publishers', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_publisher',
			'type'	=>	'multicheck',
			'options'	=> mbdb_get_publishers('no'),
		)
	);
			
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Select Editors', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_editor',
			'type'	=>	'multicheck',
			'options'	=> mbdb_get_term_options('mbdb_editor'),
		)
	);
			
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Select Illustrators', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_illustrator',
			'type'	=>	'multicheck',
			'options'	=> mbdb_get_term_options('mbdb_illustrator'),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Select Cover Artists', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_cover_artist',
			'type'	=>	'multicheck',
			'options'	=> mbdb_get_term_options('mbdb_cover_artist'),
		)
	);
	
	
	$group_by_options = mbdb_book_grid_group_by_options();
	$count = count($group_by_options);
	
	for($x=1; $x <$count; $x++) {
		$mbdb_book_grid_metabox->add_field( array(
				'name'	=>	__('Group Books By', 'mooberry-book-manager'),
				'id'	=>	'_mbdb_book_grid_group_by_level_' . $x,
				'type'	=>	'select',
				'options'	=> $group_by_options,
			)
		);
		
		// put a warning at the 5th level
		if ( $x == 5 ) {
			$mbdb_book_grid_metabox->add_field( array(
					'name'	=> __('Warning: Setting more than 5 levels could cause the page to timeout and not display.', 'mooberry-book-manager'),
					'type'	=>	'title',
					'id'	=> '_mbdb_book_grid_warning',
					'attributes'	=> array(
							'display'	=> 'none'
					)
				)
			);
		}
		
	}
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Order By', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_order',
			'type'	=> 'select',
			'options'	=> mbdb_book_grid_order_options(),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=>	__('Use default cover height?', 'mooberry-book-manager'),
			'id'	=>	'_mbdb_book_grid_cover_height_default',
			'type'	=>	'select',
			'default'	=>	'yes',
			'options'	=>	array(
				'yes'	=> __('Yes','mooberry-book-manager'),
				'no'	=>	__('No','mooberry-book-manager'),
			),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Book Cover Height (px)', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_cover_height',
			'type'	=> 'text_small',
			'attributes' => array(
					'type' => 'number',
					'pattern' => '\d*',
					'min' => 50,
			),
		)
	);
	
	$mbdb_book_grid_metabox->add_field( array(
			'name'	=> __('Additional Content (bottom)', 'mooberry-book-manager'),
			'id'	=> '_mbdb_book_grid_description_bottom',
			'type'	=> 'wysiwyg',
			'description' => __('This displays under the book grid.', 'mooberry-book-manager'), 
			'options' => array(  
				'wpautop' => true, // use wpautop?
				'media_buttons' => true, // show insert/upload button(s)
				'textarea_rows' => 10, // rows="..."
				'tabindex' => '',
				'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the `<style>` tags, can use "scoped".
				'editor_class' => '', // add extra class(es) to the editor textarea
				'teeny' => false, // output the minimal editor config used in Press This
				'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
				'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
				'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()   
			),
		)
	);
	
	$mbdb_book_grid_metabox = apply_filters('mbdb_book_grid_meta_boxes', $mbdb_book_grid_metabox);
		
}

/****************************************************************************
		GET DATA
*****************************************************************************/

/**
 *  Get the data and generate output content for the book grid
 *  
 *  
 *  
 *  @since 1.0
 *  
 *  @return content to be displayed
 *  
 *  @access public
 */
 function mbdb_bookgrid_content() {
	global $post;
	$content ='';
	
	$mbdb_book_grid_meta_data = get_post_meta( $post->ID  );
	

	// VALIDATE THE INPUTS
	
	// loop through the group by levels
	// set up the group arrays
	// stop at the first one that is none
	// if there is a series, add none after that and stop
	$groups = array();
	$current_group = array();
	$group_by_levels = mbdb_book_grid_group_by_options();

	for($x = 1; $x< count($group_by_levels); $x++) {
		if (!array_key_exists('_mbdb_book_grid_group_by_level_' . $x, $mbdb_book_grid_meta_data) ) {
			$groups[$x] = 'none';
			$current_group['none'] = 0;
			break;
		}
		$group_by_dropdown = $mbdb_book_grid_meta_data['_mbdb_book_grid_group_by_level_' . $x][0];
		$groups[$x] = $group_by_dropdown;
		$current_group[$group_by_dropdown] = 0;
		if ( $group_by_dropdown == 'none' ) {
			break;
		} 
		if ($group_by_dropdown == 'series' ) {
			$groups[$x+1] = 'none';
			$current_group['none'] = 0;
			break;
		}
	}
	
	// set the sort
	if (array_key_exists('_mbdb_book_grid_order', $mbdb_book_grid_meta_data)) {
		$sort = mbdb_set_sort( $groups, $mbdb_book_grid_meta_data['_mbdb_book_grid_order'][0]);
	} else {
		$sort = mbdb_set_sort( $groups, 'titleA' );
	}
	
	// if selection isn't set, default to "all"
	if (array_key_exists('_mbdb_book_grid_books', $mbdb_book_grid_meta_data) ) {
		$selection = $mbdb_book_grid_meta_data['_mbdb_book_grid_books'][0];
	} else {
		$selection = 'all';
	}
	
	// turn selected_ids into an array
	// or null if there aren't any
	if (array_key_exists('_mbdb_book_grid_' . $selection, $mbdb_book_grid_meta_data)) {
		$selected_ids = unserialize($mbdb_book_grid_meta_data['_mbdb_book_grid_' . $selection][0]);
	} else {
		$selected_ids = null;
	}
	
	// start off the recursion by getting the first group
	$level = 1;
	$books = mbdb_get_group($level, $groups, $current_group, $selection, $selected_ids, $sort, null); 
	
	// $books now contains the complete array of books to display in the grid
	
	// get the display output content
	$content =  mbdb_display_grid($books, 0);

	// find all the book grid's postmeta so we can display it in comments for debugging purposes
	$grid_values = array();
	foreach ($mbdb_book_grid_meta_data as $key => $data) {
		if ( substr($key, 0, 5) == '_mbdb' ) {
			$grid_values[$key] = $data[0];
		}
	}
	$content = '<!-- Grid Parameters:
				' . print_r($grid_values, true) . ' -->' . $content;
				

	// add on bottom text
	if (array_key_exists('_mbdb_book_grid_description_bottom', $mbdb_book_grid_meta_data)) {
		$book_grid_description_bottom = $mbdb_book_grid_meta_data[ '_mbdb_book_grid_description_bottom'][0];
	} else {
		$book_grid_description_bottom = '';
	}
	
	return $content . $book_grid_description_bottom;
	
}

/**
 *  Return one group of books for the grid
 *  This is called recursively until the group "none" is found
 *  
 *  @since 1.0
 *  @since 3.0 re-factored
 *  
 *  @param [int] $level         the nested level of the grid we're currently one
 *  @param [array] $groups       the groups in grid
 *  @param [array] $current_group  the id of the current group. Could be if of a
 *  								 series, genre, publisher, illustrator, etc.
 *  @param [string] $selection     what selection of books for the grid ('all',
 *  								 'unpublished', 'series', etc.)
 *  @param [array] $selected_ids  ids of the selection
 *  @param [string] $sort          represents sort, ie 'titleA', 'titleD', 
 *  								'series, etc.
 *  @param [array] $book_ids      optional list of book_ids to filter by, useful
 *  								for add-on plugins to add on to grid (ie MA)
 *  
 *  @return array of books for this group
 *  
 *  @access public
 */
function mbdb_get_group($level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids) { 
	
	do_action('mbdb_book_grid_pre+get_group', $level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
	
	$books = array();
	$taxonomies = get_object_taxonomies( 'mbdb_book', 'objects' );
	$tax_names = array_keys($taxonomies);
	
	switch ( $groups[$level] ) {
		// break the recursion by actually getting the books
		case 'none':
			$books =  MBDB()->books->get_ordered_selection($selection, $selected_ids, $sort, $book_ids, $current_group ); 
			break;
		case 'publisher':
			$books = mbdb_get_books_by_publisher($level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
			break;
		default:
			// see if it's a taxonomy
			// don't just assume it's a taxonomy because it could be
			// that there's an add-on plugin (ie MA) that's added
			// a new group
			if (in_array('mbdb_' . $groups[$level], $tax_names)) {
				$books = mbdb_get_books_by_taxonomy($level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
			}
	}
	
	do_action('mbdb_book_grid_post_get_group', $level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
	
	return apply_filters('mbdb_book_grid_get_group_books', $books, $level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
}
			
/**
 *  Get books by publisher
 *  
 *  @since 
 *  @param [int] $level         the nested level of the grid we're currently one
 *  @param [array] $groups       the groups in grid
 *  @param [array] $current_group  the id of the current group. Could be if of a
 *  								 series, genre, publisher, illustrator, etc.
 *  @param [string] $selection     what selection of books for the grid ('all',
 *  								 'unpublished', 'series', etc.)
 *  @param [array] $selected_ids  ids of the selection
 *  @param [string] $sort          represents sort, ie 'titleA', 'titleD', 
 *  								'series, etc.
 *  @param [array] $book_ids      optional list of book_ids to filter by, useful
 *  								for add-on plugins to add on to grid (ie MA)
 *  
 *  @return array of books
 *  
 *  @access public
 */
 function mbdb_get_books_by_publisher($level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ) { 	
 
	$books = array();
	
	// Get ones w/o publishers first
	$current_group[ $groups[ $level ] ] = -1;
	
	// recursively get the next nested group of books
	$results = mbdb_get_group( $level + 1, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
	
	// only return results if are any so that headers of empty groups
	// aren't displayed
	if ( count($results) > 0 ) {
		$books[ apply_filters('mbdb_book_grid_no_publisher_heading', __('No Publisher Specified', 'mooberry-book-manager')) ] = $results;
	}
	
	// loop through each publisher
	// and recursively get the next nested group of books for that publisher
	$mbdb_options = get_option('mbdb_options');
	if (array_key_exists('publishers', $mbdb_options)) {
		$publishers = $mbdb_options['publishers'];
		foreach($publishers as $publisher) {
			$current_group[ $groups [ $level ] ] = $publisher['uniqueID'];
			$results = mbdb_get_group( $level + 1, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
			
			// only return results if are any so that headers of empty groups
			// aren't displayed
			if (count($results)>0) {
				$books[ apply_filters('mbdb_book_grid_heading', __('Published by ', 'mooberry-book-manager') . $publisher['name'])] = $results;
			}
		}
	}
	return $books;
}

/**
 *  Get books by taxonomy
 *  
 *  @since 
 *  @param [int] $level         the nested level of the grid we're currently one
 *  @param [array] $groups       the groups in grid
 *  @param [array] $current_group  the id of the current group. Could be id of a
 *  								 series, genre, publisher, illustrator, etc.
 *  @param [string] $selection     what selection of books for the grid ('all',
 *  								 'unpublished', 'series', etc.)
 *  @param [array] $selected_ids  ids of the selection
 *  @param [string] $sort          represents sort, ie 'titleA', 'titleD', 
 *  								'series, etc.
 *  @param [array] $book_ids      optional list of book_ids to filter by, useful
 *  								for add-on plugins to add on to grid (ie MA)
 *  
 *  @return array of books
 *  
 *  @access public
 */			
function mbdb_get_books_by_taxonomy($level, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ) { 
		
	$books = array();
		
	// Get ones not in the taxonomy first
	$current_group[ $groups[ $level ] ] = -1;
	
	// recursively get the next nested group of books
	$results = mbdb_get_group($level + 1, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
	
	// only return results if are any so that headers of empty groups
	// aren't displayed
	if (count($results)>0) {
		switch ($groups[$level]) {
			case 'genre':
				$empty = apply_filters('mbdb_book_grid_uncategorized_heading', __('Uncategorized', 'mooberry-book-manager'));
				break;
			case 'series':
				$empty = apply_filters('mbdb_book_grid_standalones_heading', __('Standalones', 'mooberry-book-manager'));
				break;
			case 'tag':
				$empty = apply_filters('mbdb_book_grid_untagged_heading', __('Untagged', 'mooberry-book-manager'));
				break;
			case 'editor':
				$empty = apply_filters('mbdb_book_grid_uncategorized_heading', __('No Editor Specified', 'mooberry-book-manager'));
				break;
			case 'illustrator':
				$empty = apply_filters('mbdb_book_grid_uncategorized_heading', __('No Illustrator Specified', 'mooberry-book-manager'));
				break;
			case 'cover_artist':
				$empty = apply_filters('mbdb_book_grid_uncategorized_heading', __('No Cover Artist Specified', 'mooberry-book-manager'));
				break;
		}	
		$books[ $empty] = $results;
	}
	
	// loop through each term
	// and recursively get the next nested group of books for that term
	$terms_query = array('orderby' => 'slug',
				'hide_empty' => true);

	// if we're grouping by what we're filtering by, only get terms that we're filtering on
	if ($groups[$level] == $selection) {
		$terms_query['include'] = $selected_ids;
	}
	
	$all_terms = get_terms( 'mbdb_' . $groups[$level], $terms_query);
	$taxonomy = get_taxonomy('mbdb_' . $groups[$level]);

	// loop through all the terms
	foreach ($all_terms as $term) {
		$current_group[$groups[$level]] = $term->term_id;
		
		$results = mbdb_get_group($level+1, $groups, $current_group, $selection, $selected_ids, $sort, $book_ids ); 
		
		// only return results if are any so that headers of empty groups
		// aren't displayed
		if (count($results)>0) {
			if (in_array($groups[$level], array('genre', 'series', 'tag'))) {
				$heading = $term->name . ' ' . $taxonomy->labels->singular_name;
			} else {
				$heading = $taxonomy->labels->singular_name . ' ' . $term->name;
			}
			$books[ apply_filters('mbdb_book_grid_heading', $heading )] = $results;
		}
	}
	return $books;
}

/**
 *  
 *  If any of the groups is a series, order by series
 *  otherwise order by whatever came in
 *  
 *  
 *  @since 3.0
 *  @param [array] $groups list of groups for the grid
 *  @param [string] $sort   sort setting
 *  
 *  @return sort setting
 *  
 *  @access public
 */
function mbdb_set_sort($groups, $sort) {
	if (in_array('series', $groups)) {
		return 'series_order';
	} else {
		return $sort;
	}
}


/*****************************************************************************
			DISPLAY GRID
*******************************************************************************/
	
/**
 *  Loop through the $books array and generate the HTML output for the
 *  grid, including printing out the headings and indenting at each
 *  nested level
 *  
 *  Recursively called for each level
 *  
 *  @since 1.0
 *  @since 2.0 made responsive
 *  @since 3.0 re-factored
 *  
 *  @param [array] $mbdb_books nested array of books in grid
 *  @param [int] $l           current level to display
 *  
 *  @return Return_Description
 *  
 *  @access public
 */
function mbdb_display_grid($mbdb_books,  $l) {
	
	// grab the coming soon image
	$mbdb_options = get_option('mbdb_options');
	$coming_soon_image = $mbdb_options['coming-soon'];
	
	// indent the grid by 15px per depth level of the array
	do_action('mbdb_book_grid_pre_div', $l);
	
	$content = '<div class="mbm-book-grid-div" style="padding-left:' . (15 * $l) . 'px;">';
	
	if (count($mbdb_books)>0) {
	
		// because the index of the array could be a genre or series name and not a sequential index use array_keys to get the index
		// if the first element in the array is an object that means there's NOT another level in the array
		// so just print out the grid and skip the rest
		 $the_key = array_keys($mbdb_books);
		 if (count($the_key)>0) {
		
			// this breaks the recursion
			if ( gettype( $mbdb_books[$the_key[0]] ) == 'object') {
				foreach ($mbdb_books as $book) {
					do_action('mbdb_book_grid_pre_div',  $l);
					$content .= mbdb_output_grid_book($book, $coming_soon_image);
				}
				$content .= '</div>'; 
				do_action('mbdb_book_grid_post_div', $l);
				return apply_filters('mbdb_book_grid_content', $content, $l);
			}
		 }
		 
		 // loop through each book
		foreach ($mbdb_books as $key => $set) {
			// If a label is set and there's at least one book, print the label
			if ( $key && count( $set ) > 0 ) {
				// set the heading level based on the depth level of the array
				do_action('mbdb_book_grid_pre_heading',  $l, $key);
				// start the headings at H2
				$heading_level = $l + 2;
				// Headings can only go to H6
				if ($heading_level > 6) {
					$heading_level = 6;
				}
				// display the heading
				$content .= '<h' . $heading_level . ' class="mbm-book-grid-heading' . ( $l + 1 ) . '">' . esc_html($key) . '</h' . $heading_level .'>';
				do_action('mbdb_book_grid_post_heading', $l, $key);
			}	
			if ( gettype( $set ) != 'object') {
				do_action('mbdb_book_grid_pre_recursion',$set,  $l+1);
				$content .= mbdb_display_grid($set,  $l+1);
				do_action('mbdb_book_grid_post_recursion', $set,  $l+1);
			} 
		}
	} else {
		do_action('mbdb_book_grid_no_books_found');
		$content = apply_filters('mbdb_book_grid_books_not_found', $content . __('Books not found', 'mooberry-book-manager'));
	}
	$content .= '</div>'; 
	do_action('mbdb_book_grid_post_div', $l);
	return apply_filters('mbdb_book_grid_content', $content, $l);
}

/**
 *  Generate the HTML to display a book and its cover image
 *  coming soon object passed as parameter because it's stored in 
 *  the options and this function is called several times
 *  
 *  @since 1.0
 *  @since 2.0 made responsive
 *  @since 3.0 re-factored, added alt text
 *  
 *  @param [obj] $book              book object
 *  @param [string] $coming_soon_image coming soon image
 *  
 *  @return html output
 *  
 *  @access public
 */
function mbdb_output_grid_book($book, $coming_soon_image) {

	$image = $book->cover; 
	$default_alt = __('Book Cover:', 'mooberry-book-manager') . ' ' . $book->post_title;
	
	$content = '<span class="mbdb_float_grid">';
	if ($image) {
		$alt = mbdb_get_alt_text( $book->cover_id, $default_alt );
		$content .= '<div class="mbdb_grid_image">';
		$content = apply_filters('mbdb_book_grid_pre_image', $content, $book->book_id, $image);
		$content .= '<a class="mbm-book-grid-title-link" href="' . esc_url(get_permalink($book->book_id)) . '"><img  src="' . esc_url($image) . '" ' . $alt . ' ></a>';
		$content = apply_filters('mbdb_book_grid_post_image', $content, $book->book_id, $image);
		$content .= '</div>';
		
	} else {
		if (isset($coming_soon_image)) {
			$alt = mbdb_get_alt_text( 0, $default_alt );
			$content .= '<div class="mbdb_grid_image">';
			$content = apply_filters('mbdb_book_grid_pre_placeholder_image', $content, $book->book_id, $coming_soon_image);
			$content .= '<a class="mbm-book-grid-title-link" href="' . esc_url(get_permalink($book->book_id)) . '"><img src="' . esc_url($coming_soon_image) . '" ' . $alt . ' ></a></div>';
			$content = apply_filters('mbdb_book_grid_post_placeholder_image', $content, $book->book_id, $coming_soon_image);
		} else {
			$content .= '<div class="mbdb_grid_no_image">';
			$content = apply_filters('mbdb_book_grid_no_image', $content, $book->book_id);
			$content .= '</div>';
		}
	}

	
	$content .= '<span class="mbdb_grid_title">';
	$content = apply_filters('mbdb_book_grid_pre_link', $content, $book->book_id, $book->post_title);
	$content .= '<a class="mbm-book-grid-title-link" href="' . esc_url(get_permalink($book->book_id)) . '">' . esc_html($book->post_title) . '</a>';
	$content = apply_filters('mbdb_book_grid_post_link', $content, $book->book_id, $book->post_title);
	$content .= '</span></span>';

	return $content;
}