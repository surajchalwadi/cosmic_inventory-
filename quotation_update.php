<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $estimate_id = intval($_POST['estimate_id']);
    
    // Validate estimate exists
    $check_query = "SELECT estimate_id FROM estimates WHERE estimate_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $estimate_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "Estimate not found.";
        header("Location: quotation_list.php");
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update estimate details
        $update_query = "UPDATE estimates SET 
            estimate_date = ?,
            status = ?,
            currency = ?,
            global_tax = ?,
            salesperson = ?,
            bill_client_name = ?,
            bill_company = ?,
            bill_address = ?,
            estimate_comments = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE estimate_id = ?";
            
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssdsssssi", 
            $_POST['estimate_date'],
            $_POST['status'],
            $_POST['currency'],
            $_POST['global_tax'],
            $_POST['salesperson'],
            $_POST['bill_client_name'],
            $_POST['bill_company'],
            $_POST['bill_address'],
            $_POST['estimate_comments'],
            $estimate_id
        );
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error updating estimate: " . mysqli_error($conn));
        }
        
        // Delete existing items
        $delete_items = "DELETE FROM estimate_items WHERE estimate_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_items);
        mysqli_stmt_bind_param($delete_stmt, "i", $estimate_id);
        
        if (!mysqli_stmt_execute($delete_stmt)) {
            throw new Exception("Error deleting existing items: " . mysqli_error($conn));
        }
        
        // Insert new items
        $subtotal = 0;
        $tax_amount = 0;
        $discount_amount = 0;
        
        if (isset($_POST['items']) && is_array($_POST['items']['product_description'])) {
            $insert_item = "INSERT INTO estimate_items (estimate_id, product_description, quantity_unit, quantity, unit_price, tax_discount_type, tax_discount_value, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_item);
            
            for ($i = 0; $i < count($_POST['items']['product_description']); $i++) {
                $description = $_POST['items']['product_description'][$i];
                $quantity_unit = $_POST['items']['quantity_unit'][$i] ?? 'Quantity';
                $quantity = floatval($_POST['items']['quantity'][$i]);
                $unit_price = floatval($_POST['items']['unit_price'][$i]);
                $tax_discount_type = $_POST['items']['tax_discount_type'][$i];
                $tax_discount_value = floatval($_POST['items']['tax_discount_value'][$i]);
                
                // Calculate amount
                $amount = $quantity * $unit_price;
                
                // Apply tax or discount
                if ($tax_discount_type == 'Tax' && $tax_discount_value > 0) {
                    $tax_amount += ($amount * $tax_discount_value / 100);
                } elseif ($tax_discount_type == 'Discount' && $tax_discount_value > 0) {
                    $discount_amount += ($amount * $tax_discount_value / 100);
                    $amount = $amount * (1 - $tax_discount_value / 100);
                }
                
                $subtotal += $amount;
                
                mysqli_stmt_bind_param($insert_stmt, "issddsdd", 
                    $estimate_id,
                    $description,
                    $quantity_unit,
                    $quantity,
                    $unit_price,
                    $tax_discount_type,
                    $tax_discount_value,
                    $amount
                );
                
                if (!mysqli_stmt_execute($insert_stmt)) {
                    throw new Exception("Error inserting item: " . mysqli_error($conn));
                }
            }
        }
        
        // Calculate totals
        $total_amount = $subtotal + $tax_amount - $discount_amount;
        
        // Update totals in estimates table
        $update_totals = "UPDATE estimates SET 
            subtotal = ?,
            tax_amount = ?,
            discount_amount = ?,
            total_amount = ?
            WHERE estimate_id = ?";
            
        $totals_stmt = mysqli_prepare($conn, $update_totals);
        mysqli_stmt_bind_param($totals_stmt, "ddddi", 
            $subtotal,
            $tax_amount,
            $discount_amount,
            $total_amount,
            $estimate_id
        );
        
        if (!mysqli_stmt_execute($totals_stmt)) {
            throw new Exception("Error updating totals: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $_SESSION['success'] = "Quotation updated successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: quotation_list.php");
    exit;
} else {
    header("Location: quotation_list.php");
    exit;
}
?>
