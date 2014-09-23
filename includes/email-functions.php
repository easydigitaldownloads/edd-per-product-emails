<?php
/**
 * Email functions
 *
 * @since 1.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
* Triggers Custom Purchase Receipts to be sent after the payment status is updated
*
* @since 1.0
* @param int $payment_id Payment ID
* @return void
*/
function edd_ppe_trigger_purchase_receipt( $payment_id ) {

	// Make sure we don't send a purchase receipt while editing a payment
	if ( isset( $_POST['edd-action'] ) && 'edit_payment' == $_POST['edd-action'] )
		return;

	// Send custom email
	edd_ppe_email_custom_purchase_receipts( $payment_id );
}
add_action( 'edd_complete_purchase', 'edd_ppe_trigger_purchase_receipt', 999, 1 );	

/**
 * Resend the custom Email Purchase Receipts. (This can be done from the Payment History page)
 *
 * @since 1.0.3
 * @param array $data Payment Data
 * @return void
 */
function edd_ppe_resend_custom_purchase_receipts( $data ) {
	$purchase_id = $data['purchase_id'];
	edd_ppe_email_custom_purchase_receipts( $purchase_id, false ); // doesn't send admin email
}
add_action( 'edd_email_links', 'edd_ppe_resend_custom_purchase_receipts', 9 );

/**
 * Email the custom download link(s) and payment confirmation to the buyer in a
 * customizable Purchase Receipt
 *
 * @since 1.0
 * @param int $payment_id Payment ID
 * @param bool $admin_notice Whether to send the admin email notification or not (default: true)
 * @return void
 */
function edd_ppe_email_custom_purchase_receipts( $payment_id, $admin_notice = true ) {

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_id      = edd_get_payment_user_id( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$email        = edd_get_payment_user_email( $payment_id );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	// get cart items from payment ID
	$cart_items = edd_get_payment_meta_cart_details( $payment_id );

	// loop through each item in cart and add IDs to $product_id array
	foreach ( $cart_items as $product ) {
		$product_ids[] = $product['id'];
	}

	foreach ( $product_ids as $product_id ) {

		if ( ! edd_ppe_is_receipt_active( edd_ppe_get_receipt_id( $product_id ) ) )
		 	continue;

		$receipt = get_post( edd_ppe_get_receipt_id( $product_id ) );
		
		// default email body
		$default_email_body = __( "Dear", "edd-ppe" ) . " {name},\n\n";
		$default_email_body .= __( "Thank you for purchasing {download_name}. Please click on the link(s) below to download your files.", "edd-ppe" ) . "\n\n";
		$default_email_body .= "{download_list}\n\n";
		$default_email_body .= "{sitename}";

		$subject = apply_filters( 'edd_ppe_purchase_subject', $receipt->post_excerpt ? wp_strip_all_tags( $receipt->post_excerpt, true ) : __( 'Purchase Receipt - {download_name}', 'edd-ppe' ), $payment_id );

		$message = apply_filters( 'edd_ppe_purchase_body', $receipt->post_content ? $receipt->post_content : $default_email_body );
		$message = edd_ppe_email_template_tags( $message, $product_id );

		$subject = edd_email_template_tags( $subject, $payment_data, $payment_id );
		$subject = edd_ppe_email_template_tags( $subject, $product_id );

		EDD()->emails->send( $email, $subject, $message );

	}

}


/**
 * Disable standard purchase receipt, but only if all products purchased have custom emails configured
 * @since 1.0.1
*/
function edd_ppe_disable_purchase_receipt( $payment_id, $admin_notice = true ) {
	global $edd_options;

	$payment_data = edd_get_payment_meta( $payment_id );

	$cart_items = edd_get_payment_meta_cart_details( $payment_id );

	foreach ( $cart_items as $product ) {
		$product_ids[] = $product['id'];
	}

	// make sure all of the downloads purchase exist as receipts
	if ( isset( $edd_options[ 'edd_ppe_disable_purchase_receipt' ] ) && count( array_intersect( $product_ids, edd_ppe_get_active_receipts() ) ) === count( $product_ids ) ) {

		// prevents standard purchase receipt from firing
		remove_action( 'edd_complete_purchase', 'edd_trigger_purchase_receipt', 999, 1 );

		//the above remove_action disables the admin notification, so let's get it going again
		if ( $admin_notice && ! edd_admin_notices_disabled( $payment_id ) ) {
			do_action( 'edd_admin_sale_notice', $payment_id, $payment_data );
		}
		
	}
	
}
add_action( 'edd_complete_purchase', 'edd_ppe_disable_purchase_receipt', -999, 2 );


/**
 * Add {download_name} to the allowed subject template tags
 *
 * @since 1.0
*/
function edd_ppe_email_template_tags( $input, $product_id ) {

	$download_name = get_the_title( $product_id );

	// used by the subject line
	$input = str_replace( '{sitename}', get_bloginfo( 'name' ), $input );

	// used by subject line and body
	$input = str_replace( '{download_name}', $download_name, $input );

	return $input;

}


/**
 * Trigger the sending of a Test Email.
 *
 * @since 1.0
 * @param array $data Contains post_type, page, edd-action, receipt ID and _wpnonce ID
 * @return void
 */
function edd_ppe_send_test_email( $data ) {

	if ( ! wp_verify_nonce( $data['_wpnonce'], 'edd-ppe-test-email' ) )
		return;

	$receipt_id = $data['receipt'];
	edd_ppe_test_purchase_receipt( $receipt_id );

}
add_action( 'edd_send_test_email', 'edd_ppe_send_test_email' );


/**
 * Send test email
 * 
 * @todo remove edd_email_preview_templage_tags() function check when EDD supports it
 * @since 1.0
*/
function edd_ppe_test_purchase_receipt( $receipt_id = 0 ) {

	global $pagenow, $typenow;

	$receipt = edd_ppe_get_receipt( $receipt_id );

	// default email subject
	$default_email_subject = __( "Thanks for purchasing {download_name}", "edd-ppe" );

	// default email body
	$default_email_body = __( "Dear", "edd-ppe" ) . " {name},\n\n";
	$default_email_body .= __( "Thank you for purchasing {download_name}. Please click on the link(s) below to download your files.", "edd-ppe" ) . "\n\n";
	$default_email_body .= "{download_list}\n\n";
	$default_email_body .= "{sitename}";

	// we're on the main screen of edd receipts, get relevant subject and body for test email
	if ( isset( $_GET['page'] ) && 'edd-receipts' == $_GET['page'] && 'download' == $typenow && in_array( $pagenow, array( 'edit.php' ) ) ) {
		$subject = $receipt->post_excerpt ? $receipt->post_excerpt : $default_email_subject;
		$message = $receipt->post_content ? $receipt->post_content : $default_email_body;	
	}
	
	// run subject through email_preview_subject_template_tags() function
	$subject = apply_filters( 'edd_ppe_purchase_receipt_subject', edd_ppe_email_preview_subject_template_tags( $subject, $receipt_id ), 0, array() );

	EDD()->emails->send( edd_get_admin_notice_emails(), $subject, $message );
}


/**
 * Preview subject line template tags
 *
 * @since 1.0
*/
function edd_ppe_email_preview_subject_template_tags( $subject, $receipt_id ) {

	// get the download's title from the '_edd_receipt_download' meta key which is listed against the receipt ID 
	$download_name = get_the_title( get_post_meta( $receipt_id, '_edd_receipt_download', true ) );

	$subject = str_replace( '{sitename}', get_bloginfo( 'name' ), $subject );
	$subject = str_replace( '{download_name}', $download_name, $subject );

	return apply_filters( 'edd_ppe_preview_subject_template_tags', $subject );
}


/**
 * Add {download_name} to the allowed preview template tags
 *
 * @since 1.0
*/
function edd_ppe_email_preview_template_tags( $message ) {

	$post_id = isset( $_GET['receipt'] ) ? $_GET['receipt'] : '';
	
	$download_name = get_the_title( $post_id );
	$message = str_replace( '{download_name}', $download_name, $message );

	return $message;

}
add_filter( 'edd_email_preview_template_tags', 'edd_ppe_email_preview_template_tags' );


/**
 * Add {download_name} to list of available tags
 *
 * @since 1.0
*/
function edd_ppe_get_purchase_receipt_template_tags( $tags ) {
	$tags .= '<br />' . '{download_name} - ' . sprintf( __( 'The %s name', 'edd-ppe'), strtolower( edd_get_label_singular() ) );

	return $tags;
}
add_filter( 'edd_purchase_receipt_template_tags_description', 'edd_ppe_get_purchase_receipt_template_tags' );