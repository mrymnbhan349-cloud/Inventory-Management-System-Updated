<?php
include "config.php";
checkLogin();

// جلب سجل حركة المخزون مع اسم المنتج واسم المستخدم الذي قام بالعملية
$sql = "SELECT sm.*, p.name AS product_name, u.full_name AS user_name
        FROM stock_movements sm
        LEFT JOIN products p ON sm.product_id = p.id
        LEFT JOIN users u ON sm.user_id = u.id
        ORDER BY sm.created_at DESC";
$result = mysqli_query($conn, $sql);
$movements = mysqli_fetch_all($result, MYSQLI_ASSOC);

// جلب المنتجات لاستخدامها في نموذج التسجيل اليدوي
$sql_products = "SELECT id, name, stock_quantity FROM products ORDER BY name ASC";
$products_result = mysqli_query($conn, $sql_products);
$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حركة المخزون - نظام إدارة المخزون المبسط</title>
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
            max-width: 1200px;
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

        .table-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: #4cc9f0;
        }

        .badge-danger {
            background-color: rgba(230, 57, 70, 0.15);
            color: #e63946;
        }

        .badge-warning {
            background-color: rgba(247, 37, 133, 0.15);
            color: #f72585;
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

        .alert-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: #1997b8;
            border-right: 4px solid var(--success);
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
            <a href="sales.php"><i class="fas fa-cash-register"></i> المبيعات</a>
            <a href="payments.php"><i class="fas fa-hand-holding-usd"></i> المدفوعات</a>
            <a href="stock_movements.php" class="active"><i class="fas fa-exchange-alt"></i> حركة المخزون</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="users.php"><i class="fas fa-users-cog"></i> المستخدمين</a>
            <?php endif; ?>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-exchange-alt"></i> حركة المخزون</h1>
            <button onclick="showAddForm()" class="btn btn-primary"><i class="fas fa-plus"></i> تسجيل حركة يدوية</button>
        </div>

        <?php if(isset($_GET['message'])): ?>
            <div class="alert alert-success"><?php echo $_GET['message']; ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
        <?php endif; ?>

        <!-- نموذج تسجيل حركة يدوية -->
        <div class="form-container" id="addForm" style="display: none;">
            <h2 style="margin-bottom: 1.5rem;">تسجيل حركة مخزون يدوية</h2>
            <form action="add_stock_movement.php" method="post">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="product_id">المنتج</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <option value="">اختر منتج</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo $p['name']; ?> (المخزون الحالي: <?php echo $p['stock_quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="movement_type">نوع الحركة</label>
                            <select class="form-control" id="movement_type" name="movement_type" required>
                                <option value="in">وارد (إضافة للمخزون)</option>
                                <option value="out">صادر (سحب من المخزون)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="quantity">الكمية</label>
                            <input type="number" min="1" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="note">ملاحظة</label>
                            <input type="text" class="form-control" id="note" name="note" placeholder="مثال: توريد جديد، جرد سنوي...">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> تسجيل الحركة</button>
                    <button type="button" onclick="hideAddForm()" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-times"></i> إلغاء</button>
                </div>
            </form>
        </div>

        <!-- جدول سجل الحركات -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>نوع الحركة</th>
                        <th>الكمية</th>
                        <th>الملاحظة</th>
                        <th>بواسطة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($movements) > 0): ?>
                        <?php foreach($movements as $movement): ?>
                            <?php
                            if($movement['movement_type'] == 'in') {
                                $type_class = 'badge-success';
                                $type_label = 'وارد';
                            } elseif($movement['movement_type'] == 'out') {
                                $type_class = 'badge-danger';
                                $type_label = 'صادر';
                            } else {
                                $type_class = 'badge-warning';
                                $type_label = 'تعديل';
                            }
                            ?>
                            <tr>
                                <td><?php echo $movement['product_name'] ? $movement['product_name'] : 'منتج محذوف'; ?></td>
                                <td><span class="badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span></td>
                                <td><?php echo $movement['quantity']; ?></td>
                                <td><?php echo $movement['note'] ? $movement['note'] : '—'; ?></td>
                                <td><?php echo $movement['user_name'] ? $movement['user_name'] : '—'; ?></td>
                                <td><?php echo $movement['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">لا توجد حركات مخزون مسجلة</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
        }
    </script>
</body>
</html>
