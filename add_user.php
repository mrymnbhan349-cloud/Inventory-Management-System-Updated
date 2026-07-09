<?php
include "config.php";
checkAdmin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'] === 'admin' ? 'admin' : 'merchant';

    // تشفير كلمة المرور قبل حفظها
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password, full_name, email, role)
            VALUES ('$username', '$hashed_password', '$full_name', '$email', '$role')";

    if(mysqli_query($conn, $sql)) {
        header("Location: users.php?message=تم إضافة المستخدم بنجاح");
        exit();
    } else {
        header("Location: users.php?error=حدث خطأ أثناء إضافة المستخدم (ربما اسم المستخدم مستخدم من قبل)");
        exit();
    }
}
?>
