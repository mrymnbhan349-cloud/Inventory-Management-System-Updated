<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_id = (int)$_POST['product_id'];
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 'NULL';
    $invoice_no = mysqli_real_escape_string($conn, trim($_POST['invoice_no']));
    $quantity = (int)$_POST['quantity'];
    $unit_cost = (float)$_POST['unit_cost'];
    $total_cost = $quantity * $unit_cost;
    $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 'NULL';

    if(!$product_id || $quantity <= 0 || $unit_cost < 0){
        header("Location: purchases.php?error=يرجى إدخال بيانات صحيحة لعملية الشراء");
        exit();
    }

    $sql = "INSERT INTO purchases (product_id, supplier_id, user_id, invoice_no, quantity, unit_cost, total_cost, purchase_date, notes)
            VALUES ($product_id, $supplier_id, $user_id, '$invoice_no', $quantity, $unit_cost, $total_cost, '$purchase_date', '$notes')";

    if(mysqli_query($conn, $sql)){
        // زيادة كمية المخزون تلقائياً وتسجيل حركة "وارد"
        mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE id = $product_id");
        logStockMovement($product_id, 'in', $quantity, 'عملية شراء' . ($invoice_no ? " - فاتورة $invoice_no" : ''));

        header("Location: purchases.php?message=تم تسجيل عملية الشراء وتحديث المخزون بنجاح");
        exit();
    } else {
        header("Location: purchases.php?error=حدث خطأ أثناء تسجيل عملية الشراء");
        exit();
    }
}

header("Location: purchases.php");
exit();
?>
