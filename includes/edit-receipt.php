<?php
/**
 * Edit Receipt Page
 *
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['receipt'] ) || ! is_numeric( $_GET['receipt'] ) ) {
	wp_die( __( 'Something went wrong.', 'edd-ppe' ), __( 'Error', 'edd-ppe' ) );
}

$receipt_id  = absint( $_GET['receipt'] );
$receipt     = edd_ppe_get_receipt( $receipt_id );

?>
<h2><?php _e( 'Edit Email', 'edd-ppe' ); ?> - <a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-receipts' ); ?>" class="button-secondary"><?php _e( 'Go Back', 'edd-ppe' ); ?></a></h2>
<form id="edd-edit-receipt" action="" method="post">
	<?php do_action( 'edd_edit_receipt_form_top', $receipt_id, $receipt ); ?>
	<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="download"><?php printf( __( 'Select %s', 'edd-ppe' ), edd_get_label_singular() ); ?></label>
				</th>
				<td>
					<?php

					// current download
					$include = get_post_meta( $receipt_id, '_edd_receipt_download', true );

					// remove downloads from dropdown
					$excluded_products = edd_ppe_get_meta_values();
					$excluded_products = array_filter( $excluded_products );

					$download_to_remove = array_keys( $excluded_products, $include );

					foreach( $download_to_remove as $download ) {
					    unset( $excluded_products[$download] );
					}

					$products = get_posts( array( 'post_type' => 'download', 'exclude' => $excluded_products, 'nopaging' => true, 'orderby' => 'title', 'order' => 'ASC' ) ); ?>


					<select name="download" id="download">

						<?php if ( $products ) : ?>

						<option><?php printf( __( 'Select %s', 'edd-ppe' ), strtolower( edd_get_label_singular() ) ); ?></option>

						<?php foreach ( $products as $product ) {
						?>
						<option value="<?php echo absint( $product->ID ); ?>" <?php echo selected( edd_ppe_get_receipt_download( $receipt_id ), $product->ID, false ); ?>><?php echo esc_html( get_the_title( $product->ID ) ); ?></option>

						<?php } ?>

						<?php else : ?>

							<option><?php printf( __( 'No %s found', 'edd-ppe' ), edd_get_label_plural() ); ?></option>

						<?php endif; ?>

					</select>

					<p class="description"><?php printf( __( 'Select the %s for this email', 'edd-ppe' ), strtolower( edd_get_label_singular() ) ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="subject"><?php _e( 'Purchase Email Subject', 'edd-ppe' ); ?></label>
				</th>
				<td>
					<p><input type="text" class="widefat" name="subject" id="subject" value="<?php echo esc_attr( stripslashes( $receipt->post_excerpt ) ); ?>" size="30" /></p>
					<p class="description"><?php printf( __( 'Enter the email subject line for this %s. Available tags {download_name}, {sitename}', 'edd-ppe' ), strtolower( edd_get_label_singular() ) ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="email"><?php _e( 'Email', 'edd-ppe' ); ?></label>
				</th>
				<td>
					<?php wp_editor( $receipt->post_content, 'email' ); echo '<p>' . edd_get_emails_tags_list() . edd_ppe_list_custom_email_tags() . '</p>'; ?>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="edd-status"><?php _e( 'Status', 'edd-ppe' ); ?></label>
				</th>
				<td>
					<select name="status" id="edd-status">
						<option value="active" <?php selected( $receipt->post_status, 'active' ); ?>><?php _e( 'Active', 'edd-ppe' ); ?></option>
						<option value="inactive"<?php selected( $receipt->post_status, 'inactive' ); ?>><?php _e( 'Inactive', 'edd-ppe' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The status of this email', 'edd-ppe' ); ?></p>
				</td>
			</tr>

		</tbody>
	</table>

	<?php do_action( 'edd_edit_receipt_form_bottom', $receipt_id, $receipt ); ?>

	<p class="submit">
		<input type="hidden" name="edd-action" value="edit_receipt"/>
		<input type="hidden" name="receipt-id" value="<?php echo absint( $_GET['receipt'] ); ?>" />
		<input type="hidden" name="edd-receipt" value="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-receipts' ) ); ?>" />
		<input type="hidden" name="edd-receipt-nonce" value="<?php echo wp_create_nonce( 'edd_receipt_nonce' ); ?>" />
		<input type="submit" value="<?php _e( 'Update Email', 'edd-ppe' ); ?>" class="button-primary" />
	</p>
</form>
