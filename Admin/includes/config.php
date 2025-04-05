<?php 
// DB credentials
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'spoural');

// First, connect without specifying a database to check/create database
try {
    $init_conn = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $init_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists, if not create it
    $result = $init_conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DB_NAME."'");
    if (!$result->fetch()) {
        $init_conn->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."`");
    }
    
    // Close the initial connection
    $init_conn = null;
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; margin: 20px; border-radius: 5px;'>
        Database connection error: " . $e->getMessage() . "<br>
        Please check your database server is running and credentials are correct.
        </div>";
}

// Establish PDO database connection
try {
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; margin: 20px; border-radius: 5px;'>
        Database connection error: " . $e->getMessage() . "<br>
        Please check your database server is running and credentials are correct.
        </div>";
}

// Establish MySQLi database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spoural";

$conn = @mysqli_connect($servername, $username, $password, $dbname);

// Check MySQLi connection
if (!$conn) {
    $error = mysqli_connect_error();
    if (strpos($error, "Unknown database") !== false) {
        // Create the database if it doesn't exist
        $temp_conn = @mysqli_connect($servername, $username, $password);
        if ($temp_conn) {
            mysqli_query($temp_conn, "CREATE DATABASE IF NOT EXISTS `$dbname`");
            mysqli_close($temp_conn);
            // Try to connect again
            $conn = @mysqli_connect($servername, $username, $password, $dbname);
        }
    }
    
    // If still not connected, show error
    if (!$conn) {
        echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; margin: 20px; border-radius: 5px;'>
            MySQLi connection failed: " . mysqli_connect_error() . "<br>
            Please check your database server is running and credentials are correct.
            </div>";
    }
}

// Function to check if required tables exist and create them if needed
if (!function_exists('ensureTablesExist')) {
    function ensureTablesExist($conn) {
        // Check if tables exist
        $tables = [
            'admin' => "CREATE TABLE `admin` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `password` varchar(50) NOT NULL,
                PRIMARY KEY (`id`)
            )",
            'admins' => "CREATE TABLE `admins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                PRIMARY KEY (`id`)
            )",
            'events' => "CREATE TABLE `events` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `date` date DEFAULT NULL,
                PRIMARY KEY (`id`)
            )",
            'ulsc' => "CREATE TABLE `ulsc` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                PRIMARY KEY (`id`)
            )",
            'students' => "CREATE TABLE `students` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `department` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`)
            )",
            'departments' => "CREATE TABLE `departments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                PRIMARY KEY (`id`)
            )"
        ];
        
        foreach ($tables as $table => $create_sql) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (!$result || mysqli_num_rows($result) == 0) {
                mysqli_query($conn, $create_sql);
            }
        }
        
        // Check if admin user exists and create one if not
        $result = mysqli_query($conn, "SELECT * FROM `admin` LIMIT 1");
        if (!$result || mysqli_num_rows($result) == 0) {
            mysqli_query($conn, "INSERT INTO `admin` (`username`, `password`) VALUES ('admin', 'admin')");
        }
    }
}

// Create tables if database is connected
if ($conn) {
    ensureTablesExist($conn);
}
?>
