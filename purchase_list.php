<?php
include 'config/db.php';
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['user']['role'];


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
            <div class="form-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="form-title"><i class="fas fa-list me-2"></i> Purchase Inward List</h4>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Party Name</th>
                            <th>Invoice No.</th>
                            <th>Delivery Date</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = mysqli_query($conn, "SELECT * FROM purchase_invoices ORDER BY purchase_id DESC");
                        if (mysqli_num_rows($result) > 0):
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['party_name']) ?></td>
                            <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['delivery_date'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                            <a href="purchase_view.php?id=<?= $row['purchase_id'] ?>" class="action-btn view-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
            <i class="fas fa-eye"></i>
        </a>
        <a href="purchase_edit.php?id=<?= $row['purchase_id'] ?>" class="action-btn edit-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <a href="purchase_print.php?id=<?= $row['purchase_id'] ?>" class="action-btn print-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Print" target="_blank">
            <i class="fas fa-print"></i>
        </a>
        <button type="button" class="action-btn delete-btn" 
                data-id="<?= $row['purchase_id'] ?>" 
                data-party="<?= htmlspecialchars($row['party_name']) ?>"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modern Action Button Styles -->
<style>
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    margin: 0 1px;
    font-size: 0.9rem;
    border: 1px solid transparent;
    transition: all 0.2s ease;
    color: #fff;
    text-decoration: none;
    background: transparent;
}
.view-btn { 
    background: #0d6efd; 
    border-color: #0d6efd;
}
.edit-btn { 
    background: #ffc107; 
    color: #000;
    border-color: #ffc107;
}
.print-btn { 
    background: #198754; 
    border-color: #198754;
}
.delete-btn { 
    background: #dc3545; 
    border-color: #dc3545;
}
.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    text-decoration: none;
}
.view-btn:hover { background: #0b5ed7; color: #fff; }
.edit-btn:hover { background: #ffca2c; color: #000; }
.print-btn:hover { background: #157347; color: #fff; }
.delete-btn:hover { background: #bb2d3b; color: #fff; }
</style>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Delete button functionality
    $('.delete-btn').click(function() {
        const purchaseId = $(this).data('id');
        const partyName = $(this).data('party');
        
        if (confirm('Are you sure you want to delete the purchase for "' + partyName + '"?\n\nThis action cannot be undone.')) {
            // Send delete request
            $.ajax({
                url: 'purchase_delete.php',
                type: 'POST',
                data: {
                    purchase_id: purchaseId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Purchase deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error occurred while deleting the purchase.');
                }
            });
        }
    });
});
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
</body>
</html>
