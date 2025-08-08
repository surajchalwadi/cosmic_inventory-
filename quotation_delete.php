<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $quotation_id = intval($_GET['id']);
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Delete quotation items first (due to foreign key constraint)
        $delete_items = "DELETE FROM quotation_items WHERE quotation_id = ?";
        $stmt_items = mysqli_prepare($conn, $delete_items);
        mysqli_stmt_bind_param($stmt_items, "i", $quotation_id);
        
        if (!mysqli_stmt_execute($stmt_items)) {
            throw new Exception("Error deleting quotation items: " . mysqli_error($conn));
        }
        
        // Delete quotation
        $delete_quotation = "DELETE FROM quotations WHERE quotation_id = ?";
        $stmt_quotation = mysqli_prepare($conn, $delete_quotation);
        mysqli_stmt_bind_param($stmt_quotation, "i", $quotation_id);
        
        if (!mysqli_stmt_execute($stmt_quotation)) {
            throw new Exception("Error deleting quotation: " . mysqli_error($conn));
        }
        
        // Check if quotation was actually deleted
        if (mysqli_affected_rows($conn) > 0) {
            mysqli_commit($conn);
            $_SESSION['success'] = "Quotation deleted successfully!";
        } else {
            throw new Exception("Quotation not found or could not be deleted.");
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid quotation ID.";
}

header("Location: quotation_list.php");
exit;
?>
