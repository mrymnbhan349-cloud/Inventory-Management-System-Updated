<?php
include "config.php";
checkLogin();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $contact_person = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address)
            VALUES ('$name', '$contact_person', '$phone', '$email', '$address')";

    if(mysqli_query($conn, $sql)) {
        header("Location: suppliers.php?message=تم إضافة المورد بنجاح");
        exit();
    } else {
        header("Location: suppliers.php?error=حدث خطأ أثناء إضافة المورد");
        exit();
    }
}
?>
