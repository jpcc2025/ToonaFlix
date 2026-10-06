<?php
require __DIR__ . '/config.php';
redirect(empty($_SESSION['username']) ? 'login.php' : 'home.php');
