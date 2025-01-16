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

$isauthenticated = $auth->is_authenticated();
if (!$isauthenticated) {
    header('Location: login.php');
    exit();
}

$car_storage = new CarStorage();
$booking_storage = new BookingStorage();
$user_bookings = [];
if ($auth->authorize(["admin"])) {
    $user_bookings = $booking_storage->findAll();
} else {
    $user_bookings = $booking_storage->findAll(['user_email' => $user['email']]);
}

// print_r($user);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iKarRental</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

</head>

<body>

    <nav class="navbar ">
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


    <div class="content-body">

        <!-- <h1 class="text-center mb-5">Profil</h1> -->

        <?php if ($auth->authorize(["admin"])): ?>
            <h2>Adminisztrátor</h2>
        <?php endif; ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-sm-6 text-sm-end text-center">
                    <img src="<?= $user["pfp"] ?>" class="pfp-profile" alt="profilkép">
                    
                </div>
                <div class="col-12 col-sm-6 mt-3">
                    <p class="text-sm-start text-center">Bejelentkezve mint</p>
                    <p class="h1 text-sm-start text-center"><?= $user["fullname"] ?></p>
                    
                </div>
            </div>
        </div>
    

        <!-- <a href="logout.php"><button class="btn-yellow">Kijelentkezés</button></a> -->

        <h2 class="mt-5 mb-3">Foglalásaim</h3>
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($user_bookings as $booking): ?>
                    <?php
                    $car = $car_storage->findById($booking['car_id']);
                    if ($car):
                    ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-12">
                            <div class="card my-2">
                                <?php if ($auth->authorize(["admin"])): ?>
                                    <div class="card-header d-flex justify-content-between flex-wrap">
                                        <p class="card-text mb-0"><?= htmlspecialchars($booking["user_email"]) ?></p>
                                        <a href="deletebooking.php?id=<?= $booking['id'] ?>"><button class="btn-red">Törlés</button></a>
                                    </div>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($car['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($car['brand']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($booking['start_date']) ?> - <?= htmlspecialchars($booking['end_date']) ?></p>
                                    <p class="card-text"><?= htmlspecialchars($car['passengers']) ?> férőhely - <?= htmlspecialchars($car['transmission']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>