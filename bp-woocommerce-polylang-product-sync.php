<?php
 /**
 * BP Woocommerce & Polylang Pro Alternative
 * 
 * @package           BP Woocommerce & Polylang Product Sync
 * @author            BytesPulse OE
 * @license           GPL-3.0-or-later
 * 
 * @wordpress-plugin
 * Plugin Name:       BP Woocommerce & Polylang Product Sync
 * Plugin URI:        https://bytespulse.com
 * Description:       Sync product data like prices, gallery, sku, variants, terms through product translations. Polylang Pro Alternative.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.0
 * Author:            BytesPulse OE
 * Author URI:        https://bytespulse.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2025 BytesPulse OE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Αποτρέπουμε άμεση πρόσβαση
if (!defined('ABSPATH')) {
    exit;
}

// Define Plugin Path
define('BP_WOOCOMMERCE_POLYLANG_SYNC_PATH', plugin_dir_path(__FILE__));
define('BP_WOOCOMMERCE_POLYLANG_SYNC_URL', plugin_dir_url(__FILE__));

// Φορτώνουμε τις απαραίτητες κλάσεις και αρχεία
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-sync.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-gallery-sync.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-terms-sync.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-terms-translate.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-variable-sync.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-preload-attributes.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-variation-clone.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-attribute-labels.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-variable-bidirectional-sync-meta.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-sku-override.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-admin-sync-page.php';
require_once BP_WOOCOMMERCE_POLYLANG_SYNC_PATH . 'includes/class-bp-wc-polylang-debug-helper.php';

// Ενεργοποίηση του plugin
function bp_woocommerce_polylang_sync_activate() {
	// Κώδικας για την ενεργοποίηση του plugin
}
register_activation_hook(__FILE__, 'bp_woocommerce_polylang_sync_activate');

// Απενεργοποίηση του plugin
function bp_woocommerce_polylang_sync_deactivate() {
    // Κώδικας για την απενεργοποίηση του plugin (π.χ. καθαρισμός δεδομένων ή ρυθμίσεων)
}
register_deactivation_hook(__FILE__, 'bp_woocommerce_polylang_sync_deactivate');