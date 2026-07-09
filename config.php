<?php
// بدء الجلسة فقط إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إعدادات الاتصال بقاعدة البيانات
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'simple_inventory_db');

// محاولة الاتصال بقاعدة البيانات
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// التحقق من الاتصال
if($conn === false){
    die("خطأ في الاتصال: " . mysqli_connect_error());
}

// تعيين الترميز إلى UTF-8 للدعم العربي
mysqli_set_charset($conn, "utf8");

// التحقق من تسجيل الدخول
function checkLogin() {
    if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
        header("location: login.php");
        exit;
    }
}

// التحقق من أن المستخدم الحالي "أدمن" فقط (لصفحات إدارة المستخدمين)
function checkAdmin() {
    checkLogin();
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
        header("location: index.php?error=ليس لديك صلاحية للوصول لهذه الصفحة");
        exit;
    }
}

// دالة تسجيل الدخول: تبحث عن المستخدم في جدول users وتتحقق من كلمة المرور
// يدعم دخول أكثر من حساب أدمن، وكذلك حسابات التاجر
function login($username, $password) {
    global $conn;

    $username = mysqli_real_escape_string($conn, $username);
    $sql = "SELECT * FROM users WHERE username = '$username' AND is_active = 1";
    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) == 1){
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])){
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user['id'];
            $_SESSION["username"] = $user['username'];
            $_SESSION["full_name"] = $user['full_name'];
            $_SESSION["role"] = $user['role']; // admin أو merchant
            return true;
        }
    }

    return false;
}

// دالة للحصول على إحصائيات النظام
function getSystemStats() {
    global $conn;
    
    $stats = array();
    
    // عدد المنتجات
    $sql = "SELECT COUNT(*) as total FROM products";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_products'] = $row['total'];
    
    // إجمالي قيمة المخزون
    $sql = "SELECT SUM(stock_quantity * cost) as total_value FROM products";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['inventory_value'] = $row['total_value'] ? $row['total_value'] : 0;
    
    // المنتجات منخفضة المخزون
    $sql = "SELECT COUNT(*) as total FROM products WHERE stock_quantity <= min_stock_level";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['low_stock_products'] = $row['total'];

    // عدد الفئات
    $sql = "SELECT COUNT(*) as total FROM categories";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_categories'] = $row['total'];

    // عدد الموردين
    $sql = "SELECT COUNT(*) as total FROM suppliers";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_suppliers'] = $row['total'];

    // عدد العملاء
    $sql = "SELECT COUNT(*) as total FROM customers";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_customers'] = $row['total'];

    // إجمالي قيمة المبيعات
    $sql = "SELECT SUM(total_price) as total FROM sales";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_sales_value'] = $row['total'] ? $row['total'] : 0;

    // إجمالي المبالغ غير المحصّلة (ديون العملاء): مجموع فواتير البيع - مجموع المدفوعات المرتبطة بها
    $sql = "SELECT COALESCE(SUM(s.total_price), 0) - COALESCE((SELECT SUM(amount) FROM payments), 0) as total
            FROM sales s";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stats['total_due'] = $row['total'] ? $row['total'] : 0;

    return $stats;
}

// جلب جميع الفئات (تستخدم في القوائم المنسدلة وصفحة الفئات)
function getCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// جلب جميع الموردين (تستخدم في القوائم المنسدلة وصفحة الموردين)
function getSuppliers() {
    global $conn;
    $sql = "SELECT * FROM suppliers ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// جلب جميع العملاء (تستخدم في القوائم المنسدلة وصفحة العملاء)
function getCustomers() {
    global $conn;
    $sql = "SELECT * FROM customers ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// جلب جميع المنتجات (تستخدم في قوائم الشراء والبيع المنسدلة)
function getProducts() {
    global $conn;
    $sql = "SELECT id, name, sku, stock_quantity, price, cost FROM products ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// تسجيل حركة مخزون جديدة (إضافة/سحب/تعديل كمية) في سجل التتبع
function logStockMovement($product_id, $movement_type, $quantity, $note = '') {
    global $conn;

    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 'NULL';
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    $movement_type = mysqli_real_escape_string($conn, $movement_type);
    $note = mysqli_real_escape_string($conn, $note);

    $sql = "INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, note)
            VALUES ($product_id, $user_id, '$movement_type', $quantity, '$note')";
    mysqli_query($conn, $sql);
}
?>
