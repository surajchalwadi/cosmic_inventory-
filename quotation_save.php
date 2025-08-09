<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Get form data
        $reference = mysqli_real_escape_string($conn, $_POST['reference']);
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $company = mysqli_real_escape_string($conn, $_POST['company']);
        $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $additional_info = mysqli_real_escape_string($conn, $_POST['additional_info']);
        $margin_percent = floatval($_POST['margin_percent']);
        $discount_percent = floatval($_POST['discount_percent']);
        $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $follow_up_method = mysqli_real_escape_string($conn, $_POST['follow_up_method']);
        $follow_up_notes = mysqli_real_escape_string($conn, $_POST['follow_up_notes']);
        $created_by = $_SESSION['user']['id'];
        
        // Determine status based on action type
        $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'send';
        $status = ($action_type === 'draft') ? 'Draft' : 'Sent';
        
        // Calculate grand total
        $grand_total = 0;
        if (isset($_POST['price']) && isset($_POST['quantity']) && is_array($_POST['price']) && is_array($_POST['quantity'])) {
            for ($i = 0; $i < count($_POST['price']); $i++) {
                if (isset($_POST['quantity'][$i]) && isset($_POST['price'][$i])) {
                    $item_total = floatval($_POST['quantity'][$i]) * floatval($_POST['price'][$i]);
                    $grand_total += $item_total;
                }
            }
        }
        
        // Apply margin and discount
        $grand_total = $grand_total * (1 + $margin_percent / 100);
        $grand_total = $grand_total * (1 - $discount_percent / 100);
        
        // Insert quotation
        $quotation_query = "INSERT INTO quotations (
            reference, customer_name, phone, company, contact_person, address, 
            additional_info, margin_percent, discount_percent, follow_up_date, 
            follow_up_method, follow_up_notes, grand_total, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $quotation_query);
        mysqli_stmt_bind_param($stmt, "sssssssddsssdsi", 
            $reference, $customer_name, $phone, $company, $contact_person, 
            $address, $additional_info, $margin_percent, $discount_percent, 
            $follow_up_date, $follow_up_method, $follow_up_notes, $grand_total, $status, $created_by
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error inserting quotation: " . mysqli_error($conn));
        }
        
        $quotation_id = mysqli_insert_id($conn);
        
        // Insert quotation items
        if (isset($_POST['product_name']) && is_array($_POST['product_name'])) {
            $item_query = "INSERT INTO quotation_items (quotation_id, product_name, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);
            
            for ($i = 0; $i < count($_POST['product_name']); $i++) {
                if (!empty($_POST['product_name'][$i])) {
                    $product_name = mysqli_real_escape_string($conn, $_POST['product_name'][$i]);
                    $quantity = intval($_POST['quantity'][$i]);
                    $price = floatval($_POST['price'][$i]);
                    $total_amount = $quantity * $price;
                    
                    mysqli_stmt_bind_param($item_stmt, "isidd", 
                        $quotation_id, $product_name, $quantity, 
                        $price, $total_amount
                    );
                    
                    if (!mysqli_stmt_execute($item_stmt)) {
                        throw new Exception("Error inserting quotation item: " . mysqli_error($conn));
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        if ($status === 'Draft') {
            $_SESSION['success'] = "Quotation saved as draft successfully! Reference: " . $reference;
        } else {
            $_SESSION['success'] = "Quotation created and sent successfully! Reference: " . $reference;
        }
        header("Location: quotation_list.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        header("Location: add_quotation.php");
        exit;
    }
} else {
    header("Location: add_quotation.php");
    exit;
}
?>
