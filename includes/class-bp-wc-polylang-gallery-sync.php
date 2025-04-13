<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Συγχρονισμός gallery εικόνων προϊόντων WooCommerce μεταξύ Polylang μεταφράσεων.
 * 
 * Αυτό το class:
 * - Εντοπίζει και φορτώνει τις εικόνες του πρωτότυπου προϊόντος μέσω AJAX
 * - Επιτρέπει επεξεργασία gallery στο μεταφρασμένο προϊόν
 * - Συγχρονίζει gallery από πρωτότυπο προς μεταφράσεις και αντίστροφα
 * - Λειτουργεί τόσο με JavaScript όσο και με save_post hook
 */

class BP_WooCommerce_Polylang_Gallery_Sync {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_gallery_script'));
        add_action('wp_ajax_get_product_gallery', array($this, 'get_product_gallery'));
        add_action('wp_ajax_sync_product_gallery', array($this, 'sync_product_gallery'));
        add_action('save_post', array($this, 'sync_gallery_between_languages'), 20, 3);
    }

    // Φορτώνει το JavaScript για τον συγχρονισμό gallery και περνά μεταβλητές στο frontend
    public function enqueue_gallery_script($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

        wp_enqueue_script(
            'polylang-gallery-sync',
            BP_WOOCOMMERCE_POLYLANG_SYNC_URL . 'assets/js/gallery-sync.js',
            ['jquery'],
            '1.0',
            true
        );

        $current_post = get_the_ID();
        $from_post = isset($_GET['from_post']) ? intval($_GET['from_post']) : 0;

        wp_localize_script('polylang-gallery-sync', 'pll_gallery_sync_data', [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'current_post' => $current_post ?: 0,
            'from_post'    => $from_post
        ]);
    }

    // Επιστρέφει τις gallery εικόνες του πρωτότυπου προϊόντος σε JSON μορφή
    public function get_product_gallery() {
        if (!isset($_POST['original_id'])) {
            wp_send_json_error('Missing original ID');
        }

        $original_id = intval($_POST['original_id']);
        if ($original_id === 0) {
            wp_send_json_error('Invalid post ID');
        }

        $gallery = get_post_meta($original_id, '_product_image_gallery', true);
        if (!$gallery) {
            wp_send_json_success([]);
        }

        $gallery_ids = explode(',', $gallery);
        $images = [];

        foreach ($gallery_ids as $id) {
            $image_url = wp_get_attachment_image_url($id, 'thumbnail');
            if ($image_url) {
                $images[] = ['id' => $id, 'url' => $image_url];
            }
        }

        wp_send_json_success($images);
    }

    // Αποθηκεύει τη νέα gallery εικόνων για το προϊόν και συγχρονίζει με τις μεταφράσεις
    public function sync_product_gallery() {
        if (!isset($_POST['product_id']) || !isset($_POST['images'])) {
            wp_send_json_error('Missing data');
        }

        $product_id = intval($_POST['product_id']);
        $image_ids = array_map('intval', $_POST['images']);

        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        update_post_meta($product_id, '_product_image_gallery', implode(',', $image_ids));

        if (!function_exists('pll_get_post_language') || !function_exists('pll_default_language') || !function_exists('pll_get_post_translations')) {
            return;
        }

        $default_lang = pll_default_language();
        $current_lang = pll_get_post_language($product_id);

        if ($current_lang === $default_lang) {
            $translations = pll_get_post_translations($product_id);
            foreach ($translations as $lang => $translated_id) {
                if ($translated_id !== $product_id) {
                    update_post_meta($translated_id, '_product_image_gallery', implode(',', $image_ids));
                }
            }
        } else {
            $original_id = pll_get_post($product_id, $default_lang);
            if ($original_id) {
                update_post_meta($original_id, '_product_image_gallery', implode(',', $image_ids));
            }
        }

        wp_send_json_success();
    }

    // Όταν αποθηκεύεται ένα προϊόν, συγχρονίζει το gallery με τις μεταφράσεις ή με το πρωτότυπο
    public function sync_gallery_between_languages($post_id, $post, $update) {
        if (wp_is_post_autosave($post_id) || 'product' !== get_post_type($post_id)) {
            return;
        }

        if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations') || !function_exists('pll_default_language')) {
            return;
        }

        $current_lang = pll_get_post_language($post_id);
        $default_lang = pll_default_language();
        $gallery = get_post_meta($post_id, '_product_image_gallery', true);

        if ($current_lang === $default_lang) {
            $translations = pll_get_post_translations($post_id);
            foreach ($translations as $lang => $translated_id) {
                if ($translated_id !== $post_id) {
                    update_post_meta($translated_id, '_product_image_gallery', $gallery);
                }
            }
        } else {
            $translations = pll_get_post_translations($post_id);
            $original_id = $translations[$default_lang] ?? null;
            if ($original_id && $original_id !== $post_id) {
                update_post_meta($original_id, '_product_image_gallery', $gallery);
            }
        }
    }
}

new BP_WooCommerce_Polylang_Gallery_Sync();