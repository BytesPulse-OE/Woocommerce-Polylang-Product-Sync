<?php
if (!defined('ABSPATH')) exit;

// Προσθέτει admin μενού
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

// Επιστρέφει μήνυμα επιτυχίας στο admin panel μετά τον συγχρονισμό
add_action('admin_notices', function () {
    if (isset($_GET['bp_sync']) && $_GET['bp_sync'] === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Ο συγχρονισμός όρων ολοκληρώθηκε με επιτυχία.</p></div>';
    }
});

// Περιεχόμενο admin σελίδας
function bp_wc_polylang_sync_settings_page() {
    ?>
    <div class="wrap">
        <h1>BytesPulse WC & Polylang Sync Settings</h1>

        <p>Αυτό το εργαλείο βοηθά στον συγχρονισμό όλων των όρων χαρακτηριστικών (attributes) του WooCommerce με το Polylang.</p>

        <h2>Πώς λειτουργεί</h2>
        <ul>
            <li>Οι μεταφράσεις των όρων (terms) γίνονται μέσα από τη διαχείριση όρων, πατώντας το κουμπί <strong>+</strong> του Polylang.</li>
            <li>Οι μεταφράσεις των ιδιοτήτων (attributes) γίνονται μέσα από το <strong>Strings Translation</strong> του Polylang.</li>
            <li>Το παρακάτω κουμπί εκτελεί συγχρονισμό των όρων με την κύρια γλώσσα.</li>
        </ul>

        <h2>Εκτέλεση Συγχρονισμού</h2>
        <a href="<?php echo admin_url('edit.php?post_type=product&action=bp_sync_terms'); ?>" class="button button-primary">
            Εκτέλεση Συγχρονισμού Όρων
        </a>
    </div>
    <?php
}

// Εκτελείται όταν καλείται το ?action=bp_sync_terms
add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('manage_options')) return;

    if (!isset($_GET['action']) || $_GET['action'] !== 'bp_sync_terms') return;

    if (!function_exists('wc_get_attribute_taxonomies') || !function_exists('pll_set_term_language')) {
        wp_die('Απαιτείται WooCommerce και Polylang για να εκτελεστεί ο συγχρονισμός.');
    }

    $attributes = wc_get_attribute_taxonomies();
    $default_lang = pll_default_language();

    if ($attributes) {
        foreach ($attributes as $attr) {
            $taxonomy = 'pa_' . $attr->attribute_name;
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

            foreach ($terms as $term) {
                $current_lang = pll_get_term_language($term->term_id);
                if (!$current_lang) {
                    pll_set_term_language($term->term_id, $default_lang);
                }
            }
        }
    }

    // Ανακατεύθυνση πίσω με success notice
    wp_redirect(admin_url('admin.php?page=bp-wc-polylang-sync&bp_sync=success'));
    exit;
});
