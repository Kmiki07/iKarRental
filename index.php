<?php
include_once('utils/utils.php');
include_once('storage/userstorage.php');
include_once('storage/bookingstorage.php');
include_once('storage/carstorage.php');
include_once('auth.php');
session_start();
$user_storage = new UserStorage();
$auth = new Auth($user_storage);

$car_storage = new CarStorage();
$cars = $car_storage->findAll();

$isauthenticated = $auth->is_authenticated();
$user = $auth->authenticated_user();

// validate
function validate($get, &$data, &$errors, &$filter_array)
{
  $data = $get;
  // Nem kötelező minden adatot megadni

  // first part: fill up the data with the all the $_GET (maybe empty, faulty -> create the errors for them)
  if (!empty($get["passengers"])) {
    if (!is_numeric($get["passengers"])) {
      $errors["passengers"] = "A férőhelyek számának számnak kell lennie!";
    }
  }
  if (!empty($get["passengers"])) {
    if (!is_numeric($get["passengers"])) {
      $errors["passengers"] = "A férőhelyek számának számnak kell lennie!";
    } else if ($get["passengers"] < 0) {
      $errors["passengers"] = "A férőhelyek számának pozitívnak kell lennie!";
    }
  }
  if (!empty($get["startDate"])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $get['startDate'])) {
      $errors["startDate"] = "A dátumnak a következő formátumban kell lennie: YYYY-MM-DD!";
    } else if (strtotime($get["startDate"]) < strtotime(date("Y-m-d"))) {
      $errors["startDate"] = "A dátumnak a mai dátumnál későbbinek kell lennie!";
    }
  }
  if (!empty($get["endDate"])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $get['endDate'])) {
      $errors["endDate"] = "A dátumnak a következő formátumban kell lennie: YYYY-MM-DD!";
    } else if (strtotime($get["endDate"]) < strtotime(date("Y-m-d"))) {
      $errors["endDate"] = "A dátumnak a mai dátumnál későbbinek kell lennie!";
    }
  }
  // startDate endDate összehasonlítás
  if (!empty($get["startDate"]) && !empty($get["endDate"])) {
    if (strtotime($get["startDate"]) > strtotime($get["endDate"])) {
      $errors["dates"] = "A kezdő dátumnak korábbinak kell lennie mint a befejező dátum!";
    }
  }
  if (!empty($get["startDate"]) && empty($get["endDate"]) || empty($get["startDate"]) && !empty($get["endDate"])) {
    $errors["dates"] = "Csak kezdő és befejező dátummal lehet intervallumra szűrni!";
  }
  //
  if (!empty($get["transmission"])) {
    if ($get["transmission"] !== "default") {
      if ($get["transmission"] !== "automatic" && $get["transmission"] !== "manual") {
        $errors["transmission"] = "A váltó típusa csak \"Automata\" vagy \"Manuális\" lehet!";
      }
    }
  }
  if (!empty($get["minPrice"])) {
    if (!is_numeric($get["minPrice"])) {
      $errors["minPrice"] = "Az árnak számnak kell lennie!";
    } else if ($get["minPrice"] < 0) {
      $errors["minPrice"] = "Az árnak pozitívnak kell lennie!";
    }
  }
  if (!empty($get["maxPrice"])) {
    if (!is_numeric($get["maxPrice"])) {
      $errors["maxPrice"] = "Az árnak számnak kell lennie!";
    } else if ($get["maxPrice"] < 0) {
      $errors["maxPrice"] = "Az árnak pozitívnak kell lennie!";
    }
  }
  // minPrice maxPrice összehasonlítás
  if (!empty($get["minPrice"]) && !empty($get["maxPrice"])) {
    if ($get["minPrice"] > $get["maxPrice"]) {
      $errors["minPrice"] = "A minimális árnak kisebbnek kell lennie mint a maximális ár!";
    }
  }
  //


  // filter array
  // second part: fill up the filter_array from the $_GET (if !isset(errors["key"]), not default, NOT EMPTY)
  if (!empty($data["passengers"]) && !isset($errors["passengers"])) {
    $filter_array["passengers"] = $data["passengers"];
  }
  // double or nothing - startDate endDate
  if (!empty($data["startDate"]) && !isset($errors["startDate"]) && !empty($data["endDate"]) && !isset($errors["endDate"])) {
    $filter_array["startDate"] = $data["startDate"];
    $filter_array["endDate"] = $data["endDate"];
  }
  if (!empty($data["transmission"]) && !isset($errors["transmission"]) && $data["transmission"] !== "default") {
    $filter_array["transmission"] = $data["transmission"];
  }
  if (!empty($data["minPrice"]) && !isset($errors["minPrice"])) {
    $filter_array["minPrice"] = $data["minPrice"];
  }
  if (!empty($data["maxPrice"]) && !isset($errors["maxPrice"])) {
    $filter_array["maxPrice"] = $data["maxPrice"];
  }

  //return count($errors) === 0;
  return true;
}

// Function to filter cars based on the filter array
function filterCars($cars, $filter_array)
{
  return array_filter($cars, function ($car) use ($filter_array) {
    foreach ($filter_array as $key => $value) {
      if ($key === "passengers") {
        if ($car["passengers"] !== $value) {
          return false;
        }
      }
      if ($key === "transmission") {
        if ($value === "automatic") {
          if ($car["transmission"] !== "Automata") {
            return false;
          }
        }
        if ($value === "manual") {
          if ($car["transmission"] !== "Manuális") {
            return false;
          }
        }
      }
      if ($key === "minPrice") {
        if ($car["daily_price_huf"] < $value) {
          return false;
        }
      }
      if ($key === "maxPrice") {
        if ($car["daily_price_huf"] > $value) {
          return false;
        }
      }
    }
    if (!filterDates($car, $filter_array)) {
      return false;
    }
    return true;
  });
}

function filterDates($car, $filter_array)
{
  $booking_storage = new BookingStorage();
  $bookings = $booking_storage->findAll(["car_id" => $car["id"]]);
  if (isset($filter_array["startDate"]) && isset($filter_array["endDate"])) {
    foreach ($bookings as $booking) {
      if (
        strtotime($filter_array["startDate"]) >= strtotime($booking["start_date"]) && strtotime($filter_array["startDate"]) <= strtotime($booking["end_date"]) ||
        strtotime($filter_array["endDate"]) >= strtotime($booking["start_date"]) && strtotime($filter_array["endDate"]) <= strtotime($booking["end_date"]) ||
        strtotime($filter_array["startDate"]) <= strtotime($booking["start_date"]) && strtotime($filter_array["endDate"]) >= strtotime($booking["end_date"]) ||
        strtotime($filter_array["startDate"]) >= strtotime($booking["start_date"]) && strtotime($filter_array["endDate"]) <= strtotime($booking["end_date"])
      ) {
        return false;
      }
    }
  }
  return true;
}

// main
$data = [];
$errors = [];
$filter_array = [];
if (count($_GET) > 0) {
  if (validate($_GET, $data, $errors, $filter_array)) {
    $cars = $car_storage->findAll(); //we could use the $filter_array here but we will do the filtering manually (that's our only option)
    $cars = filterCars($cars, $filter_array); // Filter cars based on the filter array
  }
}

//print_r($errors);
//print_r($user);
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
        <a class="navbar-brand" href="#">iKarRental</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
           <!-- <img src="media/hamburger-menu-icon.png" class="navbar-icon"> -->
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
        <a class="navbar-brand" href="#">iKarRental<?= $auth->authorize(["admin"]) ?  " - ADMIN" : "" ?></a>

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

  <h1 class="title">Kölcsönözz autókat<br>könnyedén!</h1>
  <?php if (!$isauthenticated): ?>
    <div class="register-container">
      <button class="register-button btn-yellow" onclick="window.location.href='register.php'">Regisztráció</button>
    </div>
  <?php endif; ?>

  <div class="filter-body">

    <form action="" method="get" class="filter-form" novalidate>
      <div class="error-messages text-center">
        <?php foreach ($errors as $error): ?>
          <p class="error"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <div class="container">
        <div class="row">
          <div class="col-lg-10 col-12 container-fluid filter-input">
            <div class="row">
              <div class="col-12 col-xl-6 text-center">
                <!-- <div class="col-12 col-md-4 d-flex align-items-center"> -->
                  <button type="button" id="button-minus" class="btn-small">-</button>
                  <input type="number" name="passengers" placeholder="0" min="0" id="passenger-input" class="mx-2" <?= !empty($data["passengers"]) ? "value=\"" . $data["passengers"] . "\"" : "" ?>>
                  <button type="button" id="button-plus" class="btn-small">+</button>
                  <span>&nbsp;&nbsp;&nbsp;férőhely</span>
                <!-- </div> -->
              </div>
              <div class="col-12 col-md-6 col-xl-3 text-center"><input name="startDate" type="date" <?= !empty($data["startDate"]) ? "value=\"" . $data["startDate"] . "\"" : "" ?>>-tól</div>
              <div class="col-12 col-md-6 col-xl-3 text-center"><input name="endDate" type="date" <?= !empty($data["endDate"]) ? "value=\"" . $data["endDate"] . "\"" : "" ?>>-ig</div>
            </div>
            <div class="row justify-content-end">
              <div class="col-12 col-lg-3 text-center text-lg-end">
                <select name="transmission">
                  <option value="default" <?= empty($data["transmission"]) || $data["transmission"] === "default" ? "selected" : "" ?>>Váltó típusa</option>
                  <option value="automatic" <?= !empty($data["transmission"]) && $data["transmission"] === "automatic" ? "selected" : "" ?>>Automata</option>
                  <option value="manual" <?= !empty($data["transmission"]) && $data["transmission"] === "manual" ? "selected" : "" ?>>Manuális</option>
                </select>
              </div>
              <div class="col-12 col-lg-9 col-xl-7 text-center text-lg-end"><input name="minPrice" type="number" <?= !empty($data["minPrice"]) ? "value=\"" . $data["minPrice"] . "\"" : "" ?>> - <input name="maxPrice" type="number" <?= !empty($data["maxPrice"]) ? "value=\"" . $data["maxPrice"] . "\"" : "" ?>> Ft</div>
            </div>
          </div>

          <div class="col-lg-2 col-12 filter-submit">
            <button type="submit" class="btn-yellow">Szűrés</button>
            <button type="button" class="btn-red" onclick="window.location.href='index.php'">Törlés</button>
          </div>
        </div>

      </div>

    </form>

  </div>

  <br>





  <!-- CARDS -->

  <div class="container-fluid listed">
    <div class="row">
      <?php foreach ($cars as $car): ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12">
          <div class="card my-2">
            <img src="<?= htmlspecialchars($car['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($car['brand']) ?>">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h5>
              <p class="card-text"><?= htmlspecialchars($car['year']) ?></p>
              <p class="card-text"><?= htmlspecialchars($car['passengers']) ?> férőhely - <?= htmlspecialchars($car['transmission']) ?></p>
              <p class="card-text"><?= htmlspecialchars($car['daily_price_huf']) ?> HUF/nap</p>
              <a href="carpage.php?id=<?= htmlspecialchars($car["id"]) ?>"><button class="btn-book">Foglalás</button></a>
              <?php if ($isauthenticated && in_array('admin', $user['roles'])): ?>
                <a href="edit.php?id=<?= htmlspecialchars($car['id']) ?>"><button class="btn-gray">Edit</button></a>
                <a href="deletecar.php?id=<?= htmlspecialchars($car['id']) ?>"><button class="btn-red">Delete</button></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- CARDS -->


  <script src="index.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>