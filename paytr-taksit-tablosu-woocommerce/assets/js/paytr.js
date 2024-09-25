jQuery(document).ready(function($) {
    let contentDiv = $('#paytrInstallmentTableContent');
    let price      = paytr_object.paytr_product_price;

    fncPaytrInstallmentTable(parseFloat(price).toFixed(2));

    $('.single_variation_wrap').on('show_variation', function(event, variation) {
        fncPaytrInstallmentTable(parseFloat(variation.display_price).toFixed(2));
    });

    function fncPaytrInstallmentTable(price) {
        const script = document.createElement('script');
        script.type  = 'text/javascript';
        script.src   = 'https://www.paytr.com/odeme/taksit-tablosu/v2?token=' + paytr_object.paytr_token + '&merchant_id=' + paytr_object.paytr_merchant_id +
            '&amount=' + price + '&taksit=' + paytr_object.paytr_max_installment + '&tumu=' + paytr_object.paytr_extra_installment + '';

        contentDiv.find('#paytr_taksit_tablosu').empty();
        contentDiv.find('script').remove();
        contentDiv.append(script);
    }
});