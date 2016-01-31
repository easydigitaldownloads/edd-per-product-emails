<?php
/**
 * Add Receipt Page
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<h2><?php _e( 'Add New Email', 'edd-ppe' ); ?> - <a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-receipts' ); ?>" class="button-secondary"><?php _e( 'Go Back', 'edd-ppe' ); ?></a></h2>
<form id="edd-add-receipt" action="" method="POST">
	<?php do_action( 'edd_add_receipt_form_top' ); ?>
	<table class="form-table">
		<tbody>

			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="download"><?php printf( __( 'Select %s', 'edd-ppe' ), edd_get_label_singular() ); ?></label>
				</th>
				<td>
					<?php
					$excluded_products = edd_ppe_get_meta_values();

					$excluded_products = array_filter( $excluded_products );

					$products = get_posts( array( 'post_type' => 'download', 'exclude' => $excluded_products, 'nopaging' => true, 'orderby' => 'title', 'order' => 'ASC' ) ); ?>

					<select name="download" id="download">

						<?php if ( $products ) : ?>

						<option><?php printf( __( 'Select %s', 'edd-ppe' ), strtolower( edd_get_label_singular() ) ); ?></option>

						<?php foreach ( $products as $product ) {
						?>
						<option value="<?php echo absint( $product->ID ); ?>"><?php echo esc_html( get_the_title( $product->ID ) ); ?></option>

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
					<p><input type="text" class="widefat" name="subject" id="subject" value="" size="30" /></p>
					<p class="description"><?php printf( __( 'Enter the email subject line for this %s. Available tags {download_name}, {sitename}', 'edd-ppe' ), strtolower( edd_get_label_singular() ) ); ?></p>
				</td>
			</tr>

			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="email"><?php _e( 'Email', 'edd-ppe' ); ?></label>
				</th>
				<td>
					<?php wp_editor( '', 'email' ); echo '<p>' . edd_get_emails_tags_list() . edd_ppe_list_custom_email_tags() . '</p>'; ?>
				</td>
			</tr>

		</tbody>
	</table>
	<?php do_action( 'edd_add_receipt_form_bottom' ); ?>
	<p class="submit">
		<input type="hidden" name="edd-action" value="add_receipt" />
		<input type="hidden" name="edd-receipt" value="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-receipts' ) ); ?>" />
		<input type="hidden" name="edd-receipt-nonce" value="<?php echo wp_create_nonce( 'edd_receipt_nonce' ); ?>" />
		<input type="submit" value="<?php _e( 'Add Email', 'edd-ppe' ); ?>" class="button-primary" />
	</p>
</form>
