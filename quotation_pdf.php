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

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Quotation_' . $estimate['estimate_number'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Create HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation - ' . htmlspecialchars($estimate['estimate_number']) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: white;
        }
        .header { 
            background: #155ba3; 
            color: white; 
            padding: 20px; 
            margin-bottom: 20px; 
        }
        .company-info { 
            display: flex; 
            justify-content: space-between; 
        }
        .company-logo { 
            font-size: 20px; 
            font-weight: bold; 
        }
        .quotation-title { 
            text-align: right; 
            font-size: 24px; 
            font-weight: bold; 
        }
        .client-section { 
            display: flex; 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        .bill-to, .ship-to { 
            flex: 1; 
            background: #f8f9fa; 
            padding: 15px; 
            border-left: 4px solid #155ba3; 
        }
        .items-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        .items-table th { 
            background: #155ba3; 
            color: white; 
            padding: 8px; 
            text-align: left; 
        }
        .items-table td { 
            padding: 8px; 
            border-bottom: 1px solid #eee; 
        }
        .summary-table { 
            width: 250px; 
            margin-left: auto; 
        }
        .summary-table td { 
            padding: 5px 10px; 
            border-bottom: 1px solid #eee; 
        }
        .total-row { 
            font-weight: bold; 
            font-size: 16px; 
            color: #155ba3; 
            border-top: 2px solid #155ba3; 
        }
        .terms-section { 
            background: #f8f9fa; 
            padding: 15px; 
            margin-top: 20px; 
        }
        .footer { 
            text-align: center; 
            padding: 15px; 
            background: #f8f9fa; 
            border-top: 1px solid #eee; 
            margin-top: 20px; 
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div>
                <div class="company-logo">Cosmic Solutions</div>
                <div style="font-size: 11px; line-height: 1.3;">
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
                <div style="text-align: right; font-size: 12px;">
                    <div><strong>Estimate Number:</strong> ' . htmlspecialchars($estimate['estimate_number']) . '</div>
                    <div><strong>Estimate Date:</strong> ' . date('d-m-Y', strtotime($estimate['estimate_date'])) . '</div>
                    <div><strong>Status:</strong> ' . htmlspecialchars($estimate['status']) . '</div>
                </div>
            </div>
        </div>
    </div>

    <div class="client-section">
        <div class="bill-to">
            <div style="font-weight: bold; color: #155ba3; margin-bottom: 10px;">Bill To</div>
            <div style="font-weight: bold; font-size: 14px; margin-bottom: 8px;">' . htmlspecialchars($estimate['bill_client_name'] ?? 'Not specified') . '</div>';

if (!empty($estimate['bill_company'])) {
    $html .= '<div style="color: #666; margin-bottom: 8px;">' . htmlspecialchars($estimate['bill_company']) . '</div>';
}

$html .= '<div style="font-size: 12px; line-height: 1.3; color: #555;">' . nl2br(htmlspecialchars($estimate['bill_address'] ?? '')) . '</div>
        </div>
        
        <div class="ship-to">
            <div style="font-weight: bold; color: #155ba3; margin-bottom: 10px;">Ship To</div>
            <div style="font-weight: bold; font-size: 14px; margin-bottom: 8px;">' . htmlspecialchars($estimate['ship_client_name'] ?? $estimate['bill_client_name'] ?? 'Not specified') . '</div>';

if (!empty($estimate['ship_company'])) {
    $html .= '<div style="color: #666; margin-bottom: 8px;">' . htmlspecialchars($estimate['ship_company']) . '</div>';
}

$html .= '<div style="font-size: 12px; line-height: 1.3; color: #555;">' . nl2br(htmlspecialchars($estimate['ship_address'] ?? $estimate['bill_address'] ?? '')) . '</div>
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
        <tbody>';

while ($item = mysqli_fetch_assoc($items_result)) {
    $html .= '<tr>
                <td style="text-align: center;">' . number_format($item['quantity'], 2) . '</td>
                <td style="font-weight: bold; color: #155ba3;">' . htmlspecialchars($item['product_description']) . '</td>
                <td style="font-size: 11px; color: #666;">' . htmlspecialchars($item['product_description']) . '</td>
                <td style="text-align: right;">₹' . number_format($item['unit_price'], 2) . '</td>
                <td style="text-align: right;">₹' . number_format($item['amount'], 2) . '</td>
            </tr>';
}

$html .= '</tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>SUB TOTAL:</td>
            <td style="text-align: right;">₹' . number_format($estimate['subtotal'], 2) . '</td>
        </tr>
        <tr>
            <td>TAX:</td>
            <td style="text-align: right;">₹' . number_format($estimate['tax_amount'], 2) . '</td>
        </tr>
        <tr>
            <td>DISCOUNT:</td>
            <td style="text-align: right;">₹' . number_format($estimate['discount_amount'], 2) . '</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL:</td>
            <td style="text-align: right;">₹' . number_format($estimate['total_amount'], 2) . '</td>
        </tr>
    </table>

    <div class="terms-section">
        <div style="font-weight: bold; color: #155ba3; margin-bottom: 10px;">Terms & Conditions</div>
        <ul style="list-style: none; padding: 0; margin: 0; font-size: 11px;">
            <li style="margin-bottom: 6px; padding-left: 15px; position: relative;">
                <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                Total price inclusive of CGST @9%.
            </li>
            <li style="margin-bottom: 6px; padding-left: 15px; position: relative;">
                <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                Total price inclusive of SGST @9%.
            </li>
            <li style="margin-bottom: 6px; padding-left: 15px; position: relative;">
                <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                Payment 60% advance balance 40% on installation.
            </li>
            <li style="margin-bottom: 6px; padding-left: 15px; position: relative;">
                <span style="position: absolute; left: 0; color: #155ba3; font-weight: bold;">•</span>
                Prices are valid till 1 week.
            </li>
        </ul>
    </div>

    <div class="footer" style="font-size: 12px; color: #666;">
        Make all checks payable to Cosmic Solutions
    </div>
</body>
</html>';

// For now, we'll use a simple approach - redirect to print page with auto-print
// This will trigger the browser's "Save as PDF" functionality
header('Location: quotation_print.php?id=' . $estimate_id . '&download=1');
exit;
?>
