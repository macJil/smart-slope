<?php
// auth.php - Include this at the TOP of: index.php, import.php, locations.php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}