// Özel debounce fonksiyonu
function debounce(func, wait) {
  let timeout;
  return function(...args) {
    const context = this;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), wait);
  };
}

jQuery(document).ready(function($) {
  const contentDiv = $('#paytrInstallmentTableContent');
  const quantityInput = $('input.qty');
  const variationsForm = $('.variations_form');
  let currentPrice = parseFloat(paytr_object.unit_price); // Fiyatı saklamak için değişken

  // Taksit tablosunu güncelleme fonksiyonu
  function fncPaytrInstallmentTable(price) {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = `https://www.paytr.com/odeme/taksit-tablosu/v2?token=${paytr_object.paytr_token}&merchant_id=${paytr_object.paytr_merchant_id}&amount=${price}&taksit=${paytr_object.paytr_max_installment}&tumu=${paytr_object.paytr_extra_installment}`;
    
    contentDiv.find('#paytr_taksit_tablosu').empty().append(script);
  }

  // Miktar değişiminde sadece fiyatı güncelle (tabloyu değil)
  quantityInput.on('input', debounce(function() {
    const quantity = parseInt($(this).val()) || 1;
    currentPrice = (parseFloat(paytr_object.unit_price) * quantity).toFixed(2);
  }, 300));

  // Varyasyon değişiminde sadece fiyatı güncelle (tabloyu değil)
  variationsForm.on('found_variation', function(event, variation) {
    currentPrice = variation.display_price.toFixed(2);
  });

  // BUTON TIKLANDIĞINDA tabloyu güncelle
  $('#updateInstallmentsButton').on('click', function(e) {
    e.preventDefault(); // Form submit'i engellemek için
    fncPaytrInstallmentTable(currentPrice);
  });

  // Sayfa yüklendiğinde başlangıç tablosunu göster
  const initialPrice = (parseFloat(paytr_object.unit_price) * (parseInt(quantityInput.val()) || 1).toFixed(2));
  fncPaytrInstallmentTable(initialPrice);
});