<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sale_id = (int)$_POST['sale_id'];
    $amount = (float)$_POST['amount'];
    $payment_method = in_array($_POST['payment_method'], ['cash', 'card', 'bank_transfer', 'other']) ? $_POST['payment_method'] : 'cash';
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 'NULL';

    if(!$sale_id || $amount <= 0){
        header("Location: payments.php?error=يرجى إدخال بيانات صحيحة للدفعة");
        exit();
    }

    // التأكد من وجود الفاتورة وحساب المتبقي عليها
    $sql_sale = "SELECT total_price, COALESCE((SELECT SUM(amount) FROM payments WHERE sale_id = $sale_id), 0) AS paid
                 FROM sales WHERE id = $sale_id";
    $sale = mysqli_fetch_assoc(mysqli_query($conn, $sql_sale));

    if(!$sale){
        header("Location: payments.php?error=الفاتورة غير موجودة");
        exit();
    }

    $remaining = $sale['total_price'] - $sale['paid'];

    if($amount > $remaining + 0.01){
        header("Location: payments.php?error=المبلغ المدخل أكبر من المتبقي على الفاتورة (المتبقي: " . number_format($remaining, 2) . ")");
        exit();
    }

    $sql = "INSERT INTO payments (sale_id, user_id, amount, payment_method, payment_date, notes)
            VALUES ($sale_id, $user_id, $amount, '$payment_method', '$payment_date', '$notes')";

    if(mysqli_query($conn, $sql)){
        // تحديث حالة الدفع في فاتورة البيع تلقائياً بناءً على إجمالي ما تم تحصيله
        $new_paid = $sale['paid'] + $amount;
        if($new_paid >= $sale['total_price'] - 0.01){
            $new_status = 'paid';
        } elseif($new_paid > 0){
            $new_status = 'partial';
        } else {
            $new_status = 'unpaid';
        }
        mysqli_query($conn, "UPDATE sales SET payment_status = '$new_status' WHERE id = $sale_id");

        header("Location: payments.php?message=تم تسجيل الدفعة بنجاح");
        exit();
    } else {
        header("Location: payments.php?error=حدث خطأ أثناء تسجيل الدفعة");
        exit();
    }
}

header("Location: payments.php");
exit();
?>
