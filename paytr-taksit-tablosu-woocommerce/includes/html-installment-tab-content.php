<div>
	<?php
	// Get Options
	$option_content_title      = sanitize_text_field( get_option( 'woocommerce_paytrtaksit_content_title' ) );
	$option_description_top    = sanitize_text_field( get_option( 'woocommerce_paytrtaksit_description_top' ) );
	$option_description_bottom = sanitize_text_field( get_option( 'woocommerce_paytrtaksit_description_bottom' ) );
	$option_tax_included       = sanitize_text_field( get_option( 'woocommerce_paytrtaksit_tax_included' ) );

	// Get Product
	$product = new WC_Product( get_the_ID() );

	if ( $option_tax_included ) {
		$price = wc_get_price_including_tax( $product );
	} else {
		$price = wc_get_price_excluding_tax( $product );
	}

	// Register Style
	wp_register_style( 'paytr_installment_table_style', PAYTRTT_PLUGIN_URL . "/assets/css/style.css", false, '1.0', 'all' );
	wp_enqueue_style( 'paytr_installment_table_style' );

	// Register Script
	wp_register_script( 'paytr_installment_table_js', PAYTRTT_PLUGIN_URL . '/assets/js/paytr.js' );
	$paytr_js_array = array(
		'paytr_base_url'      => PAYTRTT_PLUGIN_URL,
		'paytr_product_price' => $price
	);
	wp_localize_script( 'paytr_installment_table_js', 'paytr_object', $paytr_js_array );
	wp_enqueue_script( 'paytr_installment_table_js' );

	if ( $option_content_title != '' || isset( $option_content_title ) ) {
		echo '<h2>' . $option_content_title . '</h2>';
	}

	if ( ! empty( $option_description_top ) ) {
		echo '<div class="paytr-installment-table-description-top">' . $option_description_top . '</div>';
	}

	echo '<div id="paytrInstallmentTableContent"></div>';

	if ( ! empty( $option_description_bottom ) ) {
		echo '<div class="paytr-installment-table-description-top">' . $option_description_bottom . '</div>';
	}

	?>
</div>