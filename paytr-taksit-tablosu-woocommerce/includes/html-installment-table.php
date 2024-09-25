<?php
// Get Options
$option_merchant_id       = get_option( 'woocommerce_paytrtaksit_merchant_id' );
$option_token             = get_option( 'woocommerce_paytrtaksit_token' );
$option_max_installment   = get_option( 'woocommerce_paytrtaksit_max_installment' );
$option_extra_installment = get_option( 'woocommerce_paytrtaksit_extra_installment' );
$option_amount            = sanitize_text_field( $_GET['paytr_installment_amount'] );

echo '<div id="paytr_taksit_tablosu"></div>';
printf( '<script src="https://www.paytr.com/odeme/taksit-tablosu/v2?token=%1$s&merchant_id=%2$s&amount=%3$s&taksit=%4$s&tumu=%5$s"></script>', sanitize_text_field( $option_token ), sanitize_text_field( $option_merchant_id ), $option_amount, sanitize_text_field( $option_max_installment ), sanitize_text_field( $option_extra_installment ) );