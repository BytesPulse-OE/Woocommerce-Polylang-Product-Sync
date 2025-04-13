jQuery(document).ready(function($) {
	// Αν έχει οριστεί το "from_post" από το wp_localize_script → είμαστε σε σελίδα μεταφρασμένου προϊόντος
    if (pll_gallery_sync_data.from_post) {
		// Κάνουμε AJAX αίτημα για να πάρουμε τις εικόνες γκαλερί του πρωτότυπου προϊόντος
        $.post(pll_gallery_sync_data.ajax_url, {
            action: 'get_product_gallery',
            original_id: pll_gallery_sync_data.from_post
        }, function(response) {
            if (response.success) {
				// Βρίσκουμε τον wrapper που περιέχει τις εικόνες του προϊόντος και τον αδειάζουμε
                let galleryWrapper = $('#product_images_container .product_images');
                galleryWrapper.empty();
               
				// Για κάθε εικόνα που λάβαμε, δημιουργούμε νέο <li> και το προσθέτουμε στο DOM
				$.each(response.data, function(index, image) {
                    galleryWrapper.append('<li class="image" data-attachment_id="' + image.id + '"><img src="' + image.url + '" /><a href="#" class="delete">x</a></li>');
                });

                console.log("Gallery loaded:", response.data); // DEBUG
            }
        });
    }

    // Συγχρονισμός εικόνων κατά την αλλαγή στο gallery - Όταν διαγράψουμε εικόνα από τη gallery → ενημερώνουμε το gallery sync
    $('#product_images_container').on('click', '.delete', function(e) {
        e.preventDefault();
        let imageId = $(this).closest('.image').data('attachment_id');
        $(this).closest('.image').remove();
        syncGallery();
    });

    // Όταν προστεθεί νέα εικόνα στο DOM (όχι refresh), κάνουμε συγχρονισμό της gallery
    $('#product_images_container').on('DOMNodeInserted', '.image', function() {
        console.log("New image detected:", $(this).data('attachment_id')); // DEBUG
        syncGallery();
    });

    function syncGallery() {
        let imageIds = [];
		// Παίρνουμε τα IDs από όλες τις εικόνες που υπάρχουν αυτή τη στιγμή
        $('#product_images_container .product_images .image').each(function() {
            imageIds.push($(this).data('attachment_id'));
        });

        console.log("Syncing gallery:", imageIds); // DEBUG

        // Στέλνουμε τα νέα image IDs μέσω AJAX για να αποθηκευτούν στο μεταφρασμένο προϊόν
		$.post(pll_gallery_sync_data.ajax_url, {
            action: 'sync_product_gallery',
            product_id: pll_gallery_sync_data.current_post,
            images: imageIds
        }, function(response) {
            console.log("Sync response:", response); // DEBUG
        });
    }
});