<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_id = (int)$_POST['product_id'];
    $movement_type = $_POST['movement_type']; // in أو out
    $quantity = (int)$_POST['quantity'];
    $note = $_POST['note'];

    // جلب الكمية الحالية للمنتج
    $sql = "SELECT stock_quantity FROM products WHERE id = $product_id";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);

    if($product){
        if($movement_type === 'in'){
            $sql = "UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE id = $product_id";
        } else {
            // لا نسمح أن تصبح الكمية أقل من صفر
            $new_quantity = max(0, $product['stock_quantity'] - $quantity);
            $sql = "UPDATE products SET stock_quantity = $new_quantity WHERE id = $product_id";
        }

        if(mysqli_query($conn, $sql)){
            logStockMovement($product_id, $movement_type, $quantity, $note);
            header("Location: stock_movements.php?message=تم تسجيل حركة المخزون بنجاح");
            exit();
        }
    }

    header("Location: stock_movements.php?error=حدث خطأ أثناء تسجيل الحركة");
    exit();
}
?>
