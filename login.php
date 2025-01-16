<?php
include_once('utils/utils.php');
include_once('storage/userstorage.php');
include('auth.php');

// print_r($_POST);

// functions
function validate($post, &$data, &$errors)
{
  // email, password not empty
  // ...
  if (empty($post['email'])) {
    $errors['email'] = "Email is required";
  }

  if (empty($post['email'])) {
    $errors['email'] = "Email is required";
  } else if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Invalid email format";
  }

  if (empty($post['password'])) {
    $errors['password'] = "Password is required";
  }
  $data = $post;

  return count($errors) === 0;
}

// main
session_start();
$user_storage = new UserStorage();
$auth = new Auth($user_storage);

$isauthenticated = $auth->is_authenticated();
if ($isauthenticated) {
  header('Location: profile.php');
  exit();
}

$data = [];
$errors = [];
if ($_POST) {
  if (validate($_POST, $data, $errors)) {
    $auth_user = $auth->authenticate($data['email'], $data['password']);
    if (!$auth_user) {
      $errors['global'] = "Login error";
    } else {
      $auth->login($auth_user);
      redirect('index.php');
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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

    <h1 class="text-center">Belépés</h1>


    <form action="" method="post" id="loginform" class="auth-form" novalidate>
      <div class="container container-form">
        <div class="row">
          <div class="col-12">
            <label for="email">E-mail cím</label><br>
            <input type="text" name="email" id="email" placeholder="gipsz.jakab@ikarrental.net" value="<?= $_POST['email'] ?? "" ?>">
            <?php if (isset($errors['email'])) : ?>
              <span class="error"><?= $errors['email'] ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <label for="password">Jelszó</label><br>
            <input type="password" name="password" id="password" placeholder="********">
            <?php if (isset($errors['password'])) : ?>
              <span class="error"><?= $errors['password'] ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (isset($errors['global'])) : ?>
          <p><span class="error"><?= $errors['global'] ?></span></p>
        <?php endif; ?>
        <div class="row mt-2">
          <div class="col-12">
            <button id="loginbutton" class="btn-yellow">Belépés</button>
          </div>
        </div>
      </div>
    </form>

  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>