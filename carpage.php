<?php
include_once('utils/utils.php');
include_once('storage/userstorage.php');
include_once('storage/carstorage.php');
include_once('storage/bookingstorage.php');
include_once('auth.php');
session_start();
$user_storage = new UserStorage();
$auth = new Auth($user_storage);

$isauthenticated = $auth->is_authenticated();
$user = $auth->authenticated_user();

if (!$isauthenticated) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}
$id = $_GET["id"];
$car_storage = new CarStorage();
$car = $car_storage->findById($id);
if (!$car) {
    header("Location: index.php");
    exit();
}
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

// functions
function validate($post, &$data, &$errors)
{
    $data = $post;
    // validate
    if (!isset($_GET['startDate']) || !strtotime($_GET['startDate'])) {
        $errors['startDate'] = "Start date is required";
    }

    if (!isset($_GET['endDate']) || !strtotime($_GET['endDate'])) {
        $errors['endDate'] = "End date is required";
    }

    if (strtotime($_GET['startDate']) > strtotime($_GET['endDate'])) {
        $errors['endDate'] = "End date must be after start date";
    }
    if (!isCarAvailable($_GET['id'], $_GET['startDate'], $_GET['endDate'])) {
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
            "start_date" => $startDate,
            "end_date" => $endDate,
        ]);
        redirect('successful.php?brand=' . $car_storage->findById($id)["brand"] . '&model=' . $car_storage->findById($id)["model"] . '&startDate=' . $startDate . '&endDate=' . $endDate);
        //Should we send the raw data or the booking id?
        //(sending booking id is not secure, you could brute force all of them)
        //sending raw data: just printing it out
    } else {
        redirect('unsuccessful.php?carId=' . $id);
    }
}

// print_r($user);
// print_r($_POST);
// print($user["email"]);
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iKarRental</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

</head>

<body>

    <?php if (!$isauthenticated): ?>
        <nav class="navbar navbar-expand-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">iKarRental</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                    <!-- <img src="media/profile pictures/default.png"> -->
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav mb-2 mb-lg-0 d-flex justify-content-end">
                        <li class="nav-item mx-2 mt-3 my-lg-0 d-flex justify-content-end">
                            <a href="login.php"><button class="btn-plain">Bejelentkezés</button></a>
                        </li>
                        <li class="nav-item mx-2 mt-3 my-lg-0 d-flex justify-content-end">
                            <a href="register.php"><button class="btn-yellow">Regisztráció</button></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <?php else: ?>
        <nav class="navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">iKarRental<?= $auth->authorize(["admin"]) ?  " - ADMIN" : "" ?></a>

                <img class="pfp-navbar" src="<?= $user["pfp"] ?? "media/profile pictures/default.png" ?>" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav mb-2 mb-lg-0 d-flex justify-content-end">
                        <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                            <a class="my-1" href="profile.php"><button class="btn-plain">Profil</button></a>
                        </li>
                        <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                            <a class="my-1" href="logout.php"><button class="btn-plain">Kijelentkezés</button></a>
                        </li>
                        <?php if ($auth->authorize(["admin"])): ?>
                            <li class="nav-item mx-2 mt-1 my-lg-0 d-flex justify-content-end">
                                <a class="my-1" href="add.php"><button class="btn-plain">Add car</button></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </nav>
    <?php endif; ?>

    <div class="carpage-body">

        <div class="container car-details mt-5">
            <h1 class="text-end h1-car"><?= $car["brand"] ?><b><?= $car["model"] ?></b></h1>
            <div class="row">

                <div class="col-12 col-md-6 mt-3">
                    <img class="img-fluid display-img" src="<?= $car["image"] ?>" alt="<?= $car["brand"] . " " . $car["model"] ?>">
                </div>

                <div class="col-12 col-md-6 mt-3">
                    <div class="container-fluid h-100">
                        <div class="row details-card">
                            <div class="col-12 d-flex flex-column h-100 justify-content-end mt-3 pt-1 pb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">Üzemanyag: <?= $car["fuel_type"] ?></p>
                                        <p class="mb-0">Váltó: <?= $car["transmission"] ?></p>
                                    </div>
                                    <div class="text-end">
                                        <p class="mb-1">Gyártási év: <?= $car["year"] ?></p>
                                        <p class="mb-0">Férőhelyek száma: <?= $car["passengers"] ?></p>
                                    </div>
                                </div>
                                <div class="text-center mt-auto mb-2">
                                    <span class="h1 fw-bold"><?= $car["daily_price_huf"] ?> Ft</span>
                                    <span class="mb-0 day">/nap</span>
                                </div>
                            </div>
                        </div>
                        <form action="" method="post" class="booking-buttons pt-3">
                            <div class="row h-100">
                                <div class="col-8 ps-0">
                                    <button type="button" class="btn-blue w-100 h-100" onclick="window.location.href='calendar.php?id=<?= $id ?>'">Dátum kiválasztása</button>
                                </div>
                                <div class="col-4 pe-0">
                                    <button type="submit" id="submitbutton" class="btn-yellow w-100 h-100">Lefoglalom</button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <?php if ($startDate && $endDate): ?>
                <p class="text-center mt-4"><?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?></p>
            <?php endif; ?>
            <div id="bookingresponse">

            </div>
        </div>

    </div>

    <script src="book.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>