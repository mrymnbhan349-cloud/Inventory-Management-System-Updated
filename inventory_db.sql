-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS simple_inventory_db;
USE simple_inventory_db;

-- ==========================================================
-- 1) جدول المستخدمين (المدراء والتجار)
-- يسمح بوجود أكثر من حساب دخول، كل حساب له دور محدد (admin / merchant)
-- ==========================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    role ENUM('admin', 'merchant') NOT NULL DEFAULT 'merchant',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================================
-- 2) جدول الفئات (تصنيف المنتجات)
-- ==========================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- 3) جدول الموردين
-- ==========================================================
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150),
    phone VARCHAR(30),
    email VARCHAR(150),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- جدول المنتجات (موجود مسبقاً، تمت إضافة ربط بالفئة والمورد)
-- ==========================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 5,
    category_id INT NULL,
    supplier_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- ==========================================================
-- 4) جدول حركة المخزون (سجل بكل عملية إضافة/سحب/تعديل للكمية)
-- ==========================================================
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================================
-- بيانات افتراضية
-- ==========================================================

-- حسابات الدخول: مدير عام، مدير إضافي، وحساب تاجر
-- كلمة المرور لجميع الحسابات هي: admin123 (مشفّرة بواسطة password_hash / bcrypt)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2b$10$dW/xHdWh9mX34ZlwXA4GNunFuy6DWyw0MPN2zeqGB.W1QNhpqLmiW', 'مدير النظام', 'admin@example.com', 'admin'),
('admin2', '$2b$10$dW/xHdWh9mX34ZlwXA4GNunFuy6DWyw0MPN2zeqGB.W1QNhpqLmiW', 'مدير إضافي', 'admin2@example.com', 'admin'),
('tajer1', '$2b$10$dW/xHdWh9mX34ZlwXA4GNunFuy6DWyw0MPN2zeqGB.W1QNhpqLmiW', 'أحمد التاجر', 'tajer1@example.com', 'merchant');

-- فئات افتراضية
INSERT INTO categories (name, description) VALUES
('أجهزة كمبيوتر', 'أجهزة لابتوب وحواسيب مكتبية'),
('هواتف ذكية', 'هواتف ذكية وملحقاتها'),
('ملحقات الألعاب', 'سماعات وماوسات ولوحات مفاتيح خاصة بالألعاب');

-- موردون افتراضيون
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('شركة التقنية الحديثة', 'محمد العلي', '0599123456', 'info@moderntech.com', 'غزة - فلسطين'),
('مؤسسة الإلكترونيات المتحدة', 'سارة أحمد', '0598654321', 'sales@unitedelec.com', 'نابلس - فلسطين');

-- إضافة بعض المنتجات الافتراضية (مرتبطة بفئة ومورد)
INSERT INTO products (name, sku, description, price, cost, stock_quantity, min_stock_level, category_id, supplier_id) VALUES
('لابتوب ديل إصدار 2023', 'LP-DEL-2023', 'لابتوب ديل بمواصفات عالية، مناسب للأعمال والتصميم', 3200.00, 2500.00, 15, 5, 1, 1),
('هاتف سامسونج جالاكسي S22', 'PH-SS-S22', 'هاتف ذكي من سامسونج بشاشة 6.1 بوصة وكاميرا 50 ميجابكسل', 2800.00, 2200.00, 8, 5, 2, 2),
('سماعات ألعاب لاسلكية', 'HS-GM-WL', 'سماعات ألعاب لاسلكية مع ميكروفون واضح وعزل للضوضاء', 450.00, 300.00, 20, 10, 3, 1),
('ماوس ألعاب إضاءة RGB', 'MS-GM-RGB', 'ماوس ألعاب بدقة 6400 نقطة في البوصة وإضاءة RGB قابلة للتخصيص', 220.00, 150.00, 25, 10, 3, 1),
('لوحة مفاتيح ميكانيكية', 'KB-MCH-BL', 'لوحة مفاتيح ميكانيكية مع إضاءة خلفية ومفاتيح قابلة للبرمجة', 380.00, 250.00, 12, 5, 3, 2);

-- سجل حركة مخزون افتراضي (يمثل عمليات التوريد الأولية للمنتجات)
INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, note) VALUES
(1, 1, 'in', 15, 'رصيد افتتاحي'),
(2, 1, 'in', 8, 'رصيد افتتاحي'),
(3, 1, 'in', 20, 'رصيد افتتاحي'),
(4, 1, 'in', 25, 'رصيد افتتاحي'),
(5, 1, 'in', 12, 'رصيد افتتاحي');

-- ==========================================================
-- الجداول الجديدة المضافة إلى النظام
-- ملاحظة: جدولا الفئات (categories) والموردين (suppliers) موجودان
-- أصلاً في النظام الحالي، لذلك تمت إضافة أربعة جداول جديدة تكمّل
-- دورة عمل المتجر الكاملة: الشراء من الموردين، البيع للعملاء،
-- ومتابعة تحصيل المدفوعات (خصوصاً في حال البيع بالدّين/الآجل).
-- ==========================================================

-- ==========================================================
-- 5) جدول العملاء
-- ==========================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(150),
    address VARCHAR(255),
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- 6) جدول المشتريات (عمليات الشراء من الموردين)
-- كل سجل يمثل عملية شراء كمية من منتج معين من مورد معين
-- وعند إضافته تُحدَّث كمية المخزون تلقائياً (زيادة) وتُسجَّل حركة مخزون "وارد"
-- ==========================================================
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supplier_id INT NULL,
    user_id INT NULL,
    invoice_no VARCHAR(100),
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    purchase_date DATE NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================================
-- 7) جدول المبيعات (عمليات البيع للعملاء)
-- كل سجل يمثل عملية بيع كمية من منتج معين لعميل معين
-- وعند إضافته يتم التحقق من توفر الكمية في المخزون، ثم تُحدَّث
-- كمية المخزون تلقائياً (نقصان) وتُسجَّل حركة مخزون "صادر"
-- ==========================================================
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NULL,
    user_id INT NULL,
    invoice_no VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'paid',
    sale_date DATE NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================================
-- 8) جدول المدفوعات (تحصيل قيمة فواتير المبيعات، يدعم الدفع الجزئي/الآجل)
-- ==========================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    user_id INT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'other') NOT NULL DEFAULT 'cash',
    payment_date DATE NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================================
-- بيانات افتراضية للجداول الجديدة
-- ==========================================================

-- عملاء افتراضيون
INSERT INTO customers (name, phone, email, address, notes) VALUES
('محمد أبو شهاب', '0599112233', 'mohammad@example.com', 'نابلس - فلسطين', 'عميل دائم'),
('ليان حماد', '0598223344', 'layan@example.com', 'رام الله - فلسطين', NULL),
('مؤسسة الفجر التجارية', '0597334455', 'info@alfajr.com', 'الخليل - فلسطين', 'عميل جملة');

-- عمليات شراء افتراضية (من الموردين)
INSERT INTO purchases (product_id, supplier_id, user_id, invoice_no, quantity, unit_cost, total_cost, purchase_date, notes) VALUES
(1, 1, 1, 'PUR-1001', 5, 2500.00, 12500.00, '2026-05-10', 'دفعة توريد إضافية'),
(3, 1, 1, 'PUR-1002', 10, 300.00, 3000.00, '2026-05-15', NULL);

-- عمليات بيع افتراضية (للعملاء)
INSERT INTO sales (product_id, customer_id, user_id, invoice_no, quantity, unit_price, total_price, payment_status, sale_date, notes) VALUES
(2, 1, 1, 'INV-2001', 1, 2800.00, 2800.00, 'paid', '2026-06-01', NULL),
(4, 2, 1, 'INV-2002', 2, 220.00, 440.00, 'partial', '2026-06-05', 'دفعة أولى فقط'),
(3, 3, 1, 'INV-2003', 3, 450.00, 1350.00, 'unpaid', '2026-06-20', 'بيع آجل - عميل جملة');

-- مدفوعات افتراضية مرتبطة بفواتير البيع
INSERT INTO payments (sale_id, user_id, amount, payment_method, payment_date, notes) VALUES
(1, 1, 2800.00, 'cash', '2026-06-01', 'سداد كامل عند البيع'),
(2, 1, 200.00, 'cash', '2026-06-05', 'دفعة أولى');
