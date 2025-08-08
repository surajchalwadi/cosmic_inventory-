<?php
session_start();
include 'config/db.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $purchase_id = (int)$_POST['purchase_id'];
    $party_name = mysqli_real_escape_string($conn, $_POST['party_name']);
    $invoice_no = mysqli_real_escape_string($conn, $_POST['invoice_no']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $item_ids = isset($_POST['item_id']) ? $_POST['item_id'] : [];
    $product_names = isset($_POST['product_name']) ? $_POST['product_name'] : [];
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
    $prices = isset($_POST['price']) ? $_POST['price'] : [];

    // Validate required fields
    if (empty($party_name) || empty($invoice_no) || empty($delivery_date)) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: purchase_edit.php?id=$purchase_id");
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
        header("Location: purchase_edit.php?id=$purchase_id");
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        // Update main purchase record
        $sql = "UPDATE purchase_invoices SET party_name=?, invoice_no=?, delivery_date=?, notes=? WHERE purchase_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $party_name, $invoice_no, $delivery_date, $notes, $purchase_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating purchase invoice: " . mysqli_error($conn));
        }

        // Get existing item IDs from DB
        $existing_ids = [];
        $res = mysqli_query($conn, "SELECT item_id FROM purchase_items WHERE purchase_id = $purchase_id");
        while ($row = mysqli_fetch_assoc($res)) {
            $existing_ids[] = $row['item_id'];
        }

        $submitted_ids = array_filter($item_ids); // Only non-empty (existing) item_ids
        $to_delete = array_diff($existing_ids, $submitted_ids);
        // Delete removed items
        if (!empty($to_delete)) {
            $ids_str = implode(",", array_map('intval', $to_delete));
            mysqli_query($conn, "DELETE FROM purchase_items WHERE item_id IN ($ids_str)");
        }

        // Update or insert items
        for ($i = 0; $i < count($product_names); $i++) {
            $item_id = isset($item_ids[$i]) ? (int)$item_ids[$i] : 0;
            $product_name = mysqli_real_escape_string($conn, $product_names[$i]);
            $quantity = (int)$quantities[$i];
            $price = (float)$prices[$i];
            $total_price = $quantity * $price;

            if ($item_id) {
                // Update existing item
                $sql = "UPDATE purchase_items SET product_name=?, quantity=?, price=?, total_price=? WHERE item_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sidii", $product_name, $quantity, $price, $total_price, $item_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating purchase item: " . mysqli_error($conn));
                }
            } else {
                // Insert new item
                $sql = "INSERT INTO purchase_items (purchase_id, product_name, quantity, price, total_price) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isidd", $purchase_id, $product_name, $quantity, $price, $total_price);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting purchase item: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);
        $_SESSION['success'] = "Purchase updated successfully!";
        header("Location: purchase_list.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error updating purchase: " . $e->getMessage();
        header("Location: purchase_edit.php?id=$purchase_id");
        exit;
    }
} else {
    header("Location: purchase_list.php");
    exit;
}
?>