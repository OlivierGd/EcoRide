<?php
session_start();
unset($_SESSION['connecte']);
header('Location: ../public/index.php');
