<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch estimates
$query = "SELECT * FROM estimates ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
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
                <div>
                    <h4 class="form-title"><i class="fas fa-file-invoice me-2"></i>Quotations List</h4>
                    <small class="text-muted">Manage your Quotations</small>
                </div>
                <a href="add_quotation.php" class="btn btn-warning">
                    <i class="fas fa-plus me-1"></i> Add Quotation
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-warning text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-outline-danger btn-sm" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="printTable()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estimates Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="quotationTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="15%">Quotation #</th>
                            <th width="12%">Date</th>
                            <th width="15%">Bill To</th>
                            <th width="15%">Ship To</th>
                            <th width="10%">Status</th>
                            <th width="15%">Total Amount</th>
                            <th width="13%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Status badge
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'Draft': $status_class = 'bg-secondary'; break;
                                    case 'Sent': $status_class = 'bg-success'; break;
                                    case 'Approved': $status_class = 'bg-primary'; break;
                                    case 'Rejected': $status_class = 'bg-danger'; break;
                                    default: $status_class = 'bg-secondary'; break;
                                }
                                
                                // Currency symbol
                                $currency_symbol = '₹';
                                if ($row['currency_format'] === 'USD') $currency_symbol = '$';
                                elseif ($row['currency_format'] === 'EUR') $currency_symbol = '€';
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['estimate_number']) ?></strong>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($row['estimate_date'])) ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($row['bill_client_name'])): ?>
                                                <strong><?= htmlspecialchars($row['bill_client_name']) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($row['bill_company'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($row['bill_company']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($row['bill_client_name']) && empty($row['bill_company'])): ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($row['ship_client_name'])): ?>
                                                <strong><?= htmlspecialchars($row['ship_client_name']) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($row['ship_company'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($row['ship_company']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($row['ship_client_name']) && empty($row['ship_company'])): ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= $currency_symbol ?><?= number_format($row['total_amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="quotation_print.php?id=<?= $row['estimate_id'] ?>" 
                                               class="btn btn-outline-info btn-sm" 
                                               target="_blank"
                                               title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="quotation_edit.php?id=<?= $row['estimate_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="quotation_delete.php?id=<?= $row['estimate_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this quotation?')" 
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                        <h5>No Estimates Found</h5>
                                        <p>Start by creating your first estimate</p>
                                        <a href="add_quotation.php" class="btn btn-warning">
                                            <i class="fas fa-plus me-1"></i> Add Estimate
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (if needed) -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing <?= mysqli_num_rows($result) ?> estimates
                    </small>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- html2pdf for client-side PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#quotationTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Select all checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Individual checkbox handling
document.querySelectorAll('.row-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    });
});

// Export functions
function exportToPDF() {
    const selectedRows = document.querySelectorAll('.row-checkbox:checked');
    if (selectedRows.length === 0) {
        alert('Please select at least one quotation to export');
        return;
    }

    // Only support single selection for now
    if (selectedRows.length > 1) {
        alert('Please select only one quotation for PDF download for now.');
        return;
    }

    const checkbox = selectedRows[0];
    const row = checkbox.closest('tr');
    const editLink = row.querySelector('a[href*="quotation_edit.php"]');
    if (!editLink) {
        alert('Unable to locate quotation ID.');
        return;
    }

    const estimateId = editLink.href.split('id=')[1];
    const quotationNumberCell = row.querySelectorAll('td')[1];
    const quotationNumber = quotationNumberCell ? quotationNumberCell.textContent.trim() : 'Quotation';

    // Create hidden iframe to load print-friendly page
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    // Load a dedicated PDF-optimized view (no UI controls, A4 layout)
    iframe.src = 'quotation_pdf_view.php?id=' + encodeURIComponent(estimateId);
    document.body.appendChild(iframe);

    iframe.onload = function() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const content = doc.querySelector('#pdf-root') || doc.body;
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     (quotationNumber.replace(/\s+/g, '_')) + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(content).save().then(() => {
                setTimeout(() => { document.body.removeChild(iframe); }, 500);
            }).catch(() => {
                document.body.removeChild(iframe);
                alert('Failed to generate PDF.');
            });
        } catch (e) {
            document.body.removeChild(iframe);
            alert('PDF generation blocked by browser.');
        }
    };
}

function exportToExcel() {
    const table = document.getElementById('quotationTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    let csv = 'Quotation #,Date,Bill To,Ship To,Status,Total Amount\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) {
            const quotationNumber = cells[1].textContent.trim();
            const date = cells[2].textContent.trim();
            const billTo = cells[3].textContent.trim().replace(/\n/g, ' ');
            const shipTo = cells[4].textContent.trim().replace(/\n/g, ' ');
            const status = cells[5].textContent.trim();
            const total = cells[6].textContent.trim();
            
            csv += `"${quotationNumber}","${date}","${billTo}","${shipTo}","${status}","${total}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'quotations_export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printTable() {
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('quotationTable');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Quotations List</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Cosmic Solutions - Quotations List</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
            ${table.outerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}
</script>

</body>
</html>
