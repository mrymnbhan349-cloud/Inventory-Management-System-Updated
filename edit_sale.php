<?php
include "config.php";
checkLogin();

if(!isset($_GET['id'])) {
    header("Location: sales.php");
    exit();
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM sales WHERE id = $id";
$result = mysqli_query($conn, $sql);
$sale = mysqli_fetch_assoc($result);

if(!$sale) {
    header("Location: sales.php");
    exit();
}

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

    if(!$product_id || $quantity <= 0 || $unit_price < 0){
        header("Location: edit_sale.php?id=$id&error=يرجى إدخال بيانات صحيحة");
        exit();
    }

    $old_product_id = (int)$sale['product_id'];
    $old_quantity = (int)$sale['quantity'];

    // التحقق من توفر الكمية الإضافية المطلوبة في المخزون (في حال زيادة الكمية أو تغيير المنتج)
    if($old_product_id === $product_id) {
        $diff = $quantity - $old_quantity;
        if($diff > 0) {
            $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock_quantity FROM products WHERE id = $product_id"));
            if($prod['stock_quantity'] < $diff){
                header("Location: edit_sale.php?id=$id&error=الكمية الإضافية غير متوفرة في المخزون (المتوفر: {$prod['stock_quantity']})");
                exit();
            }
        }
    } else {
        $new_prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock_quantity FROM products WHERE id = $product_id"));
        if(!$new_prod || $new_prod['stock_quantity'] < $quantity){
            $available = $new_prod ? $new_prod['stock_quantity'] : 0;
            header("Location: edit_sale.php?id=$id&error=الكمية المطلوبة غير متوفرة للمنتج الجديد (المتوفر: $available)");
            exit();
        }
    }

    $sql = "UPDATE sales SET
            product_id = $product_id,
            customer_id = $customer_id,
            invoice_no = '$invoice_no',
            quantity = $quantity,
            unit_price = $unit_price,
            total_price = $total_price,
            payment_status = '$payment_status',
            sale_date = '$sale_date',
            notes = '$notes'
            WHERE id = $id";

    if(mysqli_query($conn, $sql)) {
        // تعديل المخزون حسب التغيير في الكمية أو المنتج
        if($old_product_id !== $product_id) {
            // إعادة الكمية القديمة إلى المنتج القديم، وخصم الكمية الجديدة من المنتج الجديد
            mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity + $old_quantity WHERE id = $old_product_id");
            mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE id = $product_id");
            logStockMovement($old_product_id, 'adjustment', $old_quantity, 'تعديل عملية بيع رقم ' . $id . ' (تغيير المنتج)');
            logStockMovement($product_id, 'out', $quantity, 'تعديل عملية بيع رقم ' . $id . ' (تغيير المنتج)');
        } else {
            $diff = $quantity - $old_quantity;
            if($diff !== 0) {
                if($diff > 0) {
                    mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity - $diff WHERE id = $product_id");
                } else {
                    mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity + " . abs($diff) . " WHERE id = $product_id");
                }
                logStockMovement($product_id, 'adjustment', abs($diff), 'تعديل كمية عملية بيع رقم ' . $id);
            }
        }

        header("Location: sales.php?message=تم تحديث عملية البيع وتعديل المخزون بنجاح");
        exit();
    } else {
        header("Location: sales.php?error=حدث خطأ أثناء تحديث عملية البيع");
        exit();
    }
}

$products = getProducts();
$customers = getCustomers();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل عملية بيع - نظام إدارة المخزون المبسط</title>
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
            <a href="sales.php" class="active"><i class="fas fa-cash-register"></i> المبيعات</a>
            <a href="payments.php"><i class="fas fa-hand-holding-usd"></i> المدفوعات</a>
            <a href="stock_movements.php"><i class="fas fa-exchange-alt"></i> حركة المخزون</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="users.php"><i class="fas fa-users-cog"></i> المستخدمين</a>
            <?php endif; ?>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-edit"></i> تعديل عملية بيع</h1>
            <a href="sales.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-arrow-right"></i> رجوع</a>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form action="edit_sale.php?id=<?php echo $id; ?>" method="post">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="product_id">المنتج</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <?php foreach($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $sale['product_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name']); ?> (المتوفر: <?php echo $p['stock_quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="customer_id">العميل</label>
                            <select class="form-control" id="customer_id" name="customer_id">
                                <option value="">عميل نقدي (بدون تسجيل)</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $sale['customer_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="quantity">الكمية</label>
                            <input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?php echo $sale['quantity']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="unit_price">سعر الوحدة</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="unit_price" name="unit_price" value="<?php echo $sale['unit_price']; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="invoice_no">رقم الفاتورة</label>
                            <input type="text" class="form-control" id="invoice_no" name="invoice_no" value="<?php echo htmlspecialchars($sale['invoice_no']); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="sale_date">تاريخ البيع</label>
                            <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo $sale['sale_date']; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_status">حالة الدفع</label>
                            <select class="form-control" id="payment_status" name="payment_status">
                                <option value="paid" <?php echo ($sale['payment_status'] == 'paid') ? 'selected' : ''; ?>>مدفوعة بالكامل</option>
                                <option value="partial" <?php echo ($sale['payment_status'] == 'partial') ? 'selected' : ''; ?>>دفع جزئي</option>
                                <option value="unpaid" <?php echo ($sale['payment_status'] == 'unpaid') ? 'selected' : ''; ?>>غير مدفوعة (آجل)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="<?php echo htmlspecialchars($sale['notes']); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                    <a href="sales.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-times"></i> إلغاء</a>
                </div>
                <p style="color:#6c757d; font-size: 0.85rem;">ملاحظة: عند تغيير الكمية أو المنتج، سيتم تعديل رصيد المخزون تلقائياً بعد التحقق من التوفر.</p>
            </form>
        </div>
    </div>
</body>
</html>
