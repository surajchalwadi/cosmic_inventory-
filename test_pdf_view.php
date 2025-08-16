<?php
// Simple test page to view PDF structure directly in browser
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Get the estimate ID from URL
$estimate_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Redirect to the PDF view page but without download
header("Location: quotation_pdf_view.php?id=" . $estimate_id . "&test=1&v=" . time());
exit;
?>
