<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    die('Unauthorized');
}

$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$purchase_id) die('Invalid ID');

// Get purchase details
$sql = "SELECT pi.*, u.name as created_by_name 
        FROM purchase_invoices pi 
        LEFT JOIN users u ON pi.created_by = u.user_id 
        WHERE pi.purchase_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$purchase = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$purchase) die('Not found');

// Get purchase items
$sql = "SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY item_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Invoice #<?= $purchase_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .invoice-header { border-bottom: 2px solid #000 !important; }
            .table { border: 1px solid #000 !important; }
            .table th, .table td { border: 1px solid #000 !important; }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #ddd;
        }
        
        .invoice-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .invoice-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .invoice-body {
            padding: 30px;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: #212529;
            margin-top: 2px;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .table th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .total-row {
            background: #e9ecef !important;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .total-row td {
            border-top: 2px solid #dee2e6;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn {
            margin: 0 5px;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        
        .notes-label {
            font-weight: 600;
            color: #856404;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><i class="fas fa-receipt me-3"></i>PURCHASE INVOICE</h1>
            <div class="subtitle">Cosmic Panel - Inventory Management System</div>
            <div class="subtitle">Invoice #<?= $purchase_id ?></div>
        </div>
        
        <div class="invoice-body">
            <div class="info-section">
                <div>
                    <div class="info-item">
                        <div class="info-label">Party Name</div>
                        <div class="info-value"><?= htmlspecialchars($purchase['party_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Invoice Number</div>
                        <div class="info-value"><?= htmlspecialchars($purchase['invoice_no']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Delivery Date</div>
                        <div class="info-value"><?= date('d-m-Y', strtotime($purchase['delivery_date'])) ?></div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Created By</div>
                        <div class="info-value"><?= htmlspecialchars($purchase['created_by_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Created Date</div>
                        <div class="info-value"><?= date('d-m-Y H:i', strtotime($purchase['created_at'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value"><span class="badge bg-success">Completed</span></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($purchase['notes'])): ?>
            <div class="notes-section">
                <div class="notes-label"><i class="fas fa-sticky-note me-2"></i>Notes</div>
                <div><?= nl2br(htmlspecialchars($purchase['notes'])) ?></div>
            </div>
            <?php endif; ?>
            
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price (₹)</th>
                        <th>Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $grand_total = 0;
                    while ($item = mysqli_fetch_assoc($items)): 
                        $grand_total += $item['total_price'];
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₹<?= number_format($item['price'], 2) ?></td>
                        <td>₹<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr class="total-row">
                        <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                        <td><strong>₹<?= number_format($grand_total, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="action-buttons no-print">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Invoice
                </button>
                <a href="purchase_list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>