<?php
// Βοηθητικό αρχείο για ασφαλές και ελεγχόμενο logging σε περιβάλλον ανάπτυξης

if (!function_exists('bp_debug_log')) {
    /**
     * Καταγράφει μηνύματα στο error log, μόνο αν είναι ενεργό το WP_DEBUG.
     * Προαιρετικά, μπορεί να εξαναγκαστεί καταγραφή ακόμα και αν WP_DEBUG είναι off.
     *
     * @param string $message Το μήνυμα που θα καταγραφεί
     * @param bool $force Αν είναι true, παρακάμπτει τον έλεγχο WP_DEBUG
     */
    function bp_debug_log($message, $force = false) {
        $should_log = (defined('WP_DEBUG') && WP_DEBUG) || $force;

        if ($should_log) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[$timestamp] $message");
        }
    }
}
