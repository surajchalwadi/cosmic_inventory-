<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch active products for dropdown
$products_query = "SELECT product_id, product_name, price FROM products WHERE status = 'Active' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);
$products = [];
while ($product = mysqli_fetch_assoc($products_result)) {
    $products[] = $product;
}

// Get quotation ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid quotation ID.";
    header("Location: quotation_list.php");
    exit;
}

$quotation_id = intval($_GET['id']);

// Fetch quotation data (updated to new table name)
$quotation_query = "SELECT * FROM estimates WHERE estimate_id = ?";
$stmt = mysqli_prepare($conn, $quotation_query);
mysqli_stmt_bind_param($stmt, "i", $quotation_id);
mysqli_stmt_execute($stmt);
$quotation_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($quotation_result) == 0) {
    $_SESSION['error'] = "Quotation not found.";
    header("Location: quotation_list.php");
    exit;
}

$quotation = mysqli_fetch_assoc($quotation_result);

// Fetch quotation items with all necessary fields
$items_query = "SELECT 
    item_id, 
    product_description, 
    quantity_unit, 
    quantity, 
    unit_price, 
    tax_discount_type, 
    tax_discount_value,
    amount
FROM estimate_items 
WHERE estimate_id = ?
ORDER BY item_id";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $quotation_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = [
        'item_id' => $row['item_id'],
        'product_description' => $row['product_description'],
        'quantity_unit' => $row['quantity_unit'],
        'quantity' => $row['quantity'],
        'unit_price' => $row['unit_price'],
        'tax_discount_type' => $row['tax_discount_type'],
        'tax_discount_value' => $row['tax_discount_value'],
        'amount' => $row['amount']
    ];
}

// If no items found, add one empty row
if (empty($items)) {
    $items[] = [
        'item_id' => '',
        'product_description' => '',
        'quantity_unit' => 'Quantity',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_discount_type' => 'Select',
        'tax_discount_value' => 0,
        'amount' => 0
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
    <?php include 'header.php'; ?>
    <div class="container-fluid py-4">
        <div class="form-section">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="form-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="form-title"><i class="fas fa-edit me-2"></i>Edit Quotation</h4>
                <div>
                    <a href="quotation_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <small class="text-muted">Reference: <?= htmlspecialchars($quotation['reference']) ?></small>
                </div>
            </div>

            <form action="quotation_update.php" method="POST">
                <!-- Quotation Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quotation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Quotation #</label>
                                <input type="text" name="estimate_number" class="form-control" value="<?= htmlspecialchars($quotation['estimate_number']) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quotation Date</label>
                                <input type="date" name="estimate_date" class="form-control" value="<?= htmlspecialchars($quotation['estimate_date']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <?php $statusOptions = ['Draft','Sent','Approved','Rejected']; ?>
                                    <?php foreach ($statusOptions as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($quotation['status'] === $opt ? 'selected' : '') ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Currency Format</label>
                                <select name="currency_format" class="form-control">
                                    <?php $currencyFormats = ['INR'=>'₹ (INR) India Rupees','USD'=>'$ (USD) US Dollar','EUR'=>'€ (EUR) Euro']; ?>
                                    <?php foreach ($currencyFormats as $code=>$label): ?>
                                        <option value="<?= $code ?>" <?= ($quotation['currency_format'] === $code ? 'selected' : '') ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Template</label>
                                <select name="template" class="form-control">
                                    <option value="Default" <?= ($quotation['template'] === 'Default' ? 'selected' : '') ?>>Default</option>
                                    <option value="Modern" <?= ($quotation['template'] === 'Modern' ? 'selected' : '') ?>>Modern</option>
                                    <option value="Classic" <?= ($quotation['template'] === 'Classic' ? 'selected' : '') ?>>Classic</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Client</label>
                                <div class="input-group">
                                    <select name="client_id" class="form-control" id="client-select">
                                        <option value="">Select Client</option>
                                        <!-- Client options will be populated here -->
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" title="Add New Client">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($quotation['reference'] ?? '') ?>" placeholder="Reference Number">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-control">
                                    <?php $currencyOptions = ['INR'=>'(INR) India Rupees','USD'=>'(USD) US Dollar','EUR'=>'(EUR) Euro']; ?>
                                    <?php foreach ($currencyOptions as $code=>$label): ?>
                                        <option value="<?= $code ?>" <?= ($quotation['currency'] === $code ? 'selected' : '') ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salesperson</label>
                                <input type="text" name="salesperson" class="form-control" value="<?= htmlspecialchars($quotation['salesperson'] ?? '') ?>" placeholder="Enter salesperson name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Global Tax</label>
                                <div class="input-group">
                                    <input type="number" name="global_tax" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($quotation['global_tax'] ?? 18.0) ?>">
                                    <select name="tax_type" class="form-control" style="max-width: 120px;">
                                        <option value="Percentage" <?= ($quotation['tax_type'] === 'Percentage' ? 'selected' : '') ?>>Percentage</option>
                                        <option value="Fixed" <?= ($quotation['tax_type'] === 'Fixed' ? 'selected' : '') ?>>Fixed</option>
                                    </select>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="tax_calculate_after_discount" id="taxAfterDiscount" <?= ($quotation['tax_calculate_after_discount'] ? 'checked' : '') ?>>
                                    <label class="form-check-label" for="taxAfterDiscount">
                                        Tax calculate after discount
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bill To Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Bill To</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Company</label>
                                <input type="text" name="bill_company" class="form-control" value="<?= htmlspecialchars($quotation['bill_company'] ?? '') ?>" placeholder="Company Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="bill_client_name" class="form-control" value="<?= htmlspecialchars($quotation['bill_client_name'] ?? '') ?>" placeholder="Client Name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="bill_address" class="form-control" rows="2" placeholder="Street Address"><?= htmlspecialchars($quotation['bill_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="bill_country" class="form-control" value="<?= htmlspecialchars($quotation['bill_country'] ?? '') ?>" placeholder="Country">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="bill_city" class="form-control" value="<?= htmlspecialchars($quotation['bill_city'] ?? '') ?>" placeholder="City">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="bill_state" class="form-control" value="<?= htmlspecialchars($quotation['bill_state'] ?? '') ?>" placeholder="State">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="bill_postal" class="form-control" value="<?= htmlspecialchars($quotation['bill_postal'] ?? '') ?>" placeholder="Postal Code">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ship To Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Ship To</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Company</label>
                                <input type="text" name="ship_company" class="form-control" value="<?= htmlspecialchars($quotation['ship_company'] ?? '') ?>" placeholder="Company Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="ship_client_name" class="form-control" value="<?= htmlspecialchars($quotation['ship_client_name'] ?? '') ?>" placeholder="Client Name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="ship_address" class="form-control" rows="2" placeholder="Street Address"><?= htmlspecialchars($quotation['ship_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="ship_country" class="form-control" value="<?= htmlspecialchars($quotation['ship_country'] ?? '') ?>" placeholder="Country">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="ship_city" class="form-control" value="<?= htmlspecialchars($quotation['ship_city'] ?? '') ?>" placeholder="City">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="ship_state" class="form-control" value="<?= htmlspecialchars($quotation['ship_state'] ?? '') ?>" placeholder="State">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="ship_postal" class="form-control" value="<?= htmlspecialchars($quotation['ship_postal'] ?? '') ?>" placeholder="Postal Code">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sameAsBilling" onchange="copyBillingToShipping()">
                                    <label class="form-check-label" for="sameAsBilling">
                                        Same as billing address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Line Items</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="line-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Product Title/Description</th>
                                        <th style="width: 10%;">Quantity</th>
                                        <th style="width: 15%;">Unit Price</th>
                                        <th style="width: 15%;">Tax/Discount</th>
                                        <th style="width: 15%;">Amount</th>
                                        <th style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="product-items">
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr class="product-row">
                                                <td>
                                                    <input type="hidden" name="items[item_id][]" value="<?= htmlspecialchars($item['item_id'] ?? '') ?>">
                                                    <input type="text" 
                                                           name="items[product_description][]" 
                                                           class="form-control product-description" 
                                                           value="<?= htmlspecialchars($item['product_description'] ?? '') ?>" 
                                                           required>
                                                </td>
                                                <td>
                                                    <select name="items[quantity_unit][]" class="form-control mb-1 quantity-unit">
                                                        <?php 
                                                        $quantityUnits = ['Quantity' => 'Quantity', 'Hours' => 'Hours', 'Days' => 'Days', 'Pieces' => 'Pieces'];
                                                        $currentUnit = $item['quantity_unit'] ?? 'Quantity';
                                                        foreach ($quantityUnits as $key => $label): 
                                                        ?>
                                                            <option value="<?= $key ?>" <?= $currentUnit === $key ? 'selected' : '' ?>><?= $label ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="number" 
                                                           name="items[quantity][]" 
                                                           class="form-control quantity" 
                                                           step="1" 
                                                           min="0" 
                                                           value="<?= htmlspecialchars(number_format((float)($item['quantity'] ?? 1), 0, '.', '')) ?>" 
                                                           required>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text currency-symbol"><?= $quotation['currency'] ?? 'INR' ?></span>
                                                        <input type="number" 
                                                               name="items[unit_price][]" 
                                                               class="form-control unit-price" 
                                                               step="0.01" 
                                                               min="0" 
                                                               value="<?= htmlspecialchars(number_format((float)($item['unit_price'] ?? 0), 2, '.', '')) ?>" 
                                                               required>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="items[tax_discount_type][]" class="form-control mb-1 tax-discount-type">
                                                        <?php 
                                                        $tdTypes = ['Select' => 'Select', 'Tax' => 'Tax', 'Discount' => 'Discount'];
                                                        $currentType = $item['tax_discount_type'] ?? 'Select';
                                                        foreach ($tdTypes as $key => $label): 
                                                        ?>
                                                            <option value="<?= $key ?>" <?= $currentType === $key ? 'selected' : '' ?>><?= $label ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               name="items[tax_discount_value][]" 
                                                               class="form-control tax-discount-value" 
                                                               step="1" 
                                                               min="0" 
                                                               value="<?= htmlspecialchars(number_format((float)($item['tax_discount_value'] ?? 0), 0, '.', '')) ?>">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text currency-symbol"><?= $quotation['currency'] ?? 'INR' ?></span>
                                                        <input type="text" 
                                                               name="items[amount][]" 
                                                               class="form-control amount-field" 
                                                               value="<?= number_format((float)($item['amount'] ?? 0), 2, '.', '') ?>" 
                                                               readonly>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-success btn-sm add-row-btn" title="Add Row">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm remove-row-btn mt-1" title="Remove Row">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="product-row">
                                            <td>
                                                <input type="hidden" name="items[item_id][]" value="">
                                                <input type="text" 
                                                       name="items[product_description][]" 
                                                       class="form-control product-description" 
                                                       placeholder="Product/Service description" 
                                                       required>
                                            </td>
                                            <td>
                                                <select name="items[quantity_unit][]" class="form-control mb-1 quantity-unit">
                                                    <option value="Quantity" selected>Quantity</option>
                                                    <option value="Hours">Hours</option>
                                                    <option value="Days">Days</option>
                                                    <option value="Pieces">Pieces</option>
                                                </select>
                                                <input type="number" 
                                                       name="items[quantity][]" 
                                                       class="form-control quantity" 
                                                       step="1" 
                                                       min="0" 
                                                       placeholder="1" 
                                                       value="1" 
                                                       required>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text currency-symbol"><?= $quotation['currency'] ?? 'INR' ?></span>
                                                    <input type="number" 
                                                           name="items[unit_price][]" 
                                                           class="form-control unit-price" 
                                                           step="0.01" 
                                                           min="0" 
                                                           placeholder="0.00" 
                                                           value="0" 
                                                           required>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="items[tax_discount_type][]" class="form-control mb-1 tax-discount-type">
                                                    <option value="Select">Select</option>
                                                    <option value="Tax">Tax</option>
                                                    <option value="Discount">Discount</option>
                                                </select>
                                                <div class="input-group">
                                                    <input type="number" name="items[tax_discount_value][]" class="form-control tax-discount-value" step="1" min="0" placeholder="0" value="0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="items[amount][]" class="form-control amount-field" value="0.00" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-success btn-sm add-row-btn" title="Add Row">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn mt-1" title="Remove Row">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                    </div>

                        <!-- Summary Section -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Quotation Comments/Instructions</label>
                                    <textarea name="estimate_comments" class="form-control" rows="4" placeholder="Additional comments or instructions for this quotation"><?= htmlspecialchars($quotation['estimate_comments'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Summary</h6>
                                        <table class="table table-borderless mb-0">
                                            <tr>
                                                <td class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end" id="subtotal-amount">0.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end"><strong>Tax:</strong></td>
                                                <td class="text-end" id="tax-amount">0.00</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end"><strong>Discount:</strong></td>
                                                <td class="text-end" id="discount-amount">0.00</td>
                                            </tr>
                                            <tr class="table-active">
                                                <td class="text-end"><strong>Total:</strong></td>
                                                <td class="text-end" id="total-amount">0.00</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <!-- Hidden fields to store calculated values -->
                                <input type="hidden" name="subtotal" value="0">
                                <input type="hidden" name="tax_amount" value="0">
                                <input type="hidden" name="discount_amount" value="0">
                                <input type="hidden" name="total_amount" value="0">
                                
                                <div class="d-flex justify-content-end mt-3">
                                    <input type="hidden" name="estimate_id" value="<?= $quotation_id ?>">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                    <a href="quotation_list.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="reference" value="<?= htmlspecialchars($quotation['reference'] ?? '') ?>">
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Calculate amounts on input change (align with add_quotation page)
$(document).ready(function() {
    $(document).on('input', 'input[name="items[quantity][]"], input[name="items[unit_price][]"], input[name="items[tax_discount_value][]"], input[name="global_tax"]', function() {
        calculateRowAmount($(this).closest('tr'));
        calculateTotals();
    });

    $(document).on('change', 'select[name="items[tax_discount_type][]"]', function() {
        calculateRowAmount($(this).closest('tr'));
        calculateTotals();
    });

    // Add new row
    $(document).on('click', '.add-row-btn', function() {
        addNewRow();
    });

    // Remove row
    $(document).on('click', '.remove-row-btn', function() {
    if ($('.product-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });

    // Initial totals
    // Pre-calc existing rows and totals
    setTimeout(function(){
        $('#product-items .product-row').each(function(){ calculateRowAmount($(this)); });
        calculateTotals();
    }, 0);
});

function addNewRow() {
    const newRow = `
        <tr class="product-row">
            <td>
                <input type="text" name="items[description][]" class="form-control" required>
            </td>
            <td>
                <select name="items[quantity_unit][]" class="form-control mb-1">
                    <option value="Quantity" selected>Quantity</option>
                    <option value="Hours">Hours</option>
                    <option value="Days">Days</option>
                    <option value="Pieces">Pieces</option>
                </select>
                <input type="number" name="items[quantity][]" class="form-control" step="0.01" min="0" placeholder="0" required>
            </td>
            <td>
                <input type="number" name="items[unit_price][]" class="form-control" step="0.01" min="0" placeholder="0.00" required>
            </td>
            <td>
                <select name="items[tax_discount_type][]" class="form-control mb-1">
                    <option value="Select">Select</option>
                    <option value="Tax">Tax</option>
                    <option value="Discount">Discount</option>
                </select>
                <div class="input-group">
                    <input type="number" name="items[tax_discount_value][]" class="form-control" step="0.01" min="0" placeholder="0.00">
                    <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                </div>
            </td>
            <td>
                <input type="text" name="items[amount][]" class="form-control amount-field" value="0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-success btn-sm add-row-btn" title="Add Row">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm remove-row-btn mt-1" title="Remove Row">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `;
    $('#product-items').append(newRow);
}

function calculateRowAmount(row) {
    const quantity = parseFloat(row.find('input[name="items[quantity][]"]').val()) || 0;
    const unitPrice = parseFloat(row.find('input[name="items[unit_price][]"]').val()) || 0;
    const taxDiscountType = row.find('select[name="items[tax_discount_type][]"]').val();
    const taxDiscountValue = parseFloat(row.find('input[name="items[tax_discount_value][]"]').val()) || 0;

    const subtotal = quantity * unitPrice;
    let amount = subtotal;

    if (taxDiscountType === 'Tax' && taxDiscountValue > 0) {
        amount = subtotal + (subtotal * taxDiscountValue / 100);
    } else if (taxDiscountType === 'Discount' && taxDiscountValue > 0) {
        amount = subtotal - (subtotal * taxDiscountValue / 100);
    }

    row.find('input[name="items[amount][]"]').val(amount.toFixed(2));
}

function calculateRowTotal(row) {
    const quantity = parseFloat(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
    const taxDiscountType = row.find('.tax-discount-type').val();
    const taxDiscountValue = parseFloat(row.find('.tax-discount-value').val()) || 0;
    
    let amount = quantity * unitPrice;
    
    if (taxDiscountType === 'Tax') {
        amount += (amount * taxDiscountValue / 100);
    } else if (taxDiscountType === 'Discount') {
        amount -= (amount * taxDiscountValue / 100);
    }
    
    row.find('.amount-field').val(amount.toFixed(2));
    return amount;
}

function calculateRowTotal(row) {
    // Get values from the row
    const quantity = parseFloat(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
    const taxDiscountType = row.find('.tax-discount-type').val();
    const taxDiscountValue = parseFloat(row.find('.tax-discount-value').val()) || 0;
    
    // Calculate row subtotal
    const rowSubtotal = quantity * unitPrice;
    
    // Calculate tax or discount
    let rowTax = 0;
    let rowDiscount = 0;
    
    if (taxDiscountType === 'Tax') {
        rowTax = (rowSubtotal * taxDiscountValue) / 100;
    } else if (taxDiscountType === 'Discount') {
        rowDiscount = (rowSubtotal * taxDiscountValue) / 100;
    }
    
    // Calculate final row total
    const rowTotal = rowSubtotal + rowTax - rowDiscount;
    
    // Update the amount field in the row
    row.find('.amount-field').val(rowTotal.toFixed(2));
    
    // Update the hidden amount input for form submission
    row.find('input[name*="[amount]"]').val(rowTotal.toFixed(2));
    
    return {
        subtotal: rowSubtotal,
        tax: rowTax,
        discount: rowDiscount,
        total: rowTotal
    };
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    let totalDiscount = 0;
    
    // Calculate each row
    $('.product-row').each(function() {
        const row = $(this);
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const taxDiscountType = row.find('.tax-discount-type').val();
        const taxDiscountValue = parseFloat(row.find('.tax-discount-value').val()) || 0;

        // Calculate row subtotal
        const rowSubtotal = quantity * unitPrice;
        subtotal += rowSubtotal;
        
        // Calculate row tax/discount
        let rowAmount = rowSubtotal;
        if (taxDiscountType === 'Tax' && taxDiscountValue > 0) {
            const rowTax = (rowSubtotal * taxDiscountValue / 100);
            totalTax += rowTax;
        } else if (taxDiscountType === 'Discount' && taxDiscountValue > 0) {
            const rowDiscount = (rowSubtotal * taxDiscountValue / 100);
            totalDiscount += rowDiscount;
            rowAmount = rowSubtotal - rowDiscount;
        }
        
        // Update row amount display
        row.find('.amount-field').val(rowAmount.toFixed(2));
    });

    // Apply global tax
    const globalTax = parseFloat($('input[name="global_tax"]').val()) || 0;
    const globalTaxAmount = (subtotal - totalDiscount) * globalTax / 100;
    totalTax += globalTaxAmount;

    // Calculate final total
    const finalTotal = subtotal + totalTax - totalDiscount;

    // Update summary display
    $('#subtotal-amount').text(subtotal.toFixed(2));
    $('#tax-amount').text(totalTax.toFixed(2));
    $('#discount-amount').text(totalDiscount.toFixed(2));
    $('#total-amount').text(finalTotal.toFixed(2));
    
    // Update hidden fields
    $('input[name="subtotal"]').val(subtotal.toFixed(2));
    $('input[name="tax_amount"]').val(totalTax.toFixed(2));
    $('input[name="discount_amount"]').val(totalDiscount.toFixed(2));
    $('input[name="total_amount"]').val(finalTotal.toFixed(2));
}

// Initialize calculations when page loads
$(document).ready(function() {
    // Set currency symbol on page load
    const initialCurrency = $('select[name="currency"]').val();
    $('.input-group-text.currency-symbol').text(initialCurrency);
    
    // Calculate totals on page load
    calculateTotals();
    
    // Add event listeners for dynamic calculations
    $(document).on('input change', '.quantity, .unit-price, .tax-discount-value', function() {
        calculateTotals();
    });
    
    $(document).on('change', '.tax-discount-type', function() {
        calculateTotals();
    });
    
    // Add row button
    $(document).on('click', '.add-row-btn', function() {
        const newRow = $('.product-row').first().clone();
        newRow.find('input[type="text"]').val('');
        newRow.find('input[type="number"]').val('0');
        newRow.find('.product-description').val('');
        newRow.find('.quantity').val('1').attr('step', '1');
        newRow.find('.unit-price').val('0.00');
        newRow.find('.tax-discount-type').val('Select');
        newRow.find('.tax-discount-value').val('0').attr('step', '1');
        newRow.find('.amount-field').val('0.00');
        $('#product-items').append(newRow);
        calculateTotals();
    });
    
    // Remove row button
    $(document).on('click', '.remove-row-btn', function() {
        if ($('.product-row').length > 1) {
            $(this).closest('.product-row').remove();
            calculateTotals();
        } else {
            alert('At least one row is required.');
        }
    });
    
    // Global tax change handler
    $('input[name="global_tax"]').on('input', function() {
        calculateTotals();
    });
    
    // Currency change handler
    $('select[name="currency"]').on('change', function() {
        const currency = $(this).val();
        $('.input-group-text.currency-symbol').text(currency);
        calculateTotals();
    });
    
    // Prevent form submission if there are validation errors
    $('form').on('submit', function(e) {
        let hasErrors = false;
        
        // Validate required fields
        $('.product-description').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                hasErrors = true;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Update hidden fields with final values before submission
        calculateTotals();
        return true;
    });
});

// Function to copy billing address to shipping address
function copyBillingToShipping() {
    const checkbox = document.getElementById('sameAsBilling');
    if (checkbox.checked) {
        // Copy billing to shipping
        document.querySelector('input[name="ship_company"]').value = document.querySelector('input[name="bill_company"]').value;
        document.querySelector('input[name="ship_client_name"]').value = document.querySelector('input[name="bill_client_name"]').value;
        document.querySelector('textarea[name="ship_address"]').value = document.querySelector('textarea[name="bill_address"]').value;
        document.querySelector('input[name="ship_country"]').value = document.querySelector('input[name="bill_country"]').value;
        document.querySelector('input[name="ship_city"]').value = document.querySelector('input[name="bill_city"]').value;
        document.querySelector('input[name="ship_state"]').value = document.querySelector('input[name="bill_state"]').value;
        document.querySelector('input[name="ship_postal"]').value = document.querySelector('input[name="bill_postal"]').value;
    } else {
        // Clear shipping fields
        document.querySelector('input[name="ship_company"]').value = '';
        document.querySelector('input[name="ship_client_name"]').value = '';
        document.querySelector('textarea[name="ship_address"]').value = '';
        document.querySelector('input[name="ship_country"]').value = '';
        document.querySelector('input[name="ship_city"]').value = '';
        document.querySelector('input[name="ship_state"]').value = '';
        document.querySelector('input[name="ship_postal"]').value = '';
    }
}

</script>

</body>
</html>
