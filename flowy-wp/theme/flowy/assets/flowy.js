/* Flowy: пересчёт цены по опциям, открытка, адрес только для курьера. */
(function ($) {
	"use strict";

	function rub(n) {
		return Math.round(n).toLocaleString("ru-RU").replace(/\s/g, " ") + " ₽";
	}

	var $opts = $(".fl-options");
	if ($opts.length) {
		var base = parseFloat($opts.data("base")) || 0;
		var card = parseFloat($opts.data("card")) || 0;
		var $price = $(".summary .price").first();
		var update = function () {
			var p = base;
			var $size = $opts.find('input[name="flowy_size"]:checked');
			if ($size.length) p = Math.round(base * parseFloat($size.data("mult")) / 100) * 100;
			$opts.find('input[data-add]:checked').each(function () { p += parseFloat($(this).data("add")) || 0; });
			if ($("#flowy_card_on").is(":checked")) p += card;
			$price.html('<span class="woocommerce-Price-amount amount">' + rub(p) + "</span>");
		};
		$opts.on("change", "input", update);
		$("#flowy_card_on").on("change", function () {
			$("#flowy_card").prop("hidden", !this.checked);
			if (this.checked) $("#flowy_card").trigger("focus");
		});
		update();
	}

	/* Оформление: поле адреса нужно только для курьера. */
	function toggleAddress() {
		var m = $('input[name^="shipping_method"]:checked').val() || $('input[name^="shipping_method"]').val() || "";
		$(".fl-address").toggle(m.indexOf("local_pickup") !== 0);
	}
	$(document.body).on("updated_checkout", toggleAddress);
	$(document).on("change", 'input[name^="shipping_method"]', toggleAddress);
	toggleAddress();
})(jQuery);
