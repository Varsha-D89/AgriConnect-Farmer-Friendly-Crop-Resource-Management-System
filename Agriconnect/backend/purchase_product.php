<?php
/**
 * AgriConnect – Purchase Product Backend
 * =========================================
 * Handles customer product orders.
 * Saves order details to MySQL 'orders' table.
 * Sends a JSON response back to JavaScript.
 *
 * POST Parameters:
 *   customer_name  : string
 *   mobile         : string (10-digit phone number)
 *   product_name   : string
 *   quantity       : integer
 *   price          : float (unit price in INR)
 *   address        : string
 *   payment_method : cod | upi | bank | card
 */

require_once 'db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(false, 'Invalid request method. Use POST.');
}

// === Collect & Sanitize Input ===
$customer_name  = sanitize($conn, $_POST['customer_name']  ?? '');
$mobile         = sanitize($conn, $_POST['mobile']         ?? '');
$product_name   = sanitize($conn, $_POST['product_name']   ?? '');
$quantity       = intval($_POST['quantity']                ?? 1);
$unit_price     = floatval($_POST['price']                 ?? 0);
$address        = sanitize($conn, $_POST['address']        ?? '');
$payment_method = sanitize($conn, $_POST['payment_method'] ?? 'cod');

// === Validation ===
$errors = [];

if (empty($customer_name)) $errors[] = 'Customer name is required.';
if (empty($mobile))        $errors[] = 'Mobile number is required.';
if (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\s+/', '', $mobile))) {
    $errors[] = 'Please enter a valid 10-digit Indian mobile number.';
}
if (empty($product_name))  $errors[] = 'Product name is required.';
if ($quantity < 1)         $errors[] = 'Quantity must be at least 1.';
if (empty($address))       $errors[] = 'Delivery address is required.';

if (!empty($errors)) {
    sendJSON(false, implode(' | ', $errors));
}

// === Calculate Total Price ===
$total_price = $unit_price * $quantity;

// === Generate Order ID ===
// Format: AGR-YYYYMMDD-XXXX (e.g., AGR-20240615-4829)
$order_id = 'AGR-' . date('Ymd') . '-' . rand(1000, 9999);

// === Save Order to Database ===
$stmt = $conn->prepare(
    "INSERT INTO orders 
     (order_id, customer_name, mobile, product_name, quantity, unit_price, total_price, address, payment_method, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);

if (!$stmt) {
    sendJSON(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param(
    'ssssiidss',
    $order_id,
    $customer_name,
    $mobile,
    $product_name,
    $quantity,
    $unit_price,
    $total_price,
    $address,
    $payment_method
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    // === Payment Instructions ===
    $paymentMsg = [
        'cod'  => 'Pay cash when your order arrives.',
        'upi'  => 'UPI: agriconnect@upi — Send ₹' . number_format($total_price, 2) . ' and share screenshot.',
        'bank' => 'Bank: AgriConnect | A/C: 1234567890 | IFSC: AGRI0001 | Amount: ₹' . number_format($total_price, 2),
        'card' => 'Our team will call you to complete card payment securely.',
    ][$payment_method] ?? '';

    sendJSON(true,
        "✅ Order placed successfully! Order ID: $order_id | Total: ₹" . number_format($total_price, 2) . ". $paymentMsg",
        [
            'order_id'       => $order_id,
            'product'        => $product_name,
            'quantity'       => $quantity,
            'unit_price'     => $unit_price,
            'total_price'    => $total_price,
            'payment'        => $payment_method,
            'estimated_delivery' => '3-7 business days',
        ]
    );
} else {
    sendJSON(false, 'Failed to save order: ' . $stmt->error);
}
