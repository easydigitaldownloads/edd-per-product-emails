<?php
/**
 * Receipt Functions
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Retrieve an array of all download IDs that have custom emails configured and are active
 *
 * @since 1.0
 * @param $downloads_ids IDs of downloads
 * @return mixed array if receipts exist, false otherwise
 */
function edd_ppe_get_receipts_download_ids() {

	// get only active receipts
	$receipts = edd_ppe_get_receipts( array( 'post_status' => 'active' ) );

	if ( $receipts ) {

		foreach ( $receipts as $receipt ) {
			$download_ids[] = get_post_meta( $receipt->ID, '_edd_receipt_download', true );
		}

		return $download_ids;
	}

	return false;

}


/**
 * Get Receipts
 *
 * Retrieves an array of all available receipts
 *
 * @since 1.0
 * @param array $args Query arguments
 * @return mixed array if receipts exist, false otherwise
 */
function edd_ppe_get_receipts( $args = array() ) {

	$defaults = array(
		'post_type'      => 'edd_receipt',
		'posts_per_page' => 30,
		'paged'          => null,
		'post_status'    => array( 'active', 'inactive' )
	);

	$args = wp_parse_args( $args, $defaults );

	$receipts = get_posts( $args );

	if ( $receipts )
		return $receipts;

	return false;
}


/**
 * Get Receipt post ID from payment ID
 *
 * @since 1.0
*/
function edd_ppe_get_receipt_id( $download_id ) {

	$args = array(
		'post_type'			=> 'edd_receipt',
		'posts_per_page'	=> -1,
		'meta_key'			=> '_edd_receipt_download',
		'meta_value'		=> $download_id,
		'post_status'		=> array( 'active', 'inactive' )
	);

	$receipts = get_posts( $args );

	if ( $receipts ) {

		foreach ( $receipts as $receipt ) {
			$receipt_id = $receipt->ID;
		}

		return $receipt_id;
	}

	return false;
}


/**
 * Retrieve the receipt's associated download
 *
 * @since 1.0
 * @param int $receipt_download
 * @return string $download
 */
function edd_ppe_get_receipt_download( $receipt_download = null ) {
	$download = get_post_meta( $receipt_download, '_edd_receipt_download', true );

	return apply_filters( 'edd_ppe_get_receipt_download', $download, $receipt_download );
}


/**
 * Gets all meta key values of a particular meta key
 *
 * @since 1.0
*/
function edd_ppe_get_meta_values() {
    global $wpdb;

    $meta_values = $wpdb->get_col( $wpdb->prepare( "
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '_edd_receipt_download'
        AND ( p.post_status = 'active' OR p.post_status = 'inactive')
        AND p.post_type = '%s'
    ", 'edd_receipt' ) );

    return $meta_values;
}


/**
 * Gets all download IDs of active receipts
 *
 * @since 1.0
*/
function edd_ppe_get_active_receipts() {
    global $wpdb;

    $meta_values = $wpdb->get_col( $wpdb->prepare( "
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '_edd_receipt_download'
        AND ( p.post_status = 'active')
        AND p.post_type = '%s'
    ", 'edd_receipt' ) );

    return $meta_values;
}

/**
 * Stores a receipt. If the receipt already exists, it updates it, otherwise it creates a new one.
 *
 * @since 1.0
 * @param string $details
 * @param int $receipt_id
 * @return bool Whether or not receipt was created
 */
function edd_ppe_store_receipt( $details, $receipt_id = null ) {

	$meta = array(
		'download'	=> isset( $details['download'] ) && is_numeric( $details['download'] )	? $details['download'] : '',
	);

	// check if receipt exists
	if ( edd_ppe_receipt_exists( $receipt_id ) && ! empty( $receipt_id ) ) {

		// Update an existing receipt
		$details = apply_filters( 'edd_update_receipt', $details, $receipt_id );

		do_action( 'edd_pre_update_receipt', $details, $receipt_id );

		$post = wp_update_post( array(
			'ID'          => $receipt_id,
			'post_title'  => get_the_title( $details['download'] ),
			'post_status' => $details['status'],
			'post_content' => isset( $details['email'] ) ? wp_kses_post( $details['email'] ) : '',
			'post_excerpt' => isset( $details['subject'] ) ? stripslashes( $details['subject'] ) : '',
		) );

		foreach( $meta as $key => $value ) {
			update_post_meta( $receipt_id, '_edd_receipt_' . $key, $value );
		}

		do_action( 'edd_post_update_receipt', $details, $receipt_id );

		// Receipt updated
		return true;

	} else {

		// Add the receipt
		$details = apply_filters( 'edd_insert_receipt', $details );

		do_action( 'edd_pre_insert_receipt', $details );

		// check that the post title (download name) does not already exist
		$receipt_id = wp_insert_post( array(
			'post_type'   => 'edd_receipt',
			'post_title'  => isset( $details['download'] ) ? get_the_title( $details['download'] ) : '',
			'post_status' => 'inactive', // set to inactive first so the user can test emails
			'post_content' => isset( $details['email'] ) ? wp_kses_post( $details['email'] ) : '', // email content becomes post_content
			'post_excerpt' => isset( $details['subject'] ) ? stripslashes( $details['subject'] ) : '',
		) );

		foreach( $meta as $key => $value ) {
			update_post_meta( $receipt_id, '_edd_receipt_' . $key, $value );
		}

		do_action( 'edd_post_insert_receipt', $details, $receipt_id );

		// Receipt created
		return true;

	}
}


/**
 * Deletes a receipt.
 *
 * @since 1.0
 * @param int $receipt_id Receipt ID (default: 0)
 * @return void
 */
function edd_ppe_remove_receipt( $receipt_id = 0 ) {
	do_action( 'edd_pre_delete_receipt', $receipt_id );

	wp_delete_post( $receipt_id, true );

	do_action( 'edd_post_delete_receipt', $receipt_id );
}


/**
 * Updates a receipt's status from one status to another.
 *
 * @since 1.0
 * @param int $receipt_id Receipt ID (default: 0)
 * @param string $new_status New status (default: active)
 * @return bool
 */
function edd_ppe_update_receipt_status( $receipt_id = 0, $new_status = 'active' ) {
	$receipt = edd_ppe_get_receipt( $receipt_id );

	if ( $receipt ) {
		do_action( 'edd_pre_update_receipt_status', $receipt_id, $new_status, $receipt->post_status );

		wp_update_post( array( 'ID' => $receipt_id, 'post_status' => $new_status ) );

		do_action( 'edd_post_update_receipt_status', $receipt_id, $new_status, $receipt->post_status );

		return true;
	}

	return false;
}


/**
 * Checks to see if a receipt already exists.
 *
 * @since 1.0
 * @param int $receipt_id Receipt ID
 * @return bool
 */
function edd_ppe_receipt_exists( $receipt_id ) {

	if ( edd_ppe_get_receipt( $receipt_id ) )
		return true;

	return false;
}


/**
 * Get Receipt
 *
 * Retrieves a complete receipt by receipt ID.
 *
 * @since 1.0
 * @param string $receipt_id Receipt ID
 * @return array
 */
function edd_ppe_get_receipt( $receipt_id ) {

	$receipt = get_post( $receipt_id );

	if ( get_post_type( $receipt_id ) != 'edd_receipt' )
		return false;

	return $receipt;
}


/**
 * Checks whether a receipt is active.
 *
 * @since 1.0
 * @param int $receipt_id
 * @return bool
 */
function edd_ppe_is_receipt_active( $receipt_id = null ) {

	$receipt = edd_ppe_get_receipt( $receipt_id );
	$return = false;

	if ( $receipt ) {
		if ( $receipt->post_status == 'active' ) {
			$return = true;
		}
	}

	return apply_filters( 'edd_ppe_is_receipt_active', $return, $receipt_id );
}


/**
 * Has Active Receipts
 *
 * Checks if there are any active receipts, returns a boolean.
 *
 * @since 1.0
 * @return bool
 */
function edd_ppe_has_active_receipts() {
	$has_active = false;

	$receipts  = edd_ppe_get_receipts();

	if ( $receipts) {
		foreach ( $receipts as $receipt ) {
			if ( $receipt->post_status == 'active' ) {
				$has_active = true;
				break;
			}
		}
	}

	return $has_active;
}
