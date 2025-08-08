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
        $product_name = trim(mysqli_real_escape_string($conn, $_POST['product_name']));
        $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
        $price = floatval($_POST['price']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $created_by = $_SESSION['user']['id'];
        
        // Validate required fields
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }
        
        if ($price <= 0) {
            throw new Exception("Price must be greater than 0.");
        }
        
        // Check if product name already exists
        $check_query = "SELECT product_id FROM products WHERE product_name = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $product_name);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception("Product name already exists. Please choose a different name.");
        }
        
        // Insert product
        $insert_query = "INSERT INTO products (product_name, description, price, status, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssdsi", $product_name, $description, $price, $status, $created_by);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error saving product: " . mysqli_error($conn));
        }
        
        $_SESSION['success'] = "Product '$product_name' added successfully!";
        header("Location: product_list.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: add_product.php");
        exit;
    }
} else {
    header("Location: add_product.php");
    exit;
}
?>
