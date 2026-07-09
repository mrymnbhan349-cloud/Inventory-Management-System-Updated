<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $cost = $_POST['cost'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock_level = $_POST['min_stock_level'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : "NULL";
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : "NULL";
    
    $sql = "INSERT INTO products (name, sku, description, price, cost, stock_quantity, min_stock_level, category_id, supplier_id) 
            VALUES ('$name', '$sku', '$description', $price, $cost, $stock_quantity, $min_stock_level, $category_id, $supplier_id)";
    
    if(mysqli_query($conn, $sql)) {
        // تسجيل حركة المخزون كرصيد افتتاحي عند إضافة منتج جديد
        $new_product_id = mysqli_insert_id($conn);
        if($stock_quantity > 0){
            logStockMovement($new_product_id, 'in', $stock_quantity, 'رصيد افتتاحي عند إضافة المنتج');
        }

        header("Location: products.php?message=تم إضافة المنتج بنجاح");
        exit();
    } else {
        header("Location: products.php?error=حدث خطأ أثناء إضافة المنتج");
        exit();
    }
}
?>
