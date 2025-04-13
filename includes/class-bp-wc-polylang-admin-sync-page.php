<?php
if (!defined('ABSPATH')) exit;

// Δημιουργία admin menu για WC + Polylang Sync
add_action('admin_menu', function () {
    add_menu_page(
        'WC & Polylang Sync',
        'BytesPulse WC Sync',
        'manage_options',
        'bp-wc-polylang-sync',
        'bp_wc_polylang_sync_settings_page',
        'dashicons-translation',
        56
    );
});

// Περιεχόμενο σελίδας
function bp_wc_polylang_sync_settings_page() {
    ?>
    <div class="wrap">
        <h1>BytesPulse WC & Polylang Sync Settings</h1>
        <p>Αυτό το εργαλείο βοηθά στον συγχρονισμό όλων των όρων χαρακτηριστικών (attributes) του WooCommerce με το Polylang.</p>
        
        <h2>Πώς λειτουργεί</h2>
        <ul>
            <li>Οι μεταφράσεις των όρων (terms) γίνονται μέσα από τη διαχείριση όρων, πατώντας το κουμπί <strong>+</strong> που προσθέτει ο Polylang.</li>
            <li>Οι μεταφράσεις των ιδιοτήτων (attributes) γίνονται μέσα από το <strong>Strings Translation</strong> του Polylang.</li>
            <li>Το παρακάτω κουμπί εκτελεί αυτόματα τη λειτουργία συγχρονισμού, που προηγουμένως γινόταν χειροκίνητα με URL παράμετρο: <code>?action=bp_sync_terms</code></li>
        </ul>

        <h2>Συγχρονισμός Όρων με Polylang</h2>
        <p>Αν υπάρχουν όροι χαρακτηριστικών χωρίς καταχωρημένη γλώσσα στο Polylang, αυτό θα τους δηλώσει αυτόματα στη βασική γλώσσα.</p>

        <button id="bp-sync-terms-button" class="button button-primary">Εκτέλεση Συγχρονισμού Όρων</button>
        <div id="bp-sync-terms-result" style="margin-top: 20px;"></div>
    </div>

    <script>
    document.getElementById('bp-sync-terms-button').addEventListener('click', function () {
        const resultBox = document.getElementById('bp-sync-terms-result');
        resultBox.innerHTML = 'Εκτελείται...';

        fetch(ajaxurl + '?action=bp_sync_terms_ajax', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            resultBox.innerHTML = data.message;
        })
        .catch(() => {
            resultBox.innerHTML = 'Παρουσιάστηκε σφάλμα κατά την εκτέλεση.';
        });
    });
    </script>
    <?php
}

// AJAX handler για τον συγχρονισμό
add_action('wp_ajax_bp_sync_terms_ajax', 'bp_sync_existing_terms_with_polylang_ajax');

function bp_sync_existing_terms_with_polylang_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Δεν έχετε δικαιώματα.']);
    }

    if (!function_exists('wc_get_attribute_taxonomies') || !function_exists('pll_set_term_language')) {
        wp_send_json_error(['message' => 'Απαιτείται WooCommerce και Polylang.']);
    }

    $attributes = wc_get_attribute_taxonomies();
    if (!$attributes) {
        wp_send_json_error(['message' => 'Δεν βρέθηκαν attributes.']);
    }

    $default_lang = pll_default_language();
    $synced = [];

    foreach ($attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        foreach ($terms as $term) {
            $current_lang = pll_get_term_language($term->term_id);
            if (!$current_lang) {
                pll_set_term_language($term->term_id, $default_lang);
                $synced[] = $term->name . ' (' . $taxonomy . ')';
            }
        }
    }

    if (!empty($synced)) {
        ob_start();
        echo '<strong>Συγχρονίστηκαν οι παρακάτω όροι:</strong><ul>';
        foreach ($synced as $s) {
            echo '<li>' . esc_html($s) . '</li>';
        }
        echo '</ul>';
        $html = ob_get_clean();
        wp_send_json_success(['message' => $html]);
    } else {
        wp_send_json_success(['message' => 'Όλοι οι όροι είναι ήδη συγχρονισμένοι.']);
    }
}
