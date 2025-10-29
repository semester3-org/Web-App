<?php
/**
 * ============================================
 * MIDTRANS CONFIGURATION
 * File: backend/config/midtrans.php
 * ============================================
 */

// Midtrans Credentials (SANDBOX - untuk testing)
define('MIDTRANS_SERVER_KEY', 'Mid-server-nafyr2HAaRTyzWQxEHd1axBh');
define('MIDTRANS_CLIENT_KEY', 'Mid-client-4Bj5YqCnZN6PNOcr');
define('MIDTRANS_IS_PRODUCTION', false); // false = sandbox, true = production
define('MIDTRANS_IS_SANITIZED', true);
define('MIDTRANS_IS_3DS', true);

// Payment Configuration
define('TAX_PERCENTAGE', 10); // 10% tax
define('PAYMENT_EXPIRY_DURATION', 24); // 24 hours
define('PAYMENT_ENABLED_METHODS', ['credit_card', 'gopay', 'shopeepay', 'bank_transfer']);

// Midtrans API URLs
define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION ? 
    'https://app.midtrans.com/snap/snap.js' : 
    'https://app.sandbox.midtrans.com/snap/snap.js'
);

define('MIDTRANS_API_URL', MIDTRANS_IS_PRODUCTION ? 
    'https://api.midtrans.com/v2' : 
    'https://api.sandbox.midtrans.com/v2'
);

/**
 * Function to get Midtrans config as array
 */
function getMidtransConfig() {
    return [
        'server_key' => MIDTRANS_SERVER_KEY,
        'client_key' => MIDTRANS_CLIENT_KEY,
        'is_production' => MIDTRANS_IS_PRODUCTION,
        'is_sanitized' => MIDTRANS_IS_SANITIZED,
        'is_3ds' => MIDTRANS_IS_3DS
    ];
}

/**
 * Calculate tax amount (hanya pajak, bukan total)
 */
function calculateTax($price) {
    return (int) ($price * TAX_PERCENTAGE / 100);
}

/**
 * Calculate total payment (HANYA PAJAK)
 */
function calculateTotalPayment($price) {
    return calculateTax($price);
}

/**
 * Generate unique order ID
 */
function generateOrderId($prefix = 'KOS') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(uniqid());
}
?>