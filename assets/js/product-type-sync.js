jQuery(document).ready(function ($) {

    // Έλεγχος αν έχουμε διαθέσιμα δεδομένα από το wp_localize_script
	if (typeof bp_product_type_sync_data !== 'undefined' && bp_product_type_sync_data.from_post) {
		// Κάνουμε AJAX call προς το backend για να πάρουμε τον τύπο του πρωτότυπου προϊόντος (π.χ. simple, variable)
        $.post(bp_product_type_sync_data.ajax_url, {
            action: 'bp_get_product_type',
            from_post: bp_product_type_sync_data.from_post
        }, function (response) {
			// Αν η απάντηση είναι επιτυχής και ο τύπος είναι "variable"
            if (response.success && response.data.type === 'variable') {

                // Εντοπίζουμε το select πεδίο τύπου προϊόντος
                let $typeSelect = $('select#product-type');

                if ($typeSelect.length > 0) {
					// Ορίζουμε τον τύπο προϊόντος σε "variable" και πυροδοτούμε το change event
                    $typeSelect.val('variable').trigger('change');
                }
            } else {
				// Αν δεν είναι variable, εμφανίζουμε debug log με τον τύπο που βρέθηκε
                console.log("ℹ Product type:", response.data.type);
            }
        });
    }
});