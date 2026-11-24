<?php

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_PaytrInstallmentTable')) {
    WC_PaytrInstallmentTable::get_instance()->paytr_installment_table_content();
}
