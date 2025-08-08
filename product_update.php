<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get form data
        $product_id = intval($_POST['product_id']);
        $product_name = trim(mysqli_real_escape_string($conn, $_POST['product_name']));
        $original_name = trim(mysqli_real_escape_string($conn, $_POST['original_name']));
        $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
        $price = floatval($_POST['price']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Validate required fields
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }
        
        if ($price <= 0) {
            throw new Exception("Price must be greater than 0.");
        }
        
        // Check if product name already exists (only if name changed)
        if ($product_name !== $original_name) {
            $check_query = "SELECT product_id FROM products WHERE product_name = ? AND product_id != ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "si", $product_name, $product_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Product name already exists. Please choose a different name.");
            }
        }
        
        // Update product
        $update_query = "UPDATE products SET 
                        product_name = ?, description = ?, price = ?, status = ?, 
                        updated_at = CURRENT_TIMESTAMP 
                        WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssdsi", $product_name, $description, $price, $status, $product_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating product: " . mysqli_error($conn));
        }
        
        if (mysqli_affected_rows($conn) > 0) {
            $_SESSION['success'] = "Product '$product_name' updated successfully!";
        } else {
            $_SESSION['success'] = "No changes were made to the product.";
        }
        
        header("Location: product_list.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: product_edit.php?id=" . $product_id);
        exit;
    }
} else {
    header("Location: product_list.php");
    exit;
}
?>
