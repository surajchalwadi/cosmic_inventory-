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
        
        // Get estimate details with proper escaping
        $estimate_number = mysqli_real_escape_string($conn, $_POST['estimate_number'] ?? '');
        $estimate_date = mysqli_real_escape_string($conn, $_POST['estimate_date'] ?? date('Y-m-d'));
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Draft');
        $currency_format = mysqli_real_escape_string($conn, $_POST['currency_format'] ?? 'INR');
        $template = mysqli_real_escape_string($conn, $_POST['template'] ?? 'Default');
        
        // Get client details
        $client_id = !empty($_POST['client_id']) ? intval($_POST['client_id']) : 'NULL';
        $reference = mysqli_real_escape_string($conn, $_POST['reference'] ?? '');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? 'INR');
        $salesperson = mysqli_real_escape_string($conn, $_POST['salesperson'] ?? '');
        $global_tax = floatval($_POST['global_tax'] ?? 18.0);
        $tax_type = mysqli_real_escape_string($conn, $_POST['tax_type'] ?? 'Percentage');
        $tax_calculate_after_discount = isset($_POST['tax_calculate_after_discount']) ? 1 : 0;
        
        // Get Bill To details
        $bill_company = mysqli_real_escape_string($conn, $_POST['bill_company'] ?? '');
        $bill_client_name = mysqli_real_escape_string($conn, $_POST['bill_client_name'] ?? '');
        $bill_address = mysqli_real_escape_string($conn, $_POST['bill_address'] ?? '');
        $bill_country = mysqli_real_escape_string($conn, $_POST['bill_country'] ?? '');
        $bill_city = mysqli_real_escape_string($conn, $_POST['bill_city'] ?? '');
        $bill_state = mysqli_real_escape_string($conn, $_POST['bill_state'] ?? '');
        $bill_postal = mysqli_real_escape_string($conn, $_POST['bill_postal'] ?? '');
        
        // Get Ship To details
        $ship_company = mysqli_real_escape_string($conn, $_POST['ship_company'] ?? '');
        $ship_client_name = mysqli_real_escape_string($conn, $_POST['ship_client_name'] ?? '');
        $ship_address = mysqli_real_escape_string($conn, $_POST['ship_address'] ?? '');
        $ship_country = mysqli_real_escape_string($conn, $_POST['ship_country'] ?? '');
        $ship_city = mysqli_real_escape_string($conn, $_POST['ship_city'] ?? '');
        $ship_state = mysqli_real_escape_string($conn, $_POST['ship_state'] ?? '');
        $ship_postal = mysqli_real_escape_string($conn, $_POST['ship_postal'] ?? '');
        
        // Get comments
        $estimate_comments = mysqli_real_escape_string($conn, $_POST['estimate_comments'] ?? '');
        
        $created_by = $_SESSION['user']['id'];
        
        // Calculate totals from line items
        $subtotal = 0;
        $total_tax = 0;
        $total_discount = 0;
        $fees_amount = 0;
        $item_amounts = []; // Store calculated amounts for each item
        
        if (isset($_POST['product_description']) && is_array($_POST['product_description'])) {
            for ($i = 0; $i < count($_POST['product_description']); $i++) {
                if (!empty($_POST['product_description'][$i])) {
                    $quantity = floatval($_POST['quantity'][$i] ?? 0);
                    $unit_price = floatval($_POST['unit_price'][$i] ?? 0);
                    $tax_discount_type = $_POST['tax_discount_type'][$i] ?? 'Select';
                    $tax_discount_value = floatval($_POST['tax_discount_value'][$i] ?? 0);
                    
                    // Calculate base amount for this item
                    $item_base_amount = $quantity * $unit_price;
                    $item_final_amount = $item_base_amount;
                    
                    // Apply individual item tax/discount
                    if ($tax_discount_type === 'Tax' && $tax_discount_value > 0) {
                        $item_tax = ($item_base_amount * $tax_discount_value / 100);
                        $total_tax += $item_tax;
                        $item_final_amount = $item_base_amount; // Tax is added separately
                    } elseif ($tax_discount_type === 'Discount' && $tax_discount_value > 0) {
                        $item_discount = ($item_base_amount * $tax_discount_value / 100);
                        $total_discount += $item_discount;
                        $item_final_amount = $item_base_amount - $item_discount;
                    }
                    
                    $subtotal += $item_base_amount;
                    $item_amounts[$i] = $item_final_amount; // Store final amount for this item
                }
            }
        }
        
        // Add global tax (applied to subtotal after individual discounts)
        if ($global_tax > 0) {
            $global_tax_amount = ($subtotal - $total_discount) * $global_tax / 100;
            $total_tax += $global_tax_amount;
        }
        
        $total_amount = $subtotal + $total_tax - $total_discount + $fees_amount;
        
        // Insert estimate using direct query (avoiding parameter binding issues)
        $estimate_query = "INSERT INTO estimates (
            estimate_number, estimate_date, status, currency_format, template,
            client_id, reference, currency, salesperson, global_tax, tax_type, tax_calculate_after_discount,
            bill_company, bill_client_name, bill_address, bill_country, bill_city, bill_state, bill_postal,
            ship_company, ship_client_name, ship_address, ship_country, ship_city, ship_state, ship_postal,
            estimate_comments, subtotal, tax_amount, discount_amount, fees_amount, total_amount, created_by
        ) VALUES (
            '$estimate_number', '$estimate_date', '$status', '$currency_format', '$template',
            $client_id, '$reference', '$currency', '$salesperson', $global_tax, '$tax_type', $tax_calculate_after_discount,
            '$bill_company', '$bill_client_name', '$bill_address', '$bill_country', '$bill_city', '$bill_state', '$bill_postal',
            '$ship_company', '$ship_client_name', '$ship_address', '$ship_country', '$ship_city', '$ship_state', '$ship_postal',
            '$estimate_comments', $subtotal, $total_tax, $total_discount, $fees_amount, $total_amount, $created_by
        )";
        
        if (!mysqli_query($conn, $estimate_query)) {
            throw new Exception("Error inserting estimate: " . mysqli_error($conn));
        }
        
        $estimate_id = mysqli_insert_id($conn);
        
        // Insert estimate items
        if (isset($_POST['product_description']) && is_array($_POST['product_description'])) {
            for ($i = 0; $i < count($_POST['product_description']); $i++) {
                if (!empty($_POST['product_description'][$i])) {
                    $product_description = mysqli_real_escape_string($conn, $_POST['product_description'][$i]);
                    $quantity_unit = mysqli_real_escape_string($conn, $_POST['quantity_unit'][$i] ?? 'Quantity');
                    $quantity = floatval($_POST['quantity'][$i] ?? 0);
                    $unit_price = floatval($_POST['unit_price'][$i] ?? 0);
                    $tax_discount_type = mysqli_real_escape_string($conn, $_POST['tax_discount_type'][$i] ?? 'Select');
                    $tax_discount_value = floatval($_POST['tax_discount_value'][$i] ?? 0);
                    
                    // Use the calculated amount from our calculation above
                    $amount = isset($item_amounts[$i]) ? $item_amounts[$i] : ($quantity * $unit_price);
                    
                    $item_query = "INSERT INTO estimate_items (
                        estimate_id, product_description, quantity_unit, quantity, 
                        unit_price, tax_discount_type, tax_discount_value, amount
                    ) VALUES (
                        $estimate_id, '$product_description', '$quantity_unit', $quantity,
                        $unit_price, '$tax_discount_type', $tax_discount_value, $amount
                    )";
                    
                    if (!mysqli_query($conn, $item_query)) {
                        throw new Exception("Error inserting estimate item: " . mysqli_error($conn));
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Quotation saved successfully! Quotation #: " . $estimate_number;
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
