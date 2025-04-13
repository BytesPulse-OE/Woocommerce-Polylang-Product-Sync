<?php
if (!defined('ABSPATH')) exit;

// Προσθέτουμε custom JavaScript μόνο στη σελίδα προϊόντος (single product page)
add_action('wp_footer', function () {
    if (!is_product()) return;
    ?>
    <script>
	// Εκτελείται μόλις φορτωθεί ολόκληρη η σελίδα
    document.addEventListener("DOMContentLoaded", function () {
        const skuWrapper = document.querySelector(".product_meta .sku_wrapper");

        if (!skuWrapper) {
            console.warn("❌ Δεν βρέθηκε .sku_wrapper");
            return;
        }

        // Αποθηκεύουμε το αρχικό SKU ώστε να το κρατήσουμε σταθερό
		let initialSku = '';
        const originalEl = skuWrapper.querySelector(".sku");
        if (originalEl) {
            initialSku = originalEl.textContent.trim();
        }

        if (!initialSku) {
            console.warn("❌ Το αρχικό SKU δεν βρέθηκε ή είναι κενό");
            return;
        }

        console.log("✅ Καταγράφηκε αρχικό SKU:", initialSku);

        // Παρακολουθούμε για αλλαγές στο DOM που επηρεάζουν το SKU
		const observer = new MutationObserver(() => {
            const currentSkuEl = skuWrapper.querySelector(".sku");
            if (currentSkuEl && currentSkuEl.textContent.trim() !== initialSku) {
                console.log("🔁 Το SKU άλλαξε, το επαναφέρουμε σε:", initialSku);
                currentSkuEl.textContent = initialSku;
            }
        });

        // Ορίζουμε να παρακολουθεί αλλαγές σε στοιχεία, κείμενο κ.λπ.
		observer.observe(skuWrapper, {
            childList: true,
            subtree: true,
            characterData: true
        });
    });
    </script>
    <?php
});