<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Check if product is being used in any estimates or purchases
    $usage_query = "
        SELECT 'estimate' as type, COUNT(*) as count FROM estimate_items WHERE product_description LIKE CONCAT('%', (SELECT product_name FROM products WHERE product_id = ?), '%')
        UNION ALL
        SELECT 'purchase' as type, COUNT(*) as count FROM purchase_items WHERE product_name = (SELECT product_name FROM products WHERE product_id = ?)
    ";
    
    $usage_stmt = mysqli_prepare($conn, $usage_query);
    mysqli_stmt_bind_param($usage_stmt, "ii", $product_id, $product_id);
    mysqli_stmt_execute($usage_stmt);
    $usage_result = mysqli_stmt_get_result($usage_stmt);
    
    $total_usage = 0;
    while ($usage = mysqli_fetch_assoc($usage_result)) {
        $total_usage += $usage['count'];
    }
    
    if ($total_usage > 0) {
        $_SESSION['error'] = "Cannot delete product. It is being used in $total_usage quotation(s) or purchase(s).";
        header("Location: product_list.php");
        exit;
    }
    
    // Delete the product
    $delete_query = "DELETE FROM products WHERE product_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        if (mysqli_affected_rows($conn) > 0) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Product not found.";
        }
    } else {
        $_SESSION['error'] = "Error deleting product: " . mysqli_error($conn);
    }
    
} else {
    $_SESSION['error'] = "Invalid product ID.";
}

header("Location: product_list.php");
exit;
?>
