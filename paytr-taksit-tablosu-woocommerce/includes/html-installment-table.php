<?php

if (!defined('ABSPATH')) {
    exit;
}

$amount = isset($_GET['paytr_installment_amount']) ? $_GET['paytr_installment_amount'] : '';
if (!is_string($amount) || !preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/D', $amount) || (float) $amount <= 0) {
    return;
}

echo '<div id="paytr_taksit_tablosu"></div>';
printf('<script src="%s"></script>', esc_url(paytrtt_table_url($amount)));
