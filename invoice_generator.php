<?php
include 'config/db.php';

function generateInvoiceNumber($conn) {
    $current_year = date('Y');
    
    // Get or create sequence for current year
    $sql = "SELECT current_number FROM invoice_sequence WHERE current_year = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $current_year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        // Create new sequence for this year
        $sql = "INSERT INTO invoice_sequence (current_year, current_number) VALUES (?, 0)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $current_year);
        mysqli_stmt_execute($stmt);
        $current_number = 0;
    } else {
        $row = mysqli_fetch_assoc($result);
        $current_number = $row['current_number'];
    }
    
    // Increment the number
    $current_number++;
    
    // Update the sequence
    $sql = "UPDATE invoice_sequence SET current_number = ? WHERE current_year = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $current_number, $current_year);
    mysqli_stmt_execute($stmt);
    
    // Generate invoice number format: INV-YYYY-XXXXX (e.g., INV-2024-00001)
    $invoice_number = sprintf("INV-%s-%05d", $current_year, $current_number);
    
    return $invoice_number;
}

function validateInvoiceNumber($conn, $invoice_no) {
    // Check if invoice number already exists
    $sql = "SELECT COUNT(*) as count FROM purchase_invoices WHERE invoice_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $invoice_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'] == 0;
}
?> 