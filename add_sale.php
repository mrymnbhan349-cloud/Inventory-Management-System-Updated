<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_id = (int)$_POST['product_id'];
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 'NULL';
    $invoice_no = mysqli_real_escape_string($conn, trim($_POST['invoice_no']));
    $quantity = (int)$_POST['quantity'];
    $unit_price = (float)$_POST['unit_price'];
    $total_price = $quantity * $unit_price;
    $payment_status = in_array($_POST['payment_status'], ['paid', 'partial', 'unpaid']) ? $_POST['payment_status'] : 'paid';
    $sale_date = mysqli_real_escape_string($conn, $_POST['sale_date']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 'NULL';

    if(!$product_id || $quantity <= 0 || $unit_price < 0){
        header("Location: sales.php?error=يرجى إدخال بيانات صحيحة لعملية البيع");
        exit();
    }

    // التحقق من توفر الكمية في المخزون قبل تسجيل عملية البيع
    $sql_prod = "SELECT stock_quantity FROM products WHERE id = $product_id";
    $product = mysqli_fetch_assoc(mysqli_query($conn, $sql_prod));

    if(!$product){
        header("Location: sales.php?error=المنتج غير موجود");
        exit();
    }

    if($product['stock_quantity'] < $quantity){
        header("Location: sales.php?error=الكمية المطلوبة غير متوفرة في المخزون (المتوفر: {$product['stock_quantity']})");
        exit();
    }

    $sql = "INSERT INTO sales (product_id, customer_id, user_id, invoice_no, quantity, unit_price, total_price, payment_status, sale_date, notes)
            VALUES ($product_id, $customer_id, $user_id, '$invoice_no', $quantity, $unit_price, $total_price, '$payment_status', '$sale_date', '$notes')";

    if(mysqli_query($conn, $sql)){
        $sale_id = mysqli_insert_id($conn);

        // خصم الكمية المباعة من المخزون وتسجيل حركة "صادر"
        mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE id = $product_id");
        logStockMovement($product_id, 'out', $quantity, 'عملية بيع' . ($invoice_no ? " - فاتورة $invoice_no" : ''));

        // إذا كانت الحالة مدفوعة بالكامل، نسجل دفعة تلقائية بكامل قيمة الفاتورة
        if($payment_status === 'paid'){
            mysqli_query($conn, "INSERT INTO payments (sale_id, user_id, amount, payment_method, payment_date, notes)
                VALUES ($sale_id, $user_id, $total_price, 'cash', '$sale_date', 'دفعة تلقائية عند تسجيل بيع مدفوع بالكامل')");
        }

        header("Location: sales.php?message=تم تسجيل عملية البيع وتحديث المخزون بنجاح");
        exit();
    } else {
        header("Location: sales.php?error=حدث خطأ أثناء تسجيل عملية البيع");
        exit();
    }
}

header("Location: sales.php");
exit();
?>
