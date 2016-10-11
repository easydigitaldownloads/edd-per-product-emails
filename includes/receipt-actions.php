<?php
/**
 * Receipt Actions
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Sets up and stores a new receipt
 *
 * @since 1.0
 * @param array $data Receipt data
 * @uses edd_store_receipt()
 * @return void
 */
function edd_ppe_add_receipt( $data ) {
	if ( isset( $data['edd-receipt-nonce'] ) && wp_verify_nonce( $data['edd-receipt-nonce'], 'edd_receipt_nonce' ) ) {
		// Setup the receipt details
		$posted = array();

		foreach ( $data as $key => $value ) {

			if ( $key != 'edd-receipt-nonce' && $key != 'edd-action' && $key != 'edd-receipt' ) {

				if ( 'email' == $key ) {
					$posted[ $key ] = $value;
				} elseif ( is_string( $value ) || is_int( $value ) ) {
					$posted[ $key ] = edd_sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					$posted[ $key ] = array_map( 'absint', $value );
				}
			}
		}

		if ( edd_ppe_store_receipt( $posted ) ) {
			wp_safe_redirect( add_query_arg( 'edd-message', 'receipt_added', $data['edd-receipt'] ) ); edd_die();
		} else {
			wp_safe_redirect( add_query_arg( 'edd-message', 'receipt_add_failed', $data['edd-receipt'] ) ); edd_die();
		}
	}
}
add_action( 'edd_add_receipt', 'edd_ppe_add_receipt' );


/**
 * Saves an edited receipt
 *
 * @since 1.0
 * @param array $data Receipt data
 * @return void
 */
function edd_ppe_edit_receipt( $data ) {
	if ( isset( $data['edd-receipt-nonce'] ) && wp_verify_nonce( $data['edd-receipt-nonce'], 'edd_receipt_nonce' ) ) {
		// Setup the receipt details
		$receipt = array();

		foreach ( $data as $key => $value ) {

			if ( $key != 'edd-receipt-nonce' && $key != 'edd-action' && $key != 'receipt-id' && $key != 'edd-receipt' ) {

				if ( 'email' == $key ) {
					$receipt[ $key ] = $value;
				} elseif ( is_string( $value ) || is_int( $value ) ) {
					$receipt[ $key ] = edd_sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					$receipt[ $key ] = array_map( 'absint', $value );
				}
			}
		}

		if ( edd_ppe_store_receipt( $receipt, $data['receipt-id'] ) ) {
			wp_safe_redirect( add_query_arg( 'edd-message', 'receipt_updated', $data['edd-receipt'] ) ); edd_die();
		} else {
			wp_safe_redirect( add_query_arg( 'edd-message', 'receipt_update_failed', $data['edd-receipt'] ) ); edd_die();
		}
	}
}
add_action( 'edd_edit_receipt', 'edd_ppe_edit_receipt' );


/**
 * Listens for when a receipt delete button is clicked and deletes the receipt
 *
 * @since 1.0
 * @param array $data Receipt data
 * @uses edd_ppe_remove_receipt()
 * @return void
 */
function edd_ppe_delete_receipt( $data ) {

	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'edd_receipt_nonce' ) )
		wp_die( __( 'Trying to cheat or something?', 'edd-ppe' ), __( 'Error', 'edd-ppe' ) );

	$receipt_id = $data['receipt'];

	edd_ppe_remove_receipt( $receipt_id );
}
add_action( 'edd_delete_receipt', 'edd_ppe_delete_receipt' );


/**
 * Activates Receipt
 *
 * Sets a receipt's status to active
 *
 * @since 1.0
 * @param array $data Receipt data
 * @uses edd_ppe_update_receipt_status()
 * @return void
 */
function edd_ppe_activate_receipt( $data ) {
	$id = $data['receipt'];
	edd_ppe_update_receipt_status( $id, 'active' );
}
add_action( 'edd_activate_receipt', 'edd_ppe_activate_receipt' );


/**
 * Deactivate Receipt
 *
 * Sets a receipt's status to Inactive
 *
 * @since 1.0
 * @param array $data Receipt data
 * @uses edd_ppe_update_receipt_status()
 * @return void
*/
function edd_ppe_deactivate_receipt( $data) {
	$id = $data['receipt'];
	edd_ppe_update_receipt_status( $id, 'inactive' );
}
add_action( 'edd_deactivate_receipt', 'edd_ppe_deactivate_receipt' );
