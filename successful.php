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
  header('Location: login.php');
  exit();
}

$booking_storage = new BookingStorage();
$car_storage = new CarStorage();

function validate($get, &$data, &$errors)
{
  $data = $get;
  if (!isset($data["brand"])) {
    $errors["brand"] = "Brand is required";
  }
  if (!isset($data["model"])) {
    $errors["model"] = "Model is required";
  }
  if (!isset($data["startDate"])) {
    $errors["startDate"] = "Start date is required";
  }
  if (!isset($data["endDate"])) {
    $errors["endDate"] = "End date is required";
  }

  return count($errors) === 0;
}

// if session user email is not the booking email THEN REDIRECT TO INDEX (is it safe?)

$data = [];
$errors = [];
if (count($_GET) > 0) {
  if (validate($_GET, $data, $errors)) {
  } else {
    header('Location: index.php');
    exit();
  }
}

// print_r($user);
?>

<!DOCTYPE html>
<html lang="hu">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sikeres foglalás</title>
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

  <!-- <div class="card">
    <div class="card-body">
      <h5 class="card-title">Sikertelen foglalás</h5>
      <p class="card-text">A(z) <b><?= $data["brand"] ?? "err" ?> <?= $data["model"] ?? "err" ?></b> nem elérhető a megadott intervallumra.</p>
      <p class="card-text">Próbálj megadni egy másik intervallumot, vagy keress egy másik járművet.</p>
      <a href="carpage.php?id=<?= $data["carId"] ?? "" ?>"><button>Vissza a jármű oldalára</button></a>
    </div>
  </div> -->






  <img src="media/successful.png" class="response-icon">
  <h1>Sikeres foglalás</h1>
  <p>A(z) <b><?= $data["brand"] ?? "err"?> <?= $data["model"] ?? "err"?></b> sikeresen lefoglalva <?= $data["startDate"] ?? "err"?> - <?= $data["endDate"] ?? "err"?> intervallumra.</p>
  <p>Foglalásod státuszát a profiloldalon követheted nyomon.</p>
  <a href="profile.php"><button class="btn-yellow">Profilom</button></a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>