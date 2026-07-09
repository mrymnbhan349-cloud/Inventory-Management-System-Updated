<?php
include "config.php";
checkLogin();

if(!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = $_GET['id'];

// جلب بيانات المنتج
$sql = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if(!$product) {
    header("Location: products.php");
    exit();
}

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

    $old_quantity = (int)$product['stock_quantity'];
    
    $sql = "UPDATE products SET 
            name = '$name', 
            sku = '$sku', 
            description = '$description', 
            price = $price, 
            cost = $cost, 
            stock_quantity = $stock_quantity, 
            min_stock_level = $min_stock_level,
            category_id = $category_id,
            supplier_id = $supplier_id
            WHERE id = $id";
    
    if(mysqli_query($conn, $sql)) {
        // تسجيل حركة مخزون إذا تغيرت الكمية
        $diff = (int)$stock_quantity - $old_quantity;
        if($diff != 0){
            $movement_type = $diff > 0 ? 'in' : 'out';
            logStockMovement($id, $movement_type, abs($diff), 'تعديل يدوي عبر شاشة تعديل المنتج');
        }

        header("Location: products.php?message=تم تحديث المنتج بنجاح");
        exit();
    } else {
        header("Location: products.php?error=حدث خطأ أثناء تحديث المنتج");
        exit();
    }
}

$categories = getCategories();
$suppliers = getSuppliers();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المنتج - نظام إدارة المخزون المبسط</title>
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

        /* الشريط العلوي */
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

        /* القائمة */
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

        /* رأس الصفحة */
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

        /* نموذج التعديل */
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

        /* التجاوب مع الشاشات المختلفة */
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
        <!-- الشريط العلوي -->
        <div class="topbar">
            <div class="user-info">
                <div class="avatar"><?php echo substr($_SESSION["username"], 0, 1); ?></div>
                <span><?php echo $_SESSION["username"]; ?></span>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>

        <!-- القائمة -->
                <div class="nav">
            <a href="index.php"><i class="fas fa-home"></i> الرئيسية</a>
            <a href="products.php" class="active"><i class="fas fa-box"></i> المنتجات</a>
            <a href="categories.php"><i class="fas fa-tags"></i> الفئات</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> الموردين</a>
            <a href="customers.php"><i class="fas fa-user-friends"></i> العملاء</a>
            <a href="purchases.php"><i class="fas fa-cart-arrow-down"></i> المشتريات</a>
            <a href="sales.php"><i class="fas fa-cash-register"></i> المبيعات</a>
            <a href="payments.php"><i class="fas fa-hand-holding-usd"></i> المدفوعات</a>
            <a href="stock_movements.php"><i class="fas fa-exchange-alt"></i> حركة المخزون</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="users.php"><i class="fas fa-users-cog"></i> المستخدمين</a>
            <?php endif; ?>
        </div>

        <!-- رأس الصفحة -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-edit"></i> تعديل المنتج</h1>
            <a href="products.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-arrow-right"></i> رجوع</a>
        </div>

        <!-- نموذج تعديل منتج -->
        <div class="form-container">
            <form action="edit_product.php?id=<?php echo $id; ?>" method="post">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="name">اسم المنتج</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $product['name']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="sku">رمز SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" value="<?php echo $product['sku']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">وصف المنتج</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $product['description']; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="price">سعر البيع</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="cost">التكلفة</label>
                            <input type="number" step="0.01" class="form-control" id="cost" name="cost" value="<?php echo $product['cost']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="stock_quantity">الكمية في المخزون</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="min_stock_level">الحد الأدنى للمخزون</label>
                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" value="<?php echo $product['min_stock_level']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="category_id">الفئة</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">بدون فئة</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="supplier_id">المورد</label>
                            <select class="form-control" id="supplier_id" name="supplier_id">
                                <option value="">بدون مورد</option>
                                <?php foreach($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo ($product['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>><?php echo $supplier['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                    <a href="products.php" class="btn" style="background: #6c757d; color: white;"><i class="fas fa-times"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>