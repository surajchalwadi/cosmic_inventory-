<?php
session_start();
if (!isset($_SESSION['user'])) {
	header("Location: index.php");
	exit;
}

include 'config/db.php';

// Add a test mode to view directly in browser
$test_mode = isset($_GET['test']) || isset($_GET['view']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	header("Location: quotation_list.php");
	exit;
}

$estimate_id = intval($_GET['id']);

// Fetch estimate
$estimate_query = "SELECT * FROM estimates WHERE estimate_id = ?";
$stmt = mysqli_prepare($conn, $estimate_query);
mysqli_stmt_bind_param($stmt, "i", $estimate_id);
mysqli_stmt_execute($stmt);
$estimate_result = mysqli_stmt_get_result($stmt);

if (!$estimate_result || mysqli_num_rows($estimate_result) === 0) {
	echo 'Quotation not found';
	exit;
}

$estimate = mysqli_fetch_assoc($estimate_result);

// Fetch items
$items_query = "SELECT * FROM estimate_items WHERE estimate_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $estimate_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Currency symbol
$currency_symbol = '₹';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Quotation PDF - <?= htmlspecialchars($estimate['estimate_number']) ?></title>
	<style>
		/* Enhanced PDF styling to match print version */
		html, body { 
			margin: 0; 
			padding: 0; 
			background: #ffffff; 
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
			color: #333; 
			font-size: 14px;
		}
		#pdf-root { 
			width: 210mm; 
			min-height: 297mm; 
			padding: 15mm; 
			box-sizing: border-box; 
			background: white;
			box-shadow: 0 0 20px rgba(0,0,0,0.1);
			margin: 20px auto;
		}
		.header { 
			background: #ffffff; 
			color: #333; 
			padding: 25px 30px; 
			border-bottom: 3px solid #155ba3; 
			margin-bottom: 30px;
		}
		.company-info { 
			display: flex; 
			justify-content: space-between; 
			align-items: flex-start;
		}
		.company-logo { 
			font-size: 28px; 
			font-weight: bold; 
			color: #155ba3; 
			margin-bottom: 10px;
		}
		.quotation-title { 
			text-align: right; 
			font-size: 32px; 
			font-weight: bold; 
			color: #155ba3; 
			margin-bottom: 15px;
		}
		.content { 
			padding: 0 30px 30px; 
		}
		.client-section { 
			display: flex; 
			gap: 30px; 
			margin-bottom: 40px; 
		}
		.bill-to, .ship-to { 
			flex: 1; 
			background: #f8f9fa; 
			padding: 25px; 
			border-left: 4px solid #155ba3; 
			border-radius: 6px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
		}
		.items-table { 
			width: 100%; 
			border-collapse: collapse; 
			margin-bottom: 30px; 
			border: 1px solid #ddd;
			border-radius: 6px;
			overflow: hidden;
		}
		.items-table th { 
			background: #155ba3; 
			color: white; 
			padding: 15px 12px; 
			text-align: left; 
			font-weight: 600;
		}
		.items-table td { 
			padding: 12px; 
			border-bottom: 1px solid #eee; 
			vertical-align: top;
		}
		.items-table tr:last-child td {
			border-bottom: none;
		}
		.summary-table { 
			width: 350px; 
			margin-left: auto; 
			border: 1px solid #ddd;
			border-radius: 6px;
			overflow: hidden;
			background: white;
		}
		.summary-table td { 
			padding: 10px 20px; 
			border-bottom: 1px solid #eee; 
			font-size: 14px;
		}
		.summary-table tr:last-child td {
			border-bottom: none;
		}
		.total-row { 
			font-weight: bold; 
			font-size: 18px; 
			color: #155ba3; 
			border-top: 2px solid #155ba3; 
			background: #f8f9fa;
		}
		.terms-section {
			background: #f8f9fa;
			padding: 25px;
			border-radius: 8px;
			margin-top: 40px;
			border: 1px solid #e9ecef;
		}
		.terms-title {
			font-weight: bold;
			color: #155ba3;
			margin-bottom: 20px;
			font-size: 16px;
		}
		.terms-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		.terms-list li {
			margin-bottom: 10px;
			padding-left: 25px;
			position: relative;
			line-height: 1.5;
		}
		.terms-list li:before {
			content: "•";
			position: absolute;
			left: 0;
			color: #155ba3;
			font-weight: bold;
			font-size: 16px;
		}
		.footer-section {
			text-align: center;
			padding: 25px;
			background: #f8f9fa;
			border-top: 2px solid #155ba3;
			margin-top: 40px;
			font-size: 14px;
			color: #666;
			font-weight: 500;
		}
	</style>
</head>
<body>
	<div id="pdf-root">
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
					<div class="quotation-title">QUOTATION</div>
					<div style="text-align: right; font-size: 14px;">
						<div><strong>Quotation Number:</strong> <?= htmlspecialchars($estimate['estimate_number']) ?></div>
						<div><strong>Quotation Date:</strong> <?= date('d-m-Y', strtotime($estimate['estimate_date'])) ?></div>
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
							<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($item['unit_price'], 2) ?></td>
							<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($item['amount'], 2) ?></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<table class="summary-table">
				<tr>
					<td>SUB TOTAL:</td>
					<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($estimate['subtotal'], 2) ?></td>
				</tr>
				<tr>
					<td>TAX:</td>
					<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($estimate['tax_amount'], 2) ?></td>
				</tr>
				<tr>
					<td>DISCOUNT:</td>
					<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($estimate['discount_amount'], 2) ?></td>
				</tr>
				<tr class="total-row">
					<td>TOTAL:</td>
					<td style="text-align: right;"><?= $currency_symbol ?><?= number_format($estimate['total_amount'], 2) ?></td>
				</tr>
			</table>

			<div class="terms-section">
				<div class="terms-title">Terms & Conditions</div>
				<ul class="terms-list">
					<li>Total price inclusive of CGST @9%.</li>
					<li>Total price inclusive of SGST @9%.</li>
					<li>Payment 60% advance balance 40% on installation.</li>
					<li>Prices are valid till 1 week.</li>
					<li>Delivery within 15-20 working days from the date of order confirmation.</li>
					<li>Installation charges extra if applicable.</li>
					<li>All disputes subject to Goa jurisdiction only.</li>
				</ul>
			</div>

			<div class="footer-section">
				Make all checks payable to Cosmic Solutions
			</div>
		</div>
	</div>
</body>
</html>


