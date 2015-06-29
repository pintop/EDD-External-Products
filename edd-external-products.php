<?php
/*
Plugin Name: Easy Digital Downloads - External Products
Plugin URL: http://easydigitaldownloads.com
Description: Add an "external URL" to your Download post to redirect the purchase button to a different site. Handy both for affiliate-based product lists and referencing projects that are hosted elsewhere.
Version: 1.0.0
Author: WebDevStudios
Author URI: http://webdevstudios.com
*/

/**
 * External Product URL Field
 *
 * Adds field do the EDD Downloads meta box for specifying the "External Product URL"
 *
 * @since 1.0.0
 * @param integer $post_id Download (Post) ID
 */
function edd_external_product_render_field( $post_id ) {
	$edd_external_url = get_post_meta( $post_id, '_edd_external_url', true );
	$edd_external_button_text = get_post_meta( $post_id, '_edd_external_button_text', true );
?>
	<p><strong><?php _e( 'External Product URL:', 'edd-external-product' ); ?></strong></p>
	<label for="edd_external_url">
		<input type="text" name="_edd_external_url" id="edd_external_url" value="<?php echo esc_attr( $edd_external_url ); ?>" size="80" placeholder="http://"/>
		<br/><?php _e( 'The external URL (including http://) to use for the purchase button. Leave blank for standard products.', 'edd-external-product' ); ?>
	</label>
	<p><strong><?php _e( 'External Product Button Text:', 'edd-external-product' ); ?></strong></p>
	<label for="edd_external_button_text">
		<input type="text" name="_edd_external_button_text" id="edd_external_button_text" value="<?php echo esc_attr( $edd_external_button_text ); ?>" size="40" />
		<br/><?php _e( 'The text to use for the purchase button. Leave blank for default text.', 'edd_external_button_text' ); ?>
	</label>
<?php
}
add_action( 'edd_meta_box_fields', 'edd_external_product_render_field', 90 );

/**
 * Add the _edd_external_url field to the list of saved product fields
 *
 * @since  1.0.0
 *
 * @param  array $fields The default product fields list
 * @return array         The updated product fields list
 */
function edd_external_product_save( $fields ) {

	// Add our field
	$fields[] = '_edd_external_url';
	$fields[] = '_edd_external_button_text';

	// Return the fields array
	return $fields;
}
add_filter( 'edd_metabox_fields_save', 'edd_external_product_save' );

/**
 * Sanitize metabox field to only accept URLs
 *
 * @since 1.0.0
*/
function edd_external_product_metabox_save( $new ) {

	// Convert to raw URL to save into wp_postmeta table
	$new = esc_url_raw( $_POST[ '_edd_external_url' ] );

	// Return URL
	return $new;

}
add_filter( 'edd_metabox_save__edd_external_url', 'edd_external_product_metabox_save' );

/**
 * Prevent a download linked to an external URL from being purchased with ?edd_action=add_to_cart&download_id=XXX
 *
 * @since 1.0.0
*/
function edd_external_product_pre_add_to_cart( $download_id ) {

	$edd_external_url = get_post_meta( $download_id, '_edd_external_url', true ) ? get_post_meta( $download_id, '_edd_external_url', true ) : '';

	// Prevent user trying to purchase download using EDD purchase query string
	if ( $edd_external_url )
		wp_die( sprintf( __( 'This download can only be purchased from %s', 'edd-external-product' ), esc_url( $edd_external_url ) ), '', array( 'back_link' => true ) );

}
add_action( 'edd_pre_add_to_cart', 'edd_external_product_pre_add_to_cart' );

/**
 * Override the default product purchase button with an external anchor
 *
 * Only affects products that have an external URL stored
 *
 * @since  1.0.0
 *
 * @param  string    $purchase_form The concatenated markup for the purchase area
 * @param  array    $args           Args passed from {@see edd_get_purchase_link()}
 * @return string                   The potentially modified purchase area markup
 */
function edd_external_product_link( $purchase_form, $args ) {

	// If the product has an external URL set
	if ( $external_url = get_post_meta( $args['download_id'], '_edd_external_url', true ) ) {

		if (!$button_text = get_post_meta( $args['download_id'], '_edd_external_button_text', true ) ){
			$button_text = $args['text'];
		}
		// Open up the standard containers
		$output = '<div class="edd_download_purchase_form">';
		$output .= '<div class="edd_purchase_submit_wrapper">';

		// Output an anchor tag with the same classes as the product button
		$output .= sprintf(
			'<a class="%1$s" href="%2$s" %3$s>%4$s</a>',
			implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ) ) ),
			esc_attr( $external_url ),
			apply_filters( 'edd_external_product_link_attrs', '', $args ),
			esc_attr( $button_text )
		);

		// Close the containers
		$output .= '</div><!-- .edd_purchase_submit_wrapper -->';
		$output .= '</div><!-- .edd_download_purchase_form -->';

		// Replace the form output with our own output
		$purchase_form = $output;
	}

	// Return the possibly modified purchase form
	return $purchase_form;
}
add_filter( 'edd_purchase_download_form', 'edd_external_product_link', 10, 2 );