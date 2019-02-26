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
	global $edd_options;

	$purchase_id  = $data['purchase_id'];

	// get cart items from purchase ID
	$cart_items = edd_get_payment_meta_cart_details( $purchase_id );

	// loop through each item in cart and add IDs to $product_id array
	foreach ( $cart_items as $product ) {
		$product_ids[] = $product['id'];
	}
	
	// make sure all of the downloads purchase exist as receipts
	if ( isset( $edd_options[ 'edd_ppe_disable_purchase_receipt' ] ) && count( array_intersect( $product_ids, edd_ppe_get_active_receipts() ) ) === count( $product_ids ) ) {

		// Disable standard purchase receipt
		remove_action( 'edd_email_links', 'edd_resend_purchase_receipt' );

	}

	// send the custom email. This will not send admin email
	edd_ppe_email_custom_purchase_receipts( $purchase_id, false );

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
	$cart_items  = edd_get_payment_meta_cart_details( $payment_id );
	$product_ids = array();

	// loop through each item in cart and add IDs to $product_id array
	foreach ( $cart_items as $product ) {
		$product_ids[] = $product['id'];
	}

	// get unique product IDs only (to prevent multiple emails in case of variable pricing)
	$product_ids = array_unique( $product_ids );
  
	foreach ( $product_ids as $product_id ) {

		if ( ! edd_ppe_is_receipt_active( edd_ppe_get_receipt_id( $product_id ) ) ) {
			continue;
		}

		$receipt = get_post( edd_ppe_get_receipt_id( $product_id ) );

		// default email body
		$default_email_body = __( "Dear", "edd-ppe" ) . " {name},\n\n";
		$default_email_body .= __( "Thank you for purchasing {download_name}. Please click on the link(s) below to download your files.", "edd-ppe" ) . "\n\n";
		$default_email_body .= "{download_list}\n\n";
		$default_email_body .= "{sitename}";

		// use new EDD 2.1 Email class
		if ( class_exists( 'EDD_Emails' ) ) {

			// get our subject
			$subject = apply_filters( 'edd_ppe_purchase_subject', $receipt->post_excerpt ? wp_strip_all_tags( $receipt->post_excerpt, true ) : __( 'Purchase Receipt - {download_name}', 'edd-ppe' ), $payment_id );

			// run subject through the plugin's custom email tag function
			// this runs before so apostrophe's can correctly be replaced when {sitename} is used in the subject. This will eventually be fixed in EDD core
			$subject = edd_ppe_email_template_tags( $subject, $product_id, $payment_id );

			// run subject through the standard EDD email tag function
			$subject = edd_do_email_tags( $subject, $payment_id );

			// message
			$message = apply_filters( 'edd_ppe_purchase_body', $receipt->post_content ? $receipt->post_content : $default_email_body );

			// run our message through the standard EDD email tag function
			$message = apply_filters( 'edd_purchase_receipt', edd_do_email_tags( $message, $payment_id ), $payment_id, $payment_data );

			// run the message through the plugin's custom email tag function
			$message = edd_ppe_email_template_tags( $message, $product_id, $payment_id );

			// add download name as email heading. Off by default
			// will introduce a checkbox in admin to turn all headings on rather than turn them on now which may mess up emails
			if ( apply_filters( 'edd_ppe_email_heading', false ) ) {
				EDD()->emails->__set( 'heading', get_the_title( $product_id ) );
			}

			$attachments = apply_filters( 'edd_receipt_attachments', array(), $payment_id, $payment_data );

			// send an email for each custom email
			EDD()->emails->send( $email, $subject, $message, $attachments );

		} else {
			// support older EDD versions where the EDD Email Class does not exist
			$subject = apply_filters( 'edd_ppe_purchase_subject', $receipt->post_excerpt ? wp_strip_all_tags( $receipt->post_excerpt, true ) : __( 'Purchase Receipt - {download_name}', 'edd-ppe' ), $payment_id );

			$body = apply_filters( 'edd_ppe_purchase_body', $receipt->post_content ? $receipt->post_content : $default_email_body );
			$body = edd_ppe_email_template_tags( $body, $product_id, $payment_id );

			$subject = edd_email_template_tags( $subject, $payment_data, $payment_id, $payment_id );
			$subject = edd_ppe_email_template_tags( $subject, $product_id, $payment_id );

			$message = edd_get_email_body_header();
			$message .= apply_filters( 'edd_purchase_receipt', edd_email_template_tags( $body, $payment_data, $payment_id ), $payment_id, $payment_data );
			$message .= edd_get_email_body_footer();

			$from_name = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo('name');
			$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

			$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
			$headers .= "Reply-To: ". $from_email . "\r\n";
			//$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8\r\n";
			$headers = apply_filters( 'edd_receipt_headers', $headers, $payment_id, $payment_data );

			// Allow add-ons to add file attachments
			$attachments = apply_filters( 'edd_receipt_attachments', array(), $payment_id, $payment_data );

			wp_mail( $email, $subject, $message, $headers, $attachments );

		}

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



	// use new EDD 2.1 Email class
	if ( class_exists( 'EDD_Emails' ) ) {

		// we're on the main screen of edd receipts, get relevant subject and body for test email
		if ( isset( $_GET['page'] ) && 'edd-receipts' == $_GET['page'] && 'download' == $typenow && in_array( $pagenow, array( 'edit.php' ) ) ) {
			$subject = $receipt->post_excerpt ? $receipt->post_excerpt : $default_email_subject;
			$body = $receipt->post_content ? $receipt->post_content : $default_email_body;
		}

		// run subject through email_preview_subject_template_tags() function
		$subject = apply_filters( 'edd_ppe_purchase_receipt_subject', edd_ppe_email_preview_subject_template_tags( $subject, $receipt_id ), 0, array() );

		// run subject through the standard EDD email tag function
		$subject = edd_do_email_tags( $subject, 0 );

		// message
		$message = apply_filters( 'edd_ppe_purchase_body', $receipt->post_content ? $receipt->post_content : $default_email_body );
		$message = edd_email_preview_template_tags( $body, 0 );

		// add download name as email heading. Off by default
		// will introduce a checkbox in admin to turn all headings on rather than turn them on now which may mess up emails
		if ( apply_filters( 'edd_ppe_email_heading', false ) ) {
			EDD()->emails->__set( 'heading', get_the_title( $receipt->ID ) );
		}

		EDD()->emails->send( edd_get_admin_notice_emails(), $subject, $message );

	} else {
		// we're on the main screen of edd receipts, get relevant subject and body for test email
		if ( isset( $_GET['page'] ) && 'edd-receipts' == $_GET['page'] && 'download' == $typenow && in_array( $pagenow, array( 'edit.php' ) ) ) {
			$subject = $receipt->post_excerpt ? $receipt->post_excerpt : $default_email_subject;
			$body = $receipt->post_content ? $receipt->post_content : $default_email_body;
		}

		// run subject through email_preview_subject_template_tags() function
		$subject = apply_filters( 'edd_ppe_purchase_receipt_subject', edd_ppe_email_preview_subject_template_tags( $subject, $receipt_id ), 0, array() );

		$message = edd_get_email_body_header();

		// will remove the edd_email_preview_templage_tags() function when new EDD version is released
		$message .= apply_filters( 'edd_purchase_receipt', function_exists( 'edd_email_preview_template_tags' ) ? edd_email_preview_template_tags( $body ) : edd_email_preview_templage_tags( $body ), null, null );
		$message .= edd_get_email_body_footer();

		$from_name = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo('name');
		$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= "Reply-To: ". $from_email . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$headers = apply_filters( 'edd_test_purchase_headers', $headers );

		wp_mail( edd_get_admin_notice_emails(), $subject, $message, $headers );
	}

}


/**
 * Add email tags
 *
 * {download_name}
 * {sitename}
 * {license_key}
 *
 * @since 1.0
*/
function edd_ppe_email_template_tags( $input, $product_id, $payment_id ) {

	// download name
	$download_name = html_entity_decode( get_the_title( $product_id ), ENT_COMPAT, 'UTF-8' );

	// used by subject line and body
	$input = str_replace( '{download_name}', $download_name, $input );

	// blog name
	$blog_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	// used by the subject line
	$input = str_replace( '{sitename}', $blog_name, $input );

	return apply_filters( 'edd_ppe_email_template_tags', $input, $product_id, $payment_id );

}

/**
 * Preview subject line template tags
 *
 * @since 1.0
*/
function edd_ppe_email_preview_subject_template_tags( $subject, $receipt_id ) {

	// get the download's title from the '_edd_receipt_download' meta key which is listed against the receipt ID
	$download_name = html_entity_decode( get_the_title( get_post_meta( $receipt_id, '_edd_receipt_download', true ) ), ENT_COMPAT, 'UTF-8' );

	$blog_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = str_replace( '{sitename}', $blog_name, $subject );
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
 * List custom email tags
 *
 * @since 1.1
*/
function edd_ppe_list_custom_email_tags() {

	$tags = '{download_name} - ' . sprintf( __( 'The %s name', 'edd-ppe' ), strtolower( edd_get_label_singular() ) );

	return apply_filters( 'edd_ppe_list_custom_email_tags', $tags );
}
