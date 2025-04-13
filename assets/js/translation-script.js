jQuery(document).ready(function($) {
	// Αν έχει δοθεί το "from_post" από wp_localize_script (δηλ. είμαστε σε μεταφρασμένο προϊόν)
    if (pll_translation_data.from_post) {
		// AJAX αίτημα προς το backend για να πάρουμε βασικά μεταδεδομένα από το πρωτότυπο προϊόν
        $.post(pll_translation_data.ajax_url, {
            action: 'get_product_translation_data',
            original_id: pll_translation_data.from_post
        }, function(response) {
            if (response.success) {
				// Για κάθε μεταδεδομένο που επιστρέφει ο server, το εισάγουμε στο αντίστοιχο input field
                $.each(response.data, function(key, value) {
                    $('input[name="' + key + '"]').val(value);
                });

                // Αν το πρωτότυπο προϊόν είχε ενεργή διαχείριση αποθέματος
                if (response.data['_manage_stock'] === 'yes') {
					// Ενεργοποιούμε και τη διαχείριση αποθέματος στη μετάφραση
                    $('#_manage_stock').prop('checked', true).trigger('change');
                }

                // Αυτόματη τροποποίηση του SKU στη μετάφραση (π.χ. B001 → B001-en)
                var sku = $('input[name="_sku"]').val();
                if (sku && pll_translation_data.current_lang !== pll_translation_data.default_lang) {
                    var newSku = sku + '-' + pll_translation_data.current_lang;
                    $('input[name="_sku"]').val(newSku);
                }
            }
        });
    }
});