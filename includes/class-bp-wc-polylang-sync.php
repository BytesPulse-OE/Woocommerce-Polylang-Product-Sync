<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Συγχρονισμός βασικών πεδίων WooCommerce προϊόντων μεταξύ Polylang μεταφράσεων.
 * 
 * Συγκεκριμένα:
 * - Συγχρονίζει τιμές, stock, διαστάσεις και βάρος μεταξύ μεταφρασμένων προϊόντων
 * - Συγχρονίζει το SKU με βάση τη γλώσσα
 * - Φορτώνει το backend script για αυτόματη συμπλήρωση πεδίων στη μετάφραση
 * - Επιτρέπει εμφάνιση του πρωτότυπου SKU και στο frontend με φίλτρο
 */

class BP_WooCommerce_Polylang_Sync {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_translation_script'));
        add_action('wp_ajax_get_product_translation_data', array($this, 'get_product_translation_data'));
        add_action('pll_save_post', array($this, 'sync_sku_with_original_product_pll'), 10, 2);
        add_action('save_post', array($this, 'sync_product_changes_between_languages'), 20, 3);
        add_filter('woocommerce_product_get_sku', array($this, 'filter_sku'), 10, 2);
    }

    // Επισυνάπτει JS που συμπληρώνει τιμές αυτόματα κατά τη δημιουργία μετάφρασης προϊόντος
    public function enqueue_translation_script($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

        wp_enqueue_script(
            'polylang-translate-script',
            BP_WOOCOMMERCE_POLYLANG_SYNC_URL . 'assets/js/translation-script.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('polylang-translate-script', 'pll_translation_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'from_post' => isset($_GET['from_post']) ? intval($_GET['from_post']) : 0,
            'current_lang' => pll_current_language(),
            'default_lang' => pll_default_language()
        ]);
    }

    // Επιστρέφει βασικά μεταδεδομένα του πρωτότυπου προϊόντος μέσω AJAX
    public function get_product_translation_data() {
        if (!isset($_POST['original_id'])) {
            wp_send_json_error('Missing original ID');
        }

        $original_id = intval($_POST['original_id']);
        $original_product = wc_get_product($original_id);
        if (!$original_product) {
            wp_send_json_error('Invalid product');
        }

        $meta_keys = ['_sku', '_regular_price', '_sale_price', '_weight', '_length', '_width', '_height', '_stock', '_stock_status', '_manage_stock'];
        $product_data = [];

        foreach ($meta_keys as $key) {
            $product_data[$key] = get_post_meta($original_id, $key, true);
        }

        wp_send_json_success($product_data);
    }

    // Συγχρονίζει το SKU του μεταφρασμένου προϊόντος με το πρωτότυπο όταν αποθηκεύεται
    public function sync_sku_with_original_product_pll($post_id, $post) {
        if (defined('POLYLANG_VERSION') && function_exists('pll_get_post')) {
            $current_lang = pll_get_post_language($post_id);
            $default_lang = pll_default_language();

            if ($current_lang === $default_lang) {
                return;
            }

            $original_id = pll_get_post($post_id, $default_lang);
            if (!$original_id) {
                return;
            }

            $original_sku = get_post_meta($original_id, '_sku', true);

            if (empty(get_post_meta($post_id, '_sku', true))) {
                update_post_meta($post_id, '_sku', $original_sku);
            }

            $translated_product = wc_get_product($post_id);
            if ($translated_product && !$translated_product->get_sku()) {
                $sku_with_language = $original_sku . '-' . $current_lang;
                $translated_product->set_sku($sku_with_language);
                $translated_product->save();
            }
        }
    }

    // Κατά την αποθήκευση προϊόντος, συγχρονίζει δεδομένα από/προς το πρωτότυπο ή μετάφραση
    public function sync_product_changes_between_languages($post_id, $post, $update) {
        if (wp_is_post_autosave($post_id) || 'product' !== get_post_type($post_id)) {
            return;
        }

        $current_lang = pll_get_post_language($post_id);
        $default_lang = pll_default_language();

        if ($current_lang === $default_lang) {
            $translated_id = pll_get_post($post_id, 'en'); // Εδώ μπορεί να γίνει δυναμικό αν χρειαστεί
            if ($translated_id) {
                $this->sync_product_data($post_id, $translated_id);
            }
        } else {
            $original_id = pll_get_post($post_id, $default_lang);
            if ($original_id) {
                $this->sync_product_data($post_id, $original_id);
            }
        }
    }

    // Εκτελεί τον πραγματικό συγχρονισμό μεταδεδομένων μεταξύ δύο προϊόντων
    public function sync_product_data($from_post_id, $to_post_id) {
        global $wpdb;

        $from_product = wc_get_product($from_post_id);
        $to_product = wc_get_product($to_post_id);

        if (!$from_product || !$to_product) {
            return;
        }

        $regular_price = $from_product->get_regular_price();
        $sale_price = $from_product->get_sale_price();
        $price = !empty($sale_price) ? $sale_price : $regular_price;

        // Καθαρίζουμε πρώτα παλιές τιμές
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key IN ('_regular_price', '_sale_price', '_price')", $to_post_id));

        // Εισάγουμε τις νέες
        $wpdb->insert($wpdb->postmeta, ['post_id' => $to_post_id, 'meta_key' => '_regular_price', 'meta_value' => $regular_price]);

        if (!empty($sale_price)) {
            $wpdb->insert($wpdb->postmeta, ['post_id' => $to_post_id, 'meta_key' => '_sale_price', 'meta_value' => $sale_price]);
            $wpdb->insert($wpdb->postmeta, ['post_id' => $to_post_id, 'meta_key' => '_price', 'meta_value' => $sale_price]);
        } else {
            $wpdb->insert($wpdb->postmeta, ['post_id' => $to_post_id, 'meta_key' => '_price', 'meta_value' => $regular_price]);
        }

        // Stock & διαστάσεις
        $stock = $from_product->get_stock_quantity();
        $manage_stock = $from_product->get_manage_stock() ? 'yes' : 'no';
        $stock_status = $from_product->get_stock_status();

        update_post_meta($to_post_id, '_stock', $stock);
        update_post_meta($to_post_id, '_manage_stock', $manage_stock);
        update_post_meta($to_post_id, '_stock_status', $stock_status);

        update_post_meta($to_post_id, '_weight', $from_product->get_weight());
        update_post_meta($to_post_id, '_length', $from_product->get_length());
        update_post_meta($to_post_id, '_width', $from_product->get_width());
        update_post_meta($to_post_id, '_height', $from_product->get_height());

        // Αποθήκευση προϊόντος και trigger για άλλες επεκτάσεις
        $to_product->save();
        do_action('woocommerce_update_product', $to_post_id);
    }

    // Φίλτρο που επιστρέφει το SKU του πρωτότυπου προϊόντος σε μεταφρασμένες σελίδες
    public function filter_sku($sku, $product) {
        if (!function_exists('pll_get_post') || !function_exists('pll_get_post_language')) {
            return $sku;
        }

        $product_id = $product->get_id();
        $current_lang = pll_get_post_language($product_id);
        $default_lang = pll_default_language();

        // Αν δεν είμαστε στην κύρια γλώσσα, επιστρέφουμε το SKU του original
        if ($current_lang !== $default_lang) {
            $original_id = pll_get_post($product_id, $default_lang);
            if ($original_id) {
                $original_sku = get_post_meta($original_id, '_sku', true);
                return $original_sku;
            }
        }

        return $sku;
    }
}

new BP_WooCommerce_Polylang_Sync();
