<?php
session_start();
include 'config/db.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header("Location: index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $party_name = mysqli_real_escape_string($conn, $_POST['party_name']);
    $invoice_no = mysqli_real_escape_string($conn, $_POST['invoice_no']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $created_by = $_SESSION['user']['user_id'];
    
    // Get product arrays
    $product_names = $_POST['product_name'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];
    
    // Validate required fields
    if (empty($party_name) || empty($invoice_no) || empty($delivery_date)) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: add_purchase.php");
        exit;
    }
    
    // Validate product data
    $valid_products = true;
    for ($i = 0; $i < count($product_names); $i++) {
        if (empty($product_names[$i]) || empty($quantities[$i]) || empty($prices[$i])) {
            $valid_products = false;
            break;
        }
    }
    
    if (!$valid_products) {
        $_SESSION['error'] = "Please fill all product details.";
        header("Location: add_purchase.php");
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert main purchase record
        $sql = "INSERT INTO purchase_invoices (party_name, invoice_no, delivery_date, notes, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $party_name, $invoice_no, $delivery_date, $notes, $created_by);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error inserting purchase invoice: " . mysqli_error($conn));
        }
        
        $purchase_id = mysqli_insert_id($conn);
        
        // Insert product items
        for ($i = 0; $i < count($product_names); $i++) {
            $product_name = mysqli_real_escape_string($conn, $product_names[$i]);
            $quantity = (int)$quantities[$i];
            $price = (float)$prices[$i];
            $total_price = $quantity * $price;
            
            $sql = "INSERT INTO purchase_items (purchase_id, product_name, quantity, price, total_price) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isidd", $purchase_id, $product_name, $quantity, $price, $total_price);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting purchase item: " . mysqli_error($conn));
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Purchase saved successfully!";
        header("Location: purchase_list.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error saving purchase: " . $e->getMessage();
        header("Location: add_purchase.php");
        exit;
    }
    
} else {
    // If not POST request, redirect to form
    header("Location: add_purchase.php");
    exit;
}
?> 