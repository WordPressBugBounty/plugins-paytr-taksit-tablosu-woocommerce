// Debounce
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    const context = this;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), wait);
  };
}

jQuery(document).ready(function ($) {
  const contentDiv = $('#paytrInstallmentTableContent');
  const quantityInput = $('input.qty');
  const variationsForm = $('.variations_form');
  const updateButton = $('#updateInstallmentsButton');

  // Price To Number
  function sanitizePrice(price) {
    return parseFloat(price.toString().replace(/[^\d.-]/g, '')) || 0;
  }

  // Pricete format
  function formatPrice(price) {
    return sanitizePrice(price).toFixed(2);
  }

  // PayTR installment table update function
  function updateInstallmentTable(price) {
    const formattedPrice = formatPrice(price);

    console.log('Tabloda kullanılacak fiyat:', formattedPrice);

    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = `https://www.paytr.com/odeme/taksit-tablosu/v2?token=${paytr_object.paytr_token}&merchant_id=${paytr_object.paytr_merchant_id}&amount=${formattedPrice}&taksit=${paytr_object.paytr_max_installment}&tumu=${paytr_object.paytr_extra_installment}`;

    contentDiv.find('#paytr_taksit_tablosu').empty().append(script);
  }

  // sanitize and format the base unit price
  const baseUnitPrice = sanitizePrice(paytr_object.unit_price);
  let currentPrice = baseUnitPrice;

  // new price on quantity change
  quantityInput.on('input', debounce(function () {
    const quantity = parseInt($(this).val()) || 1;
    currentPrice = (baseUnitPrice * quantity).toFixed(2);
    console.log(' Yeni fiyat (miktar değişti):', currentPrice);
  }, 300));

  // basic variation change handling
  variationsForm.on('found_variation', function (event, variation) {
    currentPrice = formatPrice(variation.display_price);
    console.log(' Yeni fiyat (varyasyon değişti):', currentPrice);
  });

  // update button click handling
  updateButton.on('click', function (e) {
    e.preventDefault();
    updateInstallmentTable(currentPrice);
  });

  // show initial installment table
  const initialQuantity = parseInt(quantityInput.val()) || 1;
  const initialPrice = (baseUnitPrice * initialQuantity).toFixed(2);
  console.log(' Sayfa açılış fiyatı:', initialPrice);
  updateInstallmentTable(initialPrice);
});
