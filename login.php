<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "config.php";

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

$username = $password = "";
$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    if(login($username, $password)){
        header("location: index.php");
        exit;
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة المخزون المبسط</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .system-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .system-logo i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .system-logo h1 {
            margin-top: 15px;
            color: var(--dark);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background: white;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 40px;
            color: #6c757d;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(67, 97, 238, 0.3);
        }

        .error {
            color: var(--danger);
            text-align: center;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(230, 57, 70, 0.1);
            border-radius: 10px;
            border-left: 4px solid var(--danger);
        }

        .login-info {
            margin-top: 2rem;
            padding: 1.2rem;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9rem;
            border-left: 4px solid var(--primary);
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="system-logo">
            <i class="fas fa-warehouse"></i>
            <h1>نظام إدارة المخزون المبسط</h1>
        </div>
        
        <div class="login-header">
            <h2>تسجيل الدخول إلى النظام</h2>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" required>
                <i class="fas fa-user"></i>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit" class="btn-login">تسجيل الدخول</button>
            <?php if(!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
        
        <div class="login-info">
            <strong>بيانات الدخول الافتراضية:</strong><br>
            مدير النظام: admin / admin123<br>
            مدير إضافي: admin2 / admin123<br>
            حساب تاجر: tajer1 / admin123
        </div>
    </div>
</body>
</html>