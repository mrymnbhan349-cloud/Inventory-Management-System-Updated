<?php
include "config.php";
checkLogin();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة المخزون المبسط</title>
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

        /* قسم الترحيب */
        .welcome-section {
            background: linear-gradient(120deg, var(--primary), var(--info));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .welcome-section h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        /* إحصائيات */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.blue { background-color: var(--info); }
        .stat-icon.green { background-color: var(--success); }
        .stat-icon.red { background-color: var(--danger); }

        .stat-info h3 {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* التجاوب مع الشاشات المختلفة */
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .nav {
                flex-direction: column;
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
            <a href="index.php" class="active"><i class="fas fa-home"></i> الرئيسية</a>
            <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
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

        <!-- قسم الترحيب -->
        <div class="welcome-section">
            <h2>مرحباً <?php echo $_SESSION["full_name"]; ?>!</h2>
            <p>هنا يمكنك إدارة مخزونك بكفاءة وسهولة. صلاحيتك الحالية:
                <?php echo $_SESSION['role'] === 'admin' ? 'مدير النظام' : 'تاجر'; ?></p>
        </div>
        
        <!-- الإحصائيات -->
        <div class="stats">
            <?php
            $stats = getSystemStats();
            ?>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3>إجمالي المنتجات</h3>
                    <p><?php echo $stats['total_products']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>قيمة المخزون</h3>
                    <p><?php echo number_format($stats['inventory_value'], 2); ?> ر.س</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3>منتجات منخفضة المخزون</h3>
                    <p><?php echo $stats['low_stock_products']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-info">
                    <h3>عدد الفئات</h3>
                    <p><?php echo $stats['total_categories']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h3>عدد الموردين</h3>
                    <p><?php echo $stats['total_suppliers']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <h3>عدد العملاء</h3>
                    <p><?php echo $stats['total_customers']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="stat-info">
                    <h3>إجمالي المبيعات</h3>
                    <p><?php echo number_format($stats['total_sales_value'], 2); ?> ر.س</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-info">
                    <h3>مبالغ مستحقة على العملاء</h3>
                    <p><?php echo number_format($stats['total_due'], 2); ?> ر.س</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>