<?php 
// DB credentials
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'spoural');

// Establish PDO database connection
try {
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}

// Establish MySQLi database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spoural";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check MySQLi connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
