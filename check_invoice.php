<?php
session_start();
include 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header('Content-Type: application/json');
    echo json_encode(['available' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_no = trim($_POST['invoice_no'] ?? '');
    
    if (empty($invoice_no)) {
        header('Content-Type: application/json');
        echo json_encode(['available' => false, 'message' => 'Invoice number is required']);
        exit;
    }
    
    // Check if invoice number already exists
    $sql = "SELECT COUNT(*) as count FROM purchase_invoices WHERE invoice_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $invoice_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $available = ($row['count'] == 0);
    
    header('Content-Type: application/json');
    echo json_encode([
        'available' => $available,
        'message' => $available ? 'Invoice number is available' : 'Invoice number already exists'
    ]);
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}
?> 