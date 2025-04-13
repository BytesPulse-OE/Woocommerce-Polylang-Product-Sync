# BytesPulse WC & Polylang Product Sync Integration Plugin

## 🇬🇷 Περιγραφή (Ελληνικά)

Το plugin αυτό συνδέει το WooCommerce με το Polylang (δωρεάν έκδοση), επιτρέποντας τον πλήρη συγχρονισμό μεταξύ μεταφρασμένων προϊόντων.

### Βασικά Χαρακτηριστικά

- Συγχρονισμός τιμών, αποθεμάτων, διαστάσεων και βάρους μεταξύ γλωσσικών εκδόσεων
- Κλωνοποίηση παραλλαγών προϊόντων (variations) από το πρωτότυπο προϊόν στη μετάφραση
- Υποστήριξη μεταβλητών προϊόντων με αντιστοίχιση παραλλαγών μέσω custom meta
- Συγχρονισμός gallery εικόνων σε δύο κατευθύνσεις
- Αυτόματος εντοπισμός και αντιγραφή τύπου προϊόντος (simple, variable, κλπ.)
- Εμφάνιση του αρχικού SKU και στις μεταφράσεις
- Admin panel με οδηγίες και κουμπί συγχρονισμού όρων χαρακτηριστικών

### Εγκατάσταση

1. Ανέβασε το plugin στον φάκελο `wp-content/plugins/`
2. Ενεργοποίησέ το από το WordPress admin
3. Ρύθμισε τις μεταφράσεις όρων (terms) μέσω Polylang (+ κουμπί)
4. Ρύθμισε τις μεταφράσεις ιδιοτήτων (attributes) μέσω Polylang Strings

### Requirements

- WordPress >= 5.8
- WooCommerce >= 6.x
- Polylang (δωρεάν έκδοση)
- PHP >= 7.4

---

## ❓ Συχνές Ερωτήσεις (FAQ)

### Πρέπει να έχω την επί πληρωμή έκδοση του Polylang;
Όχι. Το plugin είναι σχεδιασμένο να λειτουργεί με τη δωρεάν έκδοση του Polylang.

### Πώς μεταφράζω τις παραλλαγές ενός μεταβλητού προϊόντος;
Αρχικά δημιουργείς τη μετάφραση του προϊόντος. Έπειτα, το plugin θα σε βοηθήσει να κλωνοποιήσεις τις παραλλαγές και να αντιστοιχίσει αυτόματα τα χαρακτηριστικά.

### Πώς μεταφράζω τις ιδιότητες (attributes) του WooCommerce;
Οι μεταφράσεις των ιδιοτήτων (όχι των όρων) γίνονται από το Polylang Strings.

### Πρέπει να κάνω χειροκίνητο συγχρονισμό κάθε φορά;
Όχι. Το plugin συγχρονίζει αυτόματα κατά την αποθήκευση προϊόντος, αλλά παρέχει και κουμπί χειροκίνητου συγχρονισμού για όρους χαρακτηριστικών.

### Γιατί το SKU αλλάζει σε -en ή -fr;
Αυτό συμβαίνει ώστε κάθε μετάφραση να έχει μοναδικό SKU. Στο frontend όμως εμφανίζεται το αρχικό SKU.

---

## 🇬🇧 Description (English)

This plugin bridges WooCommerce with Polylang (free version), enabling full synchronization between translated products.

### Key Features

- Sync prices, stock, dimensions, and weight between product translations
- Clone product variations from original to translated products
- Supports variable products with mapping of variations via custom meta
- Bidirectional gallery image synchronization
- Automatic product type detection and sync (simple, variable, etc.)
- Display original SKU across translations on the frontend
- Admin panel with instructions and "Sync Terms" button

### Installation

1. Upload plugin to `wp-content/plugins/` directory
2. Activate via WordPress admin panel
3. Translate terms via Polylang term UI (+ button)
4. Translate attributes via Polylang Strings panel

### Requirements

- WordPress >= 5.8
- WooCommerce >= 6.x
- Polylang (free)
- PHP >= 7.4

---

## ❓ Frequently Asked Questions (FAQ)

### Do I need the paid version of Polylang?
No. This plugin works with the free version of Polylang.

### How do I translate variable product variations?
First create the translation of the product. The plugin will then help you clone the variations and auto-map the attributes.

### How do I translate WooCommerce attributes?
Attribute labels are translated via Polylang Strings. Attribute terms are translated via the + button in the terms list.

### Do I need to manually sync every time?
No. The plugin auto-syncs on save, but also offers a manual sync button for attribute terms in the admin panel.

### Why is the SKU showing with -en or -fr?
Each translation gets a unique SKU (e.g. original-en) for WooCommerce compatibility. However, on the frontend, the original SKU is shown instead.

---
// TODO:
1. Sync variations from translated product back to prototype
2. Empty the DOM before saving if variation has not image.
