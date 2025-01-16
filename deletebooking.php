<?php
include_once("storage/bookingstorage.php");
include_once("storage/userstorage.php");
include_once("auth.php");
include_once("utils/utils.php");
session_start();

$user_storage = new UserStorage();
$auth = new Auth($user_storage);


// check if user is admin
if (!$auth->authorize(["admin"])) {
    header("Location: index.php");
    exit();
}


if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = $_GET["id"];
$bs = new BookingStorage();
$booking = $bs->delete($id);
header("Location: profile.php");
exit();
