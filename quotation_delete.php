<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $estimate_id = intval($_GET['id']);
    
    // Check if estimate exists
    $check_query = "SELECT estimate_id, estimate_number FROM estimates WHERE estimate_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $estimate_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "Estimate not found.";
        header("Location: quotation_list.php");
        exit;
    }
    
    $estimate = mysqli_fetch_assoc($check_result);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete estimate items first (due to foreign key constraint)
        $delete_items = "DELETE FROM estimate_items WHERE estimate_id = ?";
        $delete_items_stmt = mysqli_prepare($conn, $delete_items);
        mysqli_stmt_bind_param($delete_items_stmt, "i", $estimate_id);
        
        if (!mysqli_stmt_execute($delete_items_stmt)) {
            throw new Exception("Error deleting estimate items: " . mysqli_error($conn));
        }
        
        // Delete estimate
        $delete_estimate = "DELETE FROM estimates WHERE estimate_id = ?";
        $delete_estimate_stmt = mysqli_prepare($conn, $delete_estimate);
        mysqli_stmt_bind_param($delete_estimate_stmt, "i", $estimate_id);
        
        if (!mysqli_stmt_execute($delete_estimate_stmt)) {
            throw new Exception("Error deleting estimate: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $_SESSION['success'] = "Quotation #" . $estimate['estimate_number'] . " deleted successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
    
} else {
    $_SESSION['error'] = "Invalid estimate ID.";
}

header("Location: quotation_list.php");
exit;
?>
