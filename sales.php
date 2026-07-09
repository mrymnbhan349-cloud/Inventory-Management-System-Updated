<?php
include "config.php";
checkLogin();

// معالجة عمليات الحذف (يتم عكس أثر البيع على المخزون قبل الحذف)
if(isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    $sql = "SELECT * FROM sales WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $sale = mysqli_fetch_assoc($result);

    if($sale) {
        $product_id = (int)$sale['product_id'];
        $qty = (int)$sale['quantity'];

        // إعادة الكمية المباعة إلى المخزون
        mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity + $qty WHERE id = $product_id");
        logStockMovement($product_id, 'adjustment', $qty, 'حذف عملية بيع رقم ' . $id);

        // حذف المدفوعات المرتبطة تلقائياً عبر ON DELETE CASCADE، ثم حذف عملية البيع
        mysqli_query($conn, "DELETE FROM sales WHERE id = $id");
        header("Location: sales.php?message=تم حذف عملية البيع وإعادة الكمية إلى المخزون بنجاح");
        exit();
    } else {
        header("Location: sales.php?error=عملية البيع غير موجودة");
        exit();
    }
}

// إعداد البحث
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q_escaped = mysqli_real_escape_string($conn, $q);
$where = '';
if($q !== '') {
    $where = "WHERE p.name LIKE '%$q_escaped%' OR c.name LIKE '%$q_escaped%' OR sa.invoice_no LIKE '%$q_escaped%'";
}

// إعداد الترقيم (Pagination)
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) AS total FROM sales sa
              LEFT JOIN products p ON sa.product_id = p.id
              LEFT JOIN customers c ON sa.customer_id = c.id
              $where";
$count_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

$sql = "SELECT sa.*, p.name AS product_name, c.name AS customer_name, u.full_name AS user_name,
        COALESCE((SELECT SUM(amount) FROM payments WHERE sale_id = sa.id), 0) AS paid_amount
        FROM sales sa
        LEFT JOIN products p ON sa.product_id = p.id
        LEFT JOIN customers c ON sa.customer_id = c.id
        LEFT JOIN users u ON sa.user_id = u.id
        $where
        ORDER BY sa.sale_date DESC, sa.id DESC
        LIMIT $per_page OFFSET $offset";
$result = mysqli_query($conn, $sql);
$sales = mysqli_fetch_all($result, MYSQLI_ASSOC);

$products = getProducts();
$customers = getCustomers();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المبيعات - نظام إدارة المخزون المبسط</title>
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
            flex-wrap: wrap;
            gap: 1rem;
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

        .search-bar {
            background-color: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            gap: 0.75rem;
        }

        .search-bar input {
            flex: 1;
            padding: 0.7rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
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
            color: #1997b8;
        }

        .badge-warning {
            background-color: rgba(247, 37, 133, 0.15);
            color: #f72585;
        }

        .badge-danger {
            background-color: rgba(230, 57, 70, 0.15);
            color: #e63946;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.4rem 0.7rem;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d91a72;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 0.9rem;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
            background-color: #f8f9fa;
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: var(--primary);
            color: white;
        }

        .pagination .current {
            background-color: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .form-col {
                flex: 0 0 100%;
                margin-bottom: 1rem;
            }

            .form-row {
                margin-bottom: -1rem;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .search-bar {
                flex-direction: column;
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
            <h1 class="page-title"><i class="fas fa-cash-register"></i> إدارة المبيعات</h1>
            <button onclick="showAddForm()" class="btn btn-primary"><i class="fas fa-plus"></i> تسجيل عملية بيع</button>
        </div>

        <?php if(isset($_GET['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- نموذج تسجيل عملية بيع -->
        <div class="form-container" id="addForm" style="display: none;">
            <h2 style="margin-bottom: 1.5rem;">تسجيل عملية بيع جديدة</h2>
            <form action="add_sale.php" method="post">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="product_id">المنتج</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <option value="">اختر منتج</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
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
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
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
                            <label for="unit_price">سعر الوحدة</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="unit_price" name="unit_price" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="invoice_no">رقم الفاتورة</label>
                            <input type="text" class="form-control" id="invoice_no" name="invoice_no">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="sale_date">تاريخ البيع</label>
                            <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_status">حالة الدفع</label>
                            <select class="form-control" id="payment_status" name="payment_status">
                                <option value="paid">مدفوعة بالكامل</option>
                                <option value="partial">دفع جزئي</option>
                                <option value="unpaid">غير مدفوعة (آجل)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <input type="text" class="form-control" id="notes" name="notes">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ عملية البيع</button>
                    <button type="button" onclick="hideAddForm()" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-times"></i> إلغاء</button>
                </div>
                <p style="color:#6c757d; font-size: 0.85rem;">ملاحظة: سيتم التحقق من توفر الكمية في المخزون قبل الحفظ، وسيتم خصمها تلقائياً من المنتج المختار.</p>
            </form>
        </div>

        <!-- شريط البحث -->
        <form method="get" class="search-bar">
            <input type="text" name="q" placeholder="ابحث باسم المنتج أو العميل أو رقم الفاتورة..." value="<?php echo htmlspecialchars($q); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
            <?php if($q !== ''): ?>
                <a href="sales.php" class="btn" style="background:#6c757d;color:white;"><i class="fas fa-times"></i> إلغاء</a>
            <?php endif; ?>
        </form>

        <!-- جدول المبيعات -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المنتج</th>
                        <th>العميل</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                        <th>حالة الدفع</th>
                        <th>المتبقي</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($sales) > 0): ?>
                        <?php foreach($sales as $sale): ?>
                            <?php
                            $remaining = $sale['total_price'] - $sale['paid_amount'];
                            switch($sale['payment_status']) {
                                case 'paid': $status_class = 'badge-success'; $status_label = 'مدفوعة'; break;
                                case 'partial': $status_class = 'badge-warning'; $status_label = 'جزئي'; break;
                                default: $status_class = 'badge-danger'; $status_label = 'غير مدفوعة';
                            }
                            ?>
                            <tr>
                                <td><?php echo $sale['invoice_no'] ? htmlspecialchars($sale['invoice_no']) : '—'; ?></td>
                                <td><?php echo $sale['product_name'] ? htmlspecialchars($sale['product_name']) : 'منتج محذوف'; ?></td>
                                <td><?php echo $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'عميل نقدي'; ?></td>
                                <td><span class="badge badge-success"><?php echo $sale['quantity']; ?></span></td>
                                <td><?php echo number_format($sale['unit_price'], 2); ?> ر.س</td>
                                <td><?php echo number_format($sale['total_price'], 2); ?> ر.س</td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                <td><?php echo $remaining > 0 ? number_format($remaining, 2) . ' ر.س' : '—'; ?></td>
                                <td><?php echo $sale['sale_date']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                        <a href="sales.php?delete_id=<?php echo $sale['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف عملية البيع هذه؟ ستتم إعادة الكمية إلى المخزون.')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">لا توجد عمليات بيع مطابقة</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- الترقيم (Pagination) -->
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $q_param = $q !== '' ? '&q=' . urlencode($q) : '';
                    for($i = 1; $i <= $total_pages; $i++):
                        if($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="sales.php?page=<?php echo $i . $q_param; ?>"><?php echo $i; ?></a>
                        <?php endif;
                    endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
        }

        // تعبئة سعر الوحدة تلقائياً من بيانات المنتج المختار (يمكن تعديله يدوياً)
        document.getElementById('product_id').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var price = selected.getAttribute('data-price');
            if(price) {
                document.getElementById('unit_price').value = price;
            }
        });
    </script>
</body>
</html>
