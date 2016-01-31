<?php
/**
 * Admin Messages
 *
 * @since 1.0
 * @return void
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function edd_ppe_admin_messages() {

	if ( isset( $_GET['edd-message'] ) && 'receipt_added' == $_GET['edd-message'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-receipt-added', __( 'Email added.', 'edd-ppe' ), 'updated' );
	}

	if ( isset( $_GET['edd-message'] ) && 'receipt_add_failed' == $_GET['edd-message'] && current_user_can( edd_ppe_capability_type() ) ) {
		add_settings_error( 'edd-ppe-notices', 'edd-receipt-add-fail', __( 'There was a problem adding your email, please try again.', 'edd-ppe' ), 'error' );
	}

	if ( isset( $_GET['edd-message'] ) && 'receipt_updated' == $_GET['edd-message'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-receipt-updated', __( 'Email updated.', 'edd-ppe' ), 'updated' );
	}

	if ( isset( $_GET['edd-message'] ) && 'receipt_update_failed' == $_GET['edd-message'] && current_user_can( edd_ppe_capability_type() ) ) {
		add_settings_error( 'edd-ppe-notices', 'edd-receipt-updated-fail', __( 'There was a problem updating your email, please try again.', 'edd-ppe' ), 'error' );
	}

	if ( isset( $_GET['edd-action'] ) && 'send_test_email' == $_GET['edd-action'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-test-email-sent', __( 'Test Email Sent.', 'edd-ppe' ), 'updated' );
	}

	if ( isset( $_GET['edd-action'] ) && 'delete_receipt' == $_GET['edd-action'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-receipt-deleted', __( 'Email deleted.', 'edd-ppe' ), 'updated' );
	}

	if ( isset( $_GET['edd-action'] ) && 'activate_receipt' == $_GET['edd-action'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-receipt-activated', __( 'Email activated.', 'edd-ppe' ), 'updated' );
	}

	if ( isset( $_GET['edd-action'] ) && 'deactivate_receipt' == $_GET['edd-action'] && current_user_can( edd_ppe_capability_type() ) ) {
		 add_settings_error( 'edd-ppe-notices', 'edd-receipt-deactivated', __( 'Email deactivated.', 'edd-ppe' ), 'updated' );
	}

	settings_errors( 'edd-ppe-notices' );
}
add_action( 'admin_notices', 'edd_ppe_admin_messages' );
