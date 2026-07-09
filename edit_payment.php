<?php
include "config.php";
checkLogin();

if(!isset($_GET['id'])) {
    header("Location: payments.php");
    exit();
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM payments WHERE id = $id";
$result = mysqli_query($conn, $sql);
$payment = mysqli_fetch_assoc($result);

if(!$payment) {
    header("Location: payments.php");
    exit();
}

$sale_id = (int)$payment['sale_id'];
$sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sales WHERE id = $sale_id"));

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $amount = (float)$_POST['amount'];
    $payment_method = in_array($_POST['payment_method'], ['cash', 'card', 'bank_transfer', 'other']) ? $_POST['payment_method'] : 'cash';
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));

    if($amount <= 0){
        header("Location: edit_payment.php?id=$id&error=يرجى إدخال مبلغ صحيح");
        exit();
    }

    // التحقق من أن المبلغ الجديد لا يتجاوز قيمة الفاتورة مع باقي الدفعات الأخرى
    $sql_other = "SELECT COALESCE(SUM(amount), 0) AS other_paid FROM payments WHERE sale_id = $sale_id AND id != $id";
    $other_paid = mysqli_fetch_assoc(mysqli_query($conn, $sql_other))['other_paid'];
    $remaining_for_this = $sale['total_price'] - $other_paid;

    if($amount > $remaining_for_this + 0.01){
        header("Location: edit_payment.php?id=$id&error=المبلغ أكبر من الحد المتاح لهذه الفاتورة (الحد الأقصى: " . number_format($remaining_for_this, 2) . ")");
        exit();
    }

    $sql = "UPDATE payments SET
            amount = $amount,
            payment_method = '$payment_method',
            payment_date = '$payment_date',
            notes = '$notes'
            WHERE id = $id";

    if(mysqli_query($conn, $sql)) {
        // إعادة حساب حالة الدفع للفاتورة المرتبطة
        $total_paid = $other_paid + $amount;
        if($total_paid >= $sale['total_price'] - 0.01){
            $new_status = 'paid';
        } elseif($total_paid > 0){
            $new_status = 'partial';
        } else {
            $new_status = 'unpaid';
        }
        mysqli_query($conn, "UPDATE sales SET payment_status = '$new_status' WHERE id = $sale_id");

        header("Location: payments.php?message=تم تحديث الدفعة بنجاح");
        exit();
    } else {
        header("Location: payments.php?error=حدث خطأ أثناء تحديث الدفعة");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل دفعة - نظام إدارة المخزون المبسط</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --background: #f0f2f5;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background-color: var(--background);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .topbar {
            background-color: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--card-shadow);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-left: 10px;
            font-weight: bold;
        }

        .logout-btn {
            background-color: var(--danger);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .logout-btn i {
            margin-left: 5px;
        }

        .nav {
            display: flex;
            flex-wrap: wrap;
            background: white;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .nav a {
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .nav a:hover, .nav a.active {
            background-color: var(--primary);
            color: white;
        }

        .nav a i {
            margin-left: 8px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-left: 10px;
            color: var(--primary);
        }

        .btn {
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn i {
            margin-left: 5px;
        }

        .form-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }

        .form-col {
            flex: 1 0 0%;
            padding: 0 10px;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger);
            border-right: 4px solid var(--danger);
        }

        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #495057;
        }

        @media (max-width: 768px) {
            .form-col {
                flex: 0 0 100%;
                margin-bottom: 1rem;
            }

            .form-row {
                margin-bottom: -1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div class="user-info">
                <div class="avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                <span><?php echo $_SESSION["username"]; ?></span>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>

        <div class="nav">
            <a href="index.php"><i class="fas fa-home"></i> الرئيسية</a>
            <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
            <a href="categories.php"><i class="fas fa-tags"></i> الفئات</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> الموردين</a>
            <a href="customers.php"><i class="fas fa-user-friends"></i> العملاء</a>
            <a href="purchases.php"><i class="fas fa-cart-arrow-down"></i> المشتريات</a>
            <a href="sales.php"><i class="fas fa-cash-register"></i> المبيعات</a>
            <a href="payments.php" class="active"><i class="fas fa-hand-holding-usd"></i> المدفوعات</a>
            <a href="stock_movements.php"><i class="fas fa-exchange-alt"></i> حركة المخزون</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="users.php"><i class="fas fa-users-cog"></i> المستخدمين</a>
            <?php endif; ?>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-edit"></i> تعديل دفعة</h1>
            <a href="payments.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-arrow-right"></i> رجوع</a>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="info-box">
            الفاتورة: <strong><?php echo $sale['invoice_no'] ? htmlspecialchars($sale['invoice_no']) : ('#' . $sale['id']); ?></strong>
            — قيمة الفاتورة الإجمالية: <strong><?php echo number_format($sale['total_price'], 2); ?> ر.س</strong>
        </div>

        <div class="form-container">
            <form action="edit_payment.php?id=<?php echo $id; ?>" method="post">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="amount">المبلغ المدفوع</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo $payment['amount']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_method">طريقة الدفع</label>
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="cash" <?php echo ($payment['payment_method'] == 'cash') ? 'selected' : ''; ?>>نقداً</option>
                                <option value="card" <?php echo ($payment['payment_method'] == 'card') ? 'selected' : ''; ?>>بطاقة</option>
                                <option value="bank_transfer" <?php echo ($payment['payment_method'] == 'bank_transfer') ? 'selected' : ''; ?>>تحويل بنكي</option>
                                <option value="other" <?php echo ($payment['payment_method'] == 'other') ? 'selected' : ''; ?>>أخرى</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_date">تاريخ الدفعة</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo $payment['payment_date']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="<?php echo htmlspecialchars($payment['notes']); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                    <a href="payments.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-times"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
