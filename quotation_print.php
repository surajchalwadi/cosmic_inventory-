<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: quotation_list.php");
    exit;
}

$estimate_id = intval($_GET['id']);

// Fetch estimate data
$estimate_query = "SELECT * FROM estimates WHERE estimate_id = ?";
$stmt = mysqli_prepare($conn, $estimate_query);
mysqli_stmt_bind_param($stmt, "i", $estimate_id);
mysqli_stmt_execute($stmt);
$estimate_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($estimate_result) == 0) {
    header("Location: quotation_list.php");
    exit;
}

$estimate = mysqli_fetch_assoc($estimate_result);

// Fetch estimate items
$items_query = "SELECT * FROM estimate_items WHERE estimate_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $estimate_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - <?= htmlspecialchars($estimate['estimate_number']) ?></title>
    <style>
        @media print { .no-print { display: none !important; } }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: #ffffff; color: #1d1d1f; padding: 30px; border-bottom: 3px solid #155ba3; }
        .company-info { display: flex; justify-content: space-between; }
        .company-logo { font-size: 24px; font-weight: bold; color: #155ba3; }
        .quotation-title { text-align: right; font-size: 28px; font-weight: bold; color: #155ba3; }
        .content { padding: 30px; }
        .client-section { display: flex; gap: 30px; margin-bottom: 30px; }
        .bill-to, .ship-to { flex: 1; background: #f8f9fa; padding: 20px; border-left: 4px solid #155ba3; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #155ba3; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .summary-table { width: 300px; margin-left: auto; }
        .summary-table td { padding: 8px 15px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 18px; color: #155ba3; border-top: 2px solid #155ba3; }
        .print-btn { background: #155ba3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <?php if (!isset($_GET['download'])): ?>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="print-btn" onclick="window.print()">Print</button>
        <button class="print-btn" onclick="window.close()">Close</button>
        <a href="quotation_list.php" class="print-btn" style="text-decoration: none;">Back to List</a>
    </div>
    <?php else: ?>
    <script>
        // Auto-trigger print dialog for PDF download
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    <?php endif; ?>

    <div class="container">
        <div class="header">
            <div class="company-info">
                <div>
                    <?php 
                        $logoCandidates = ['assets/img/logo.png', 'assets/img/logo-cosmic.png'];
                        $logoPath = null;
                        foreach ($logoCandidates as $candidate) {
                            if (file_exists($candidate)) { $logoPath = $candidate; break; }
                        }
                    ?>
                    <?php if ($logoPath): ?>
                        <img src="<?= $logoPath ?>?v=<?= @filemtime($logoPath) ?: time() ?>" alt="Cosmic Solutions" style="height:60px; margin-bottom:10px; display:block;">
                    <?php endif; ?>
                    <div class="company-logo">Cosmic Solutions</div>
                    <div style="font-size: 12px; line-height: 1.4;">
                        EF-102, 1st Floor, E-boshan Building<br>
                        Boshan Hotels, Opp. Bodgeshwar Temple<br>
                        Mapusa - Goa. GSTN: 30AAMFC9553C1ZN<br>
                        Goa 403507<br>
                        Email: prajyot@cosmicsolutions.co.in<br>
                        Phone: 8390831122
                    </div>
                </div>
                <div>
                    <div class="quotation-title">ESTIMATE</div>
                    <div style="text-align: right; font-size: 14px;">
                        <div><strong>Estimate Number:</strong> <?= htmlspecialchars($estimate['estimate_number']) ?></div>
                        <div><strong>Estimate Date:</strong> <?= date('d-m-Y', strtotime($estimate['estimate_date'])) ?></div>
                        <div><strong>Status:</strong> <?= htmlspecialchars($estimate['status']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="client-section">
                <div class="bill-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Bill To</div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;"><?= htmlspecialchars($estimate['bill_client_name'] ?? 'Not specified') ?></div>
                    <?php if (!empty($estimate['bill_company'])): ?>
                        <div style="color: #666; margin-bottom: 10px;"><?= htmlspecialchars($estimate['bill_company']) ?></div>
                    <?php endif; ?>
                    <div style="font-size: 14px; line-height: 1.4; color: #555;"><?= nl2br(htmlspecialchars($estimate['bill_address'] ?? '')) ?></div>
                </div>
                
                <div class="ship-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Ship To</div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;"><?= htmlspecialchars($estimate['ship_client_name'] ?? $estimate['bill_client_name'] ?? 'Not specified') ?></div>
                    <?php if (!empty($estimate['ship_company'])): ?>
                        <div style="color: #666; margin-bottom: 10px;"><?= htmlspecialchars($estimate['ship_company']) ?></div>
                    <?php endif; ?>
                    <div style="font-size: 14px; line-height: 1.4; color: #555;"><?= nl2br(htmlspecialchars($estimate['ship_address'] ?? $estimate['bill_address'] ?? '')) ?></div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">QTY</th>
                        <th style="width: 25%;">PRODUCT</th>
                        <th style="width: 35%;">DESCRIPTION</th>
                        <th style="width: 15%;">UNIT PRICE</th>
                        <th style="width: 15%;">LINE TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                        <tr>
                            <td style="text-align: center;"><?= number_format($item['quantity'], 2) ?></td>
                            <td style="font-weight: bold; color: #155ba3;"><?= htmlspecialchars($item['product_description']) ?></td>
                            <td style="font-size: 13px; color: #666;"><?= htmlspecialchars($item['product_description']) ?></td>
                            <td style="text-align: right;">₹<?= number_format($item['unit_price'], 2) ?></td>
                            <td style="text-align: right;">₹<?= number_format($item['amount'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <table class="summary-table">
                <tr>
                    <td>SUB TOTAL:</td>
                    <td style="text-align: right;">₹<?= number_format($estimate['subtotal'], 2) ?></td>
                </tr>
                <tr>
                    <td>TAX:</td>
                    <td style="text-align: right;">₹<?= number_format($estimate['tax_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td>DISCOUNT:</td>
                    <td style="text-align: right;">₹<?= number_format($estimate['discount_amount'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">₹<?= number_format($estimate['total_amount'], 2) ?></td>
                </tr>
            </table>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 30px;">
                <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Terms & Conditions</div>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Total price inclusive of CGST @9%.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Total price inclusive of SGST @9%.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Payment 60% advance balance 40% on installation.
                    </li>
                    <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                        Prices are valid till 1 week.
                    </li>
                </ul>
            </div>

            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; margin-top: 30px; font-size: 14px; color: #666;">
                Make all checks payable to Cosmic Solutions
            </div>
        </div>
    </div>
</body>
</html>
