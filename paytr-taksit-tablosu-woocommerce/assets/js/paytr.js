jQuery(document).ready(function ($) {
  const contentDiv = $('#paytrInstallmentTableContent');
  const quantityInput = $('input.qty');
  const variationsForm = $('.variations_form');
  const updateButton = $('#updateInstallmentsButton');

  function updateInstallmentTable(price) {
    const total = Number(price);
    if (!Number.isFinite(total) || total <= 0 || total >= 1e12) {
      contentDiv.find('#paytr_taksit_tablosu').empty();
      return;
    }

    const url = new URL(paytr_object.table_url);
    url.searchParams.set('amount', total.toFixed(2));
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url.href;

    contentDiv.find('#paytr_taksit_tablosu').empty().append(script);
  }

  const baseUnitPrice = Number(paytr_object.unit_price);
  let unitPrice = baseUnitPrice;

  function currentTotal() {
    const quantity = Number(quantityInput.val());
    return unitPrice * (Number.isFinite(quantity) && quantity > 0 ? quantity : 1);
  }

  variationsForm.on('found_variation', function (event, variation) {
    unitPrice = Number(variation.display_price);
  }).on('reset_data', function () {
    unitPrice = baseUnitPrice;
  });

  // update button click handling
  updateButton.on('click', function (e) {
    e.preventDefault();
    updateInstallmentTable(currentTotal());
  });

  // show initial installment table
  updateInstallmentTable(currentTotal());
});
