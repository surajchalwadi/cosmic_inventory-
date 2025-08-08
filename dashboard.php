<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['user']['role'];
$name = $_SESSION['user']['name'];
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php';?>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php';?>
<!-- Main Area -->
<div class="main">
   <?php include 'header.php';?>

    <!-- Role-specific dashboard content here -->
    <div class="container-fluid mt-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-3 text-white bg-primary">
                    <h5>Total Products</h5>
                    <h3>124</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-success">
                    <h5>Pending Quotations</h5>
                    <h3>18</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-warning">
                    <h5>Open Invoices</h5>
                    <h3>11</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-danger">
                    <h5>Follow-ups</h5>
                    <h3>5</h3>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
