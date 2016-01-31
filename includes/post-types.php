<?php
/**
 * Registers and sets up the custom post type
 *
 * @since 1.0
 * @return void
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function edd_ppe_setup_post_type() {

	$labels = array(
		'name' 				=> _x( 'Emails', 'post type general name', 'edd-ppe' ),
		'singular_name' 	=> _x( 'Email', 'post type singular name', 'edd-ppe' ),
		'add_new' 			=> __( 'Add New', 'edd-ppe' ),
		'add_new_item' 		=> __( 'Add New Email', 'edd-ppe' ),
		'edit_item' 		=> __( 'Edit Email', 'edd-ppe' ),
		'new_item' 			=> __( 'New Email', 'edd-ppe' ),
		'all_items' 		=> __( 'All Emails', 'edd-ppe' ),
		'view_item' 		=> __( 'View Email', 'edd-ppe' ),
		'search_items' 		=> __( 'Search Emails', 'edd-ppe' ),
		'not_found' 		=> __( 'No Emails found', 'edd-ppe' ),
		'not_found_in_trash'=> __( 'No Emails found in Trash', 'edd-ppe' ),
		'parent_item_colon' => '',
		'menu_name' 		=> __( 'Emails', 'edd-ppe' )
	);

	$args = array(
		'labels' 			=> apply_filters( 'edd_ppe_post_type_labels', $labels ),
		'public' 			=> false,
		'query_var' 		=> false,
		'rewrite' 			=> false,
		'show_ui'           => false,
		'capability_type' 	=> edd_ppe_capability_type(),
		'map_meta_cap'      => true,
		'supports' 			=> array( 'title' ),
		'can_export'		=> true
	);
	register_post_type( 'edd_receipt', $args );
}
add_action( 'init', 'edd_ppe_setup_post_type' );
