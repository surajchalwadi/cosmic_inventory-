<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch active products for dropdown
$products = [];
$products_query = "SELECT product_id, product_name, price FROM products WHERE status = 'Active' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);

if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
} else {
    // If products table doesn't exist, create a note
    $products_error = "Products table not found. Please run product_setup.php first.";
}

// Generate unique reference number
$reference = 'QT' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<style>
/* Ensure number input spinners are visible and functional */
input[type="number"] {
    -webkit-appearance: textfield;
    -moz-appearance: textfield;
    appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Custom spinner buttons */
.number-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.number-input-wrapper input[type="number"] {
    width: 100%;
    padding-right: 25px;
}

.spinner-buttons {
    position: absolute;
    right: 2px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    height: calc(100% - 4px);
}

.spinner-btn {
    width: 20px;
    height: 50%;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
    border-left: 1px solid #dee2e6;
}

.spinner-btn:hover {
    background: #e9ecef;
}

.spinner-btn:first-child {
    border-bottom: 1px solid #dee2e6;
    border-radius: 0 3px 0 0;
}

.spinner-btn:last-child {
    border-radius: 0 0 3px 0;
}
</style>
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
                <h4 class="form-title"><i class="fas fa-file-invoice me-2"></i>Create Quotation</h4>
                <div>
                    <a href="quotation_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-list me-1"></i> View Quotations
                    </a>
                </div>
            </div>

            <form action="quotation_save_simple.php" method="POST">
                <!-- Quotation Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quotation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Quotation #</label>
                                <input type="text" name="estimate_number" class="form-control" value="<?= $reference ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quotation Date</label>
                                <input type="date" name="estimate_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Sent">Sent</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Currency Format</label>
                                <select name="currency_format" class="form-control">
                                    <option value="INR">₹ (INR) India Rupees</option>
                                    <option value="USD">$ (USD) US Dollar</option>
                                    <option value="EUR">€ (EUR) Euro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Template</label>
                                <select name="template" class="form-control">
                                    <option value="Default" selected>Default</option>
                                    <option value="Modern">Modern</option>
                                    <option value="Classic">Classic</option>
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
                                <input type="text" name="reference" class="form-control" placeholder="Reference Number">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-control">
                                    <option value="INR" selected>(INR) India Rupees</option>
                                    <option value="USD">(USD) US Dollar</option>
                                    <option value="EUR">(EUR) Euro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salesperson</label>
                                <input type="text" name="salesperson" class="form-control" placeholder="Enter salesperson name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Global Tax</label>
                                <div class="input-group">
                                    <input type="number" name="global_tax" class="form-control" step="0.01" min="0" value="18.0">
                                    <select name="tax_type" class="form-control" style="max-width: 120px;">
                                        <option value="Percentage" selected>Percentage</option>
                                        <option value="Fixed">Fixed</option>
                                    </select>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="tax_calculate_after_discount" id="taxAfterDiscount" checked>
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
                                <input type="text" name="bill_company" class="form-control" placeholder="Company Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="bill_client_name" class="form-control" placeholder="Client Name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <textarea name="bill_address" class="form-control" rows="2" placeholder="Billing Address"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Country</label>
                                        <select name="bill_country" class="form-control">
                                            <option value="">Select Country</option>
                                            <option value="India" selected>India</option>
                                            <option value="USA">USA</option>
                                            <option value="UK">UK</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="bill_city" class="form-control" placeholder="City">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="bill_state" class="form-control" placeholder="State">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal</label>
                                <input type="text" name="bill_postal" class="form-control" placeholder="Postal Code">
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
                                <input type="text" name="ship_company" class="form-control" placeholder="Company Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="ship_client_name" class="form-control" placeholder="Client Name">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <textarea name="ship_address" class="form-control" rows="2" placeholder="Shipping Address"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Country</label>
                                        <select name="ship_country" class="form-control">
                                            <option value="">Select Country</option>
                                            <option value="India" selected>India</option>
                                            <option value="USA">USA</option>
                                            <option value="UK">UK</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="ship_city" class="form-control" placeholder="City">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="ship_state" class="form-control" placeholder="State">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal</label>
                                <input type="text" name="ship_postal" class="form-control" placeholder="Postal Code">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label">Additional Info</label>
                        <textarea name="additional_info" class="form-control" rows="3" placeholder="Additional Information"></textarea>
                    </div>
                </div>

                <!-- Line Items Section -->
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
                                    <tr class="product-row">
                                        <td>
                                            <select name="product_description[]" class="form-control product-select" required onchange="updatePrice(this)">
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= htmlspecialchars($product['product_name']) ?>" 
                                                            data-price="<?= $product['price'] ?>">
                                                        <?= htmlspecialchars($product['product_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="quantity_unit[]" class="form-control mb-1">
                                                <option value="Quantity" selected>Quantity</option>
                                                <option value="Hours">Hours</option>
                                                <option value="Days">Days</option>
                                                <option value="Pieces">Pieces</option>
                                            </select>
                                            <div class="number-input-wrapper">
                                                <input type="number" name="quantity[]" class="form-control" step="1" min="0" placeholder="0" required>
                                                <div class="spinner-buttons">
                                                    <button type="button" class="spinner-btn" onclick="incrementValue(this, 1)">▲</button>
                                                    <button type="button" class="spinner-btn" onclick="decrementValue(this, 1)">▼</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="unit_price[]" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                                        </td>
                                        <td>
                                            <select name="tax_discount_type[]" class="form-control mb-1">
                                                <option value="Select">Select</option>
                                                <option value="Tax">Tax</option>
                                                <option value="Discount">Discount</option>
                                            </select>
                                            <div class="input-group">
                                                <div class="number-input-wrapper">
                                                    <input type="number" name="tax_discount_value[]" class="form-control" step="1" min="0" placeholder="0" oninput="calculateTotals()">
                                                    <div class="spinner-buttons">
                                                        <button type="button" class="spinner-btn" onclick="incrementValue(this, 1)">▲</button>
                                                        <button type="button" class="spinner-btn" onclick="decrementValue(this, 1)">▼</button>
                                                    </div>
                                                </div>
                                                <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="amount[]" class="form-control amount-field" value="0.00" readonly>
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
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Quotation Comments/Instructions -->
                        <div class="mt-4">
                            <label class="form-label">Quotation Comments/Instructions</label>
                            <textarea name="estimate_comments" class="form-control" rows="3" placeholder="Additional comments or instructions for this quotation"></textarea>
                        </div>
                        
                        <!-- Summary Section -->
                        <div class="row mt-4">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span id="subtotal-amount">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Tax:</span>
                                            <span id="tax-amount">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Discount:</span>
                                            <span id="discount-amount">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Fees:</span>
                                            <span id="fees-amount">0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span id="total-amount">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='quotation_list.php'">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                </div>

                <input type="hidden" name="reference" value="<?= $reference ?>">
                <input type="hidden" name="action_type" value="save" id="action-type">
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Calculate amounts on input change
    $(document).on('input', 'input[name="quantity[]"], input[name="unit_price[]"], input[name="tax_discount_value[]"]', function() {
        calculateRowAmount($(this).closest('tr'));
        calculateTotals();
    });
    
    // Calculate amounts on dropdown change
    $(document).on('change', 'select[name="tax_discount_type[]"]', function() {
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
        } else {
            alert('At least one item is required.');
        }
    });
    
    // Initial calculation
    calculateTotals();
});

function addNewRow() {
    const newRow = `
        <tr class="product-row">
            <td>
                <select name="product_description[]" class="form-control product-select" required onchange="updatePrice(this)">
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= htmlspecialchars($product['product_name']) ?>" 
                                data-price="<?= $product['price'] ?>">
                            <?= htmlspecialchars($product['product_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="quantity_unit[]" class="form-control mb-1">
                    <option value="Quantity" selected>Quantity</option>
                    <option value="Hours">Hours</option>
                    <option value="Days">Days</option>
                    <option value="Pieces">Pieces</option>
                </select>
                <div class="number-input-wrapper">
                    <input type="number" name="quantity[]" class="form-control" step="1" min="0" placeholder="0" required>
                    <div class="spinner-buttons">
                        <button type="button" class="spinner-btn" onclick="incrementValue(this, 1)">▲</button>
                        <button type="button" class="spinner-btn" onclick="decrementValue(this, 1)">▼</button>
                    </div>
                </div>
            </td>
            <td>
                <input type="number" name="unit_price[]" class="form-control" step="0.01" min="0" placeholder="0.00" required>
            </td>
            <td>
                <select name="tax_discount_type[]" class="form-control mb-1">
                    <option value="Select">Select</option>
                    <option value="Tax">Tax</option>
                    <option value="Discount">Discount</option>
                </select>
                <div class="input-group">
                    <div class="number-input-wrapper">
                        <input type="number" name="tax_discount_value[]" class="form-control" step="1" min="0" placeholder="0" oninput="calculateTotals()">
                        <div class="spinner-buttons">
                            <button type="button" class="spinner-btn" onclick="incrementValue(this, 1)">▲</button>
                            <button type="button" class="spinner-btn" onclick="decrementValue(this, 1)">▼</button>
                        </div>
                    </div>
                    <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                </div>
            </td>
            <td>
                <input type="text" name="amount[]" class="form-control amount-field" value="0.00" readonly>
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
    const quantity = parseFloat(row.find('input[name="quantity[]"]').val()) || 0;
    const unitPrice = parseFloat(row.find('input[name="unit_price[]"]').val()) || 0;
    const taxDiscountType = row.find('select[name="tax_discount_type[]"]').val();
    const taxDiscountValue = parseFloat(row.find('input[name="tax_discount_value[]"]').val()) || 0;
    
    let subtotal = quantity * unitPrice;
    let amount = subtotal;
    
    if (taxDiscountType === 'Tax') {
        amount = subtotal + (subtotal * taxDiscountValue / 100);
    } else if (taxDiscountType === 'Discount') {
        amount = subtotal - (subtotal * taxDiscountValue / 100);
    }
    
    row.find('input[name="amount[]"]').val(amount.toFixed(2));
}

// Custom spinner button functions
function incrementValue(button, step) {
    const input = button.closest('.number-input-wrapper').querySelector('input[type="number"]');
    const currentValue = parseFloat(input.value) || 0;
    const newValue = currentValue + step;
    
    // Check min constraint
    const min = parseFloat(input.getAttribute('min'));
    if (!isNaN(min) && newValue < min) {
        return;
    }
    
    // Set the new value with proper decimal places
    if (step === 1) {
        input.value = Math.round(newValue);
    } else {
        input.value = newValue.toFixed(2);
    }
    
    // Trigger input event to recalculate totals
    $(input).trigger('input');
}

function decrementValue(button, step) {
    const input = button.closest('.number-input-wrapper').querySelector('input[type="number"]');
    const currentValue = parseFloat(input.value) || 0;
    const newValue = currentValue - step;
    
    // Check min constraint
    const min = parseFloat(input.getAttribute('min'));
    if (!isNaN(min) && newValue < min) {
        return;
    }
    
    // Set the new value with proper decimal places
    if (step === 1) {
        input.value = Math.round(Math.max(0, newValue));
    } else {
        input.value = Math.max(0, newValue).toFixed(2);
    }
    
    // Trigger input event to recalculate totals
    $(input).trigger('input');
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    let totalDiscount = 0;
    
    $('.product-row').each(function() {
        const quantity = parseFloat($(this).find('input[name="quantity[]"]').val()) || 0;
        const unitPrice = parseFloat($(this).find('input[name="unit_price[]"]').val()) || 0;
        const taxDiscountType = $(this).find('select[name="tax_discount_type[]"]').val();
        const taxDiscountValue = parseFloat($(this).find('input[name="tax_discount_value[]"]').val()) || 0;
        
        const rowSubtotal = quantity * unitPrice;
        subtotal += rowSubtotal;
        
        if (taxDiscountType === 'Tax') {
            totalTax += (rowSubtotal * taxDiscountValue / 100);
        } else if (taxDiscountType === 'Discount') {
            totalDiscount += (rowSubtotal * taxDiscountValue / 100);
        }
    });
    
    const globalTax = parseFloat($('input[name="global_tax"]').val()) || 0;
    const globalTaxAmount = subtotal * globalTax / 100;
    
    const total = subtotal + totalTax + globalTaxAmount - totalDiscount;
    
    $('#subtotal-amount').text(subtotal.toFixed(2));
    $('#tax-amount').text((totalTax + globalTaxAmount).toFixed(2));
    $('#discount-amount').text(totalDiscount.toFixed(2));
    $('#fees-amount').text('0.00'); // Can be extended for fees
    $('#total-amount').text(total.toFixed(2));
}

// Update global tax calculation when changed
$(document).on('input', 'input[name="global_tax"]', function() {
    calculateTotals();
});

// Auto-populate price when product is selected
function updatePrice(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const row = selectElement.closest('tr');
    const priceInput = row.querySelector('input[name="unit_price[]"]');
    
    if (price && priceInput) {
        priceInput.value = price;
        calculateRowAmount($(row));
        calculateTotals();
    }
}
</script>

</body>
</html>
