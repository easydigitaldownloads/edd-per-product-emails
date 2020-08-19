<?php
/**
 * Integration functions to make Software Licenses compatible with Per Product Emails
 *
 * @package     EDD\PerProductEmails\Functions
 * @since       1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates EDD All Access with the EDD Software Licensing extension
 *
 * @since v1.1.3
 */
class EDD_PPE_Software_Licensing {

	/**
	 * Get things started
	 *
	 * @since  1.1.3
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'EDD_Software_Licensing' ) ) {
			return;
		}

		add_filter( 'edd_ppe_email_template_tags', array( $this, 'replace_license_key_email_tag_with_license_key' ), 10, 3 );
		add_filter( 'edd_ppe_list_custom_email_tags', array( $this, 'add_license_key_email_tag' ) );

	}

	/**
	 * Add the note about th ability to add {license_key} to a per product email.
	 *
	 * @since       1.1.3
	 * @param       string $email_tags An HTML string consisting of all the acceptable email tags.
	 * @return      string $email_tags An HTML string consisting of all the acceptable email tags.
	 */
	function add_license_key_email_tag( $email_tags ){
		$email_tags .= '<br/>{license_key} - ' . sprintf( __( 'Show the license key for the %s', 'edd-ppe' ), strtolower( edd_get_label_singular() ) );
		return $email_tags;
	}

	/**
	 * Add the note about th ability to add {license_key} to a per product email.
	 *
	 * @since       1.1.3
	 * @param       string $input The text in the per product email that is being sent to the customer.
	 * @param       string $product_id The id of the product that was purchased.
	 * @param       string $payment_id The id of the payment.
	 * @return      string $input The text in the per product email that is being sent to the customer.
	 */
	function replace_license_key_email_tag_with_license_key( $input, $product_id, $payment_id ){

		// get license key for the download
		$license = edd_software_licensing()->get_license_by_purchase( $payment_id, $product_id );

		if ( $license ) {
			$license_key = $license->license_key;
		}else{
			$license_key = __( 'No License Key found.', 'edd-ppe' );
		}

		// used by the body
		$input = str_replace( '{license_key}', $license_key, $input );

		return $input;

	}

}
