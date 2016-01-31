<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the settings to the Misc section
 *
 * @since 1.0
*/
function edd_ppe_add_settings( $settings ) {

  $edd_ppe_settings = array(

		array(
			'id' => 'edd_ppe_settings',
			'name' => '<strong>' . __( 'Per Product Emails', 'edd-ppe' ) . '</strong>',
			'desc' => __( 'Configure EDD Per Product Email Settings', 'edd-ppe' ),
			'type' => 'header'
		),
		array(
			'id' => 'edd_ppe_disable_purchase_receipt',
			'name' => __( 'Disable Standard Purchase Receipt', 'edd-ppe' ),
			'desc' => sprintf( __( 'Prevent the standard purchase receipt from being sent to the customer. The customer will still receive the standard purchase receipt if there are %s purchased that do not have custom emails configured.', 'edd-ppe' ), strtolower( edd_get_label_plural() ) ),
			'type' => 'checkbox'
		),

	);

	return array_merge( $settings, $edd_ppe_settings );

}
add_filter( 'edd_settings_extensions', 'edd_ppe_add_settings' );

/**
 * Capability type required to manage Per Product Emails
 *
 * @since 1.1
 */
function edd_ppe_capability_type() {
    return apply_filters( 'edd_ppe_capability_type', 'manage_shop_settings' );
}
