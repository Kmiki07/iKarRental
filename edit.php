<?php
include_once('utils/utils.php');
include_once('auth.php');
include_once('storage/carstorage.php');
include_once('storage/userstorage.php');

session_start();

// print_r($_POST);
// print_r($_SESSION);

$user_storage = new UserStorage();
$auth = new Auth($user_storage);


$isauthenticated = $auth->is_authenticated();


// check if user is admin
if (!$auth->authorize(["admin"])) {
    header("Location: index.php");
    exit();
}

// functions
function validate($post, &$data, &$errors)
{
    $data = $post;
    // validate
    if (empty($data['brand'])) {
        $errors['brand'] = "Brand is required";
    }
    if (empty($data['model'])) {
        $errors['model'] = "Model is required";
    }
    if (empty($data['year']) || !is_numeric($data['year'])) {
        $errors['year'] = "Valid year is required";
    }
    if (empty($data['transmission'])) {
        $errors['transmission'] = "Transmission is required";
    }
    if (empty($data['fuel_type'])) {
        $errors['fuel_type'] = "Fuel type is required";
    }
    if (empty($data['passengers']) || !is_numeric($data['passengers'])) {
        $errors['passengers'] = "Valid number of passengers is required";
    }
    if (empty($data['daily_price_huf']) || !is_numeric($data['daily_price_huf'])) {
        $errors['daily_price_huf'] = "Valid daily price is required";
    }
    if (empty($data['image'])) {
        $errors['image'] = "Image URL is required";
    }
    return count($errors) === 0;
}

// main
if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}
$id = $_GET["id"];
$cs = new CarStorage();
$car = $cs->findById($id);
if (!$car) {
    header("Location: index.php");
    exit();
}

$data = [];
$errors = [];
if (count($_POST) > 0) {
    if (validate($_POST, $data, $errors)) {
        $car["brand"] = $data["brand"];
        $car["model"] = $data["model"];
        $car["year"] = $data["year"];
        $car["transmission"] = $data["transmission"];
        $car["fuel_type"] = $data["fuel_type"];
        $car["passengers"] = $data["passengers"];
        $car["daily_price_huf"] = $data["daily_price_huf"];
        $car["image"] = $data["image"];
        $cs->update($id, $car);

        $success = count($errors) === 0;
        //redirect("add.php");
    }
}

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




    <div class="form-body">
        <h1 class="text-center mt-3">Edit</h1>
        <form action="" method="post" novalidate>
            <div class="container">
                <div class="row">
                    <div class="col-12 justify-content-center">
                        <label for="brand">Brand:</label><br>
                        <input type="text" name="brand" id="brand" value="<?= htmlspecialchars($data['brand'] ?? $car['brand'] ?? "") ?>">
                        <?php if (isset($errors['brand'])): ?>
                            <span><?= htmlspecialchars($errors['brand']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="model">Model:</label><br>
                        <input type="text" name="model" id="model" value="<?= htmlspecialchars($data['model'] ?? $car['model'] ?? "") ?>">
                        <?php if (isset($errors['model'])): ?>
                            <span><?= htmlspecialchars($errors['model']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="year">Year:</label><br>
                        <input type="text" name="year" id="year" value="<?= htmlspecialchars($data['year'] ?? $car['year'] ?? "") ?>">
                        <?php if (isset($errors['year'])): ?>
                            <span><?= htmlspecialchars($errors['year']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="transmission">Transmission:</label><br>
                        <input type="text" name="transmission" id="transmission" value="<?= htmlspecialchars($data['transmission'] ?? $car['transmission'] ?? "") ?>">
                        <?php if (isset($errors['transmission'])): ?>
                            <span><?= htmlspecialchars($errors['transmission']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="fuel_type">Fuel Type:</label><br>
                        <input type="text" name="fuel_type" id="fuel_type" value="<?= htmlspecialchars($data['fuel_type'] ?? $car['fuel_type'] ?? "") ?>">
                        <?php if (isset($errors['fuel_type'])): ?>
                            <span><?= htmlspecialchars($errors['fuel_type']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="passengers">Passengers:</label><br>
                        <input type="text" name="passengers" id="passengers" value="<?= htmlspecialchars($data['passengers'] ?? $car['passengers'] ?? "") ?>">
                        <?php if (isset($errors['passengers'])): ?>
                            <span><?= htmlspecialchars($errors['passengers']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="daily_price_huf">Daily Price (HUF):</label><br>
                        <input type="text" name="daily_price_huf" id="daily_price_huf" value="<?= htmlspecialchars($data['daily_price_huf'] ?? $car['daily_price_huf'] ?? "") ?>">
                        <?php if (isset($errors['daily_price_huf'])): ?>
                            <span><?= htmlspecialchars($errors['daily_price_huf']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="image">Image URL:</label><br>
                        <input type="text" name="image" id="image" value="<?= htmlspecialchars($data['image'] ?? $car['image'] ?? "") ?>">
                        <?php if (isset($errors['image'])): ?>
                            <span><?= htmlspecialchars($errors['image']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <button type="submit" class="btn-yellow">Save changes</button>
                    </div>
                </div>
            </div>
        </form>
        <?php if (isset($success) && $success): ?>
            <span id="success-message">Changes saved successfully</span>
        <?php endif; ?>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>