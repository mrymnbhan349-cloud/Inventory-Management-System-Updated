<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));

    if(empty($name)){
        header("Location: customers.php?error=اسم العميل مطلوب");
        exit();
    }

    $sql = "INSERT INTO customers (name, phone, email, address, notes)
            VALUES ('$name', '$phone', '$email', '$address', '$notes')";

    if(mysqli_query($conn, $sql)){
        header("Location: customers.php?message=تمت إضافة العميل بنجاح");
        exit();
    } else {
        header("Location: customers.php?error=حدث خطأ أثناء إضافة العميل");
        exit();
    }
}

header("Location: customers.php");
exit();
?>
