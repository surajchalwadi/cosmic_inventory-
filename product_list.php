<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch products
$query = "SELECT * FROM products ORDER BY created_at DESC";
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
                    <h4 class="form-title"><i class="fas fa-cube me-2"></i>Product List</h4>
                    <small class="text-muted">Manage your Products</small>
                </div>
                <a href="add_product.php" class="btn btn-warning">
                    <i class="fas fa-plus me-1"></i> Add Product
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-warning text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search products..." id="searchInput">
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

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="productTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="5%"></th>
                            <th width="25%">Product Name</th>
                            <th width="35%">Description</th>
                            <th width="15%">Price (₹)</th>
                            <th width="10%">Status</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Determine product icon based on product name
                                $product_name = $row['product_name'];
                                $icon = '📦';
                                if (stripos($product_name, 'macbook') !== false || stripos($product_name, 'laptop') !== false) $icon = '💻';
                                elseif (stripos($product_name, 'iphone') !== false || stripos($product_name, 'phone') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'samsung') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'ipad') !== false || stripos($product_name, 'tablet') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'airpods') !== false || stripos($product_name, 'headphone') !== false) $icon = '🎧';
                                elseif (stripos($product_name, 'watch') !== false) $icon = '⌚';
                                elseif (stripos($product_name, 'tv') !== false || stripos($product_name, 'monitor') !== false) $icon = '📺';
                                
                                // Status badge
                                $status_class = $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary';
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox">
                                    </td>
                                    <td>
                                        <span style="font-size: 1.5em;"><?= $icon ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product_name) ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>
                                            <?= strlen($row['description']) > 80 ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong>₹<?= number_format($row['price'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="product_edit.php?id=<?= $row['product_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="product_delete.php?id=<?= $row['product_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this product?')" 
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-cube fa-3x mb-3"></i>
                                        <h5>No Products Found</h5>
                                        <p>Start by adding your first product</p>
                                        <a href="add_product.php" class="btn btn-warning">
                                            <i class="fas fa-plus me-1"></i> Add Product
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing <?= mysqli_num_rows($result) ?> products
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

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">
                    <i class="fas fa-file-pdf me-2"></i>PDF Preview - Products List
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                    <div>
                        <small class="text-muted">Preview your products list before downloading</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" id="downloadPdfBtn">
                            <i class="fas fa-download me-1"></i>Download PDF
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>
                </div>
                <div id="pdfPreviewContent" style="height: 70vh; overflow-y: auto;">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
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
    const tableRows = document.querySelectorAll('#productTable tbody tr');
    
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
    // Get selected products
    const selectedRows = document.querySelectorAll('.row-checkbox:checked');
    if (selectedRows.length === 0) {
        alert('Please select at least one product to preview');
        return;
    }

    // Collect selected product data
    const selectedProducts = [];
    selectedRows.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        if (cells.length > 2) {
            selectedProducts.push({
                name: cells[2].textContent.trim(),
                description: cells[3].textContent.trim(),
                price: cells[4].textContent.trim(),
                status: cells[5].textContent.trim()
            });
        }
    });

    showProductsPDFPreview(selectedProducts);
}

// New function to show PDF preview in modal
function showProductsPDFPreview(selectedProducts) {
    // Update modal title
    document.getElementById('pdfPreviewModalLabel').innerHTML = 
        `<i class="fas fa-file-pdf me-2"></i>PDF Preview - Products List (${selectedProducts.length} items)`;
    
    // Show loading spinner
    document.getElementById('pdfPreviewContent').innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading PDF preview...</span>
            </div>
        </div>
    `;
    
    // Store current products info for download
    window.currentSelectedProducts = selectedProducts;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
    modal.show();
    
    // Pass selected products as URL parameters
    const selectedData = encodeURIComponent(JSON.stringify(selectedProducts));
    const pdfUrl = `product_pdf_view.php?selected=${selectedData}&view=1&v=${Date.now()}`;
    
    // Try to fetch the PDF content directly using AJAX
    fetch(pdfUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            console.log('Products PDF content fetched successfully');
            // Create a container div and insert the HTML content
            const contentDiv = document.createElement('div');
            contentDiv.innerHTML = html;
            contentDiv.style.width = '100%';
            contentDiv.style.height = '100%';
            contentDiv.style.overflow = 'auto';
            contentDiv.style.padding = '20px';
            contentDiv.style.backgroundColor = 'white';
            
            document.getElementById('pdfPreviewContent').innerHTML = '';
            document.getElementById('pdfPreviewContent').appendChild(contentDiv);
        })
        .catch(error => {
            console.error('Error fetching products PDF content:', error);
            
            // Fallback to iframe approach
            console.log('Falling back to iframe approach...');
            const iframe = document.createElement('iframe');
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.border = 'none';
            iframe.src = pdfUrl;
            
            // Add timeout for iframe fallback
            let loadTimeout = setTimeout(() => {
                console.error('Products PDF preview loading timeout');
                document.getElementById('pdfPreviewContent').innerHTML = `
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>Unable to load PDF preview</h5>
                            <p class="text-muted">Preview is not available, but you can still download the PDF.</p>
                            <button class="btn btn-success" onclick="generateProductsPDF(window.currentSelectedProducts)">
                                <i class="fas fa-download me-1"></i>Download PDF Instead
                            </button>
                        </div>
                    </div>
                `;
            }, 5000); // 5 second timeout for fallback
            
            iframe.onload = function() {
                clearTimeout(loadTimeout);
                console.log('Products PDF iframe loaded successfully (fallback)');
                document.getElementById('pdfPreviewContent').innerHTML = '';
                document.getElementById('pdfPreviewContent').appendChild(iframe);
            };
            
            iframe.onerror = function() {
                clearTimeout(loadTimeout);
                console.error('Products PDF iframe also failed to load');
                document.getElementById('pdfPreviewContent').innerHTML = `
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>Unable to load PDF preview</h5>
                            <p class="text-muted">Preview is not available, but you can still download the PDF.</p>
                            <button class="btn btn-success" onclick="generateProductsPDF(window.currentSelectedProducts)">
                                <i class="fas fa-download me-1"></i>Download PDF Instead
                            </button>
                        </div>
                    </div>
                `;
            };
            
            // Try the iframe fallback
            document.getElementById('pdfPreviewContent').innerHTML = '';
            document.getElementById('pdfPreviewContent').appendChild(iframe);
        });
}

function generateProductsPDF(selectedProducts) {
    // Create hidden iframe to load PDF-optimized page with selected products
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    
    // Pass selected products as URL parameters
    const selectedData = encodeURIComponent(JSON.stringify(selectedProducts));
    iframe.src = 'product_pdf_view.php?selected=' + selectedData;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const content = doc.querySelector('#pdf-root') || doc.body;
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     'products_list_' + new Date().toISOString().split('T')[0] + '.pdf',
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

// Add event listener for download button in modal
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('downloadPdfBtn').addEventListener('click', function() {
        if (window.currentSelectedProducts && window.currentSelectedProducts.length > 0) {
            generateProductsPDF(window.currentSelectedProducts);
        } else {
            alert('No products selected for download.');
        }
    });
});

function exportToExcel() {
    // Get selected products
    const selectedRows = document.querySelectorAll('.row-checkbox:checked');
    if (selectedRows.length === 0) {
        alert('Please select at least one product to export');
        return;
    }

    let csv = 'Product Name,Description,Price (₹),Status\n';
    
    selectedRows.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        if (cells.length > 2) {
            const productName = cells[2].textContent.trim();
            const description = cells[3].textContent.trim().replace(/\n/g, ' ');
            const price = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            
            csv += `"${productName}","${description}","${price}","${status}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'products_export_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printTable() {
    // Get selected products
    const selectedRows = document.querySelectorAll('.row-checkbox:checked');
    if (selectedRows.length === 0) {
        alert('Please select at least one product to print');
        return;
    }

    const printWindow = window.open('', '_blank');
    
    // Create table HTML for selected products
    let tableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">#</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Product Name</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Description</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Price (₹)</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Status</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    let i = 1;
    selectedRows.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        if (cells.length > 2) {
            const productName = cells[2].textContent.trim();
            const description = cells[3].textContent.trim();
            const price = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            
            tableHTML += `
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;">${i++}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;">${productName}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;">${description}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;">${price}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;">${status}</td>
                </tr>
            `;
        }
    });
    
    tableHTML += `
            </tbody>
        </table>
    `;
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Products List</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Cosmic Solutions - Products List</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                <p>Selected Products: ${selectedRows.length}</p>
            </div>
            ${tableHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}
</script>

</body>
</html>
