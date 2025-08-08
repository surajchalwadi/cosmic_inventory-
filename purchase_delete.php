<?php
session_start();
include 'config/db.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $purchase_id = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
    
    if (!$purchase_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid purchase ID']);
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First check if purchase exists
        $sql = "SELECT purchase_id FROM purchase_invoices WHERE purchase_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $purchase_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            throw new Exception("Purchase not found");
        }
        
        // Delete purchase items first (due to foreign key constraint)
        $sql = "DELETE FROM purchase_items WHERE purchase_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $purchase_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting purchase items: " . mysqli_error($conn));
        }
        
        // Delete main purchase record
        $sql = "DELETE FROM purchase_invoices WHERE purchase_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $purchase_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting purchase: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Purchase deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 