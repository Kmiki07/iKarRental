<?php
include_once('utils/utils.php');
include_once('storage/userstorage.php');
include_once('storage/carstorage.php');
include_once('storage/bookingstorage.php');
include_once('auth.php');
session_start();
$user_storage = new UserStorage();
$auth = new Auth($user_storage);

header("Content-Type: application/json");

$isauthenticated = $auth->is_authenticated();
$user = $auth->authenticated_user();

if (!$isauthenticated) {
    echo json_encode(["success" => false, "message" => "User not authenticated"]);
    exit();
}

if (!isset($_POST["id"])) {
    echo json_encode(["success" => false, "message" => "Car ID is required"]);
    exit();
}

$id = $_POST["id"];
$car_storage = new CarStorage();
$car = $car_storage->findById($id);
if (!$car) {
    echo json_encode(["success" => false, "message" => "Car not found"]);
    exit();
}

// validate and then -> successful or unsuccessful
function validate($post, &$data, &$errors)
{
    $data = $post;
    // validate
    if (!isset($post['startDate']) || !strtotime($post['startDate'])) {
        $errors['startDate'] = "Start date is required";
    }

    if (!isset($post['endDate']) || !strtotime($post['endDate'])) {
        $errors['endDate'] = "End date is required";
    }

    if (strtotime($post['startDate']) > strtotime($post['endDate'])) {
        $errors['endDate'] = "End date must be after start date";
    }
    if (!isCarAvailable($post['id'], $post['startDate'], $post['endDate'])) {
        $errors['car'] = "Car is not available in this period";
    }

    // we don't need to validate the car id, because it is already validated when coming here

    return count($errors) === 0;
}

function isCarAvailable($carId, $startDate, $endDate)
{
    $bs = new BookingStorage();
    $bookings = $bs->findAll(["car_id" => $carId]);
    $today = strtotime(date('Y-m-d'));
    foreach ($bookings as $booking) {
        // Ignore bookings that have already ended
        if (strtotime($booking["end_date"]) < $today) {
            continue;
        }

        if (
            strtotime($startDate) >= strtotime($booking["start_date"]) && strtotime($startDate) <= strtotime($booking["end_date"]) ||
            strtotime($endDate) >= strtotime($booking["start_date"]) && strtotime($endDate) <= strtotime($booking["end_date"]) ||
            strtotime($startDate) <= strtotime($booking["start_date"]) && strtotime($endDate) >= strtotime($booking["end_date"]) ||
            strtotime($startDate) >= strtotime($booking["start_date"]) && strtotime($endDate) <= strtotime($booking["end_date"]) ||
            strtotime($startDate) <= $today
        ) {
            return false;
        }
    }
    return true;
}

// main
$data = [];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validate($_POST, $data, $errors)) {
        $bs = new BookingStorage();
        $bs->add([
            "car_id" => $id,
            "user_email" => $user["email"],
            "start_date" => $data["startDate"],
            "end_date" => $data["endDate"],
        ]);

        $car = $car_storage->findById($data["id"]);
        $html = "<div class='card'>
                    <div class='card-body'>
                        <h5 class='card-title'>Sikeres foglalás<img src='media/successful.png' class='response-icon ms-2'></h5>
                        <p class='card-text'>A(z) <b>{$car['brand']} {$car['model']}</b> sikeresen lefoglalva {$data['startDate']} - {$data['endDate']} intervallumra.</p>
                        <p class='card-text'><a href='profile.php'><button class='btn-yellow'>Profilom</button></a></p>
                    </div>
                </div>";

        echo json_encode(["success" => true, "html" => $html]);
    } else {
        $html = "<div class='card'>
                    <div class='card-body'>
                        <h5 class='card-title'>Sikertelen foglalás<img src='media/unsuccessful.png' class='response-icon ms-2'></h5>
                        <p class='card-text'>A(z) <b>{$car['brand']} {$car['model']}</b> nem elérhető a megadott intervallumra.</p>
                        <p class='card-text'>Próbálj megadni egy másik intervallumot, vagy keress egy másik járművet.</p>
                    </div>
                </div>";
        //$html = $errors;
        echo json_encode(["success" => false, "html" => $html]);
    }
    exit();
}
