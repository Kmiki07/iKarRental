<?php
include_once("storage/carstorage.php");
include_once("storage/userstorage.php");
include_once("storage/bookingstorage.php");
include_once("auth.php");
include_once("utils/utils.php");
session_start();

$user_storage = new UserStorage();
$auth = new Auth($user_storage);


// check if user is admin
if (!$auth->authorize(["admin"])) {
    redirect("index.php");
}


if (!isset($_GET["id"])) {
    redirect("index.php");
}

$id = $_GET["id"];
$cs = new CarStorage();
$car = $cs->delete($id);

$bs = new BookingStorage();
$bookings = $bs->findAll(["car_id" => $id]);
foreach ($bookings as $booking) {
    $bs->delete($booking["id"]);
}

redirect("index.php");

//FIND ALL THE BOOKINGS WITH THE ["car_id"] => $id AND DELETE THEM