<?php
// Checking this variable will let us prevent pages from being called directly
define('DIRECT', true);
include 'config.php';

// Create the main database object
$host = constant('DB_HOST');
$db = constant('DB_NAME');
$user = constant('DB_USER');
$pass = constant('DB_PASSWORD');
$charset = constant('DB_CHARSET');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$database = new PDO($dsn, $user, $pass, $opt);

// Start the session and set us to logged out by default
session_start();
if(empty($_SESSION['authenticated'])) {
    $_SESSION['authenticated'] = 0;
}

// Create the redirect variable to be used when going to the login page
$redirect = htmlspecialchars(ltrim($_SERVER['PHP_SELF'], '/'), ENT_QUOTES, "utf-8");

// Set protocol for header strings
if ($_SERVER['SERVER_PROTOCOL'] == 'HTTPS') {
    $http = 'https://';
}
else {
    $http = 'http://';
}


?>