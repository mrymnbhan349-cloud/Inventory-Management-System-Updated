<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $description = $_POST['description'];

    $sql = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";

    if(mysqli_query($conn, $sql)) {
        header("Location: categories.php?message=تم إضافة الفئة بنجاح");
        exit();
    } else {
        header("Location: categories.php?error=حدث خطأ أثناء إضافة الفئة (ربما الاسم مستخدم من قبل)");
        exit();
    }
}
?>
