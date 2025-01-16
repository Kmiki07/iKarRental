<?php
include('utils/utils.php');
include('storage/userstorage.php');
include('auth.php');
session_start();
$auth = new Auth(new UserStorage());
$auth->logout();
redirect("index.php");