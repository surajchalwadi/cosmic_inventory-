<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    try {
        // Check if product is being used in any quotations or purchases
        $usage_check = "
            SELECT 'quotation' as type, COUNT(*) as count FROM quotation_items WHERE product_name = (SELECT product_name FROM products WHERE product_id = ?)
            UNION ALL
            SELECT 'purchase' as type, COUNT(*) as count FROM purchase_items WHERE product_name = (SELECT product_name FROM products WHERE product_id = ?)
        ";
        $usage_stmt = mysqli_prepare($conn, $usage_check);
        mysqli_stmt_bind_param($usage_stmt, "ii", $product_id, $product_id);
        mysqli_stmt_execute($usage_stmt);
        $usage_result = mysqli_stmt_get_result($usage_stmt);
        
        $total_usage = 0;
        while ($usage_row = mysqli_fetch_assoc($usage_result)) {
            $total_usage += $usage_row['count'];
        }
        
        if ($total_usage > 0) {
            $_SESSION['error'] = "Cannot delete product. It is being used in $total_usage quotation(s) or purchase(s).";
            header("Location: product_list.php");
            exit;
        }
        
        // Get product name for success message
        $name_query = "SELECT product_name FROM products WHERE product_id = ?";
        $name_stmt = mysqli_prepare($conn, $name_query);
        mysqli_stmt_bind_param($name_stmt, "i", $product_id);
        mysqli_stmt_execute($name_stmt);
        $name_result = mysqli_stmt_get_result($name_stmt);
        $product_name = "Unknown Product";
        
        if ($name_row = mysqli_fetch_assoc($name_result)) {
            $product_name = $name_row['product_name'];
        }
        
        // Delete product
        $delete_query = "DELETE FROM products WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting product: " . mysqli_error($conn));
        }
        
        // Check if product was actually deleted
        if (mysqli_affected_rows($conn) > 0) {
            $_SESSION['success'] = "Product '$product_name' deleted successfully!";
        } else {
            throw new Exception("Product not found or could not be deleted.");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid product ID.";
}

header("Location: product_list.php");
exit;
?>
