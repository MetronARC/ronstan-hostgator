<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone to Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Database connection details
$host = "localhost";
$database = "u558841402_ronstanDB";
$user = "u558841402_metronarc";
$password = "2468g0a7A7B7*";

// Absolute path to the backup directory
$backup_directory = "/home/u558841402/public_html/ronstan/backup/";
$backup_file = $backup_directory . $database . "_" . date("Y-m-d_H-i-s") . ".sql";

// Check if the backup directory exists and is writable
if (!is_dir($backup_directory)) {
    mkdir($backup_directory, 0755, true);
}

if (!is_writable($backup_directory)) {
    die("Backup directory is not writable.");
}

// Create a new .sql file
$file_handle = fopen($backup_file, 'w');

// Connect to the database
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Write a header in the backup file
fwrite($file_handle, "-- Database Backup for $database\n");
fwrite($file_handle, "-- Generated on " . date("Y-m-d H:i:s") . " (Asia/Jakarta timezone)\n\n");
fwrite($file_handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

// Fetch all tables in the database
$tables = $mysqli->query("SHOW TABLES");
while ($table = $tables->fetch_array()) {
    $table_name = $table[0];

    // Get the CREATE TABLE statement for the table structure
    $create_table_result = $mysqli->query("SHOW CREATE TABLE `$table_name`");
    $create_table_row = $create_table_result->fetch_assoc();
    fwrite($file_handle, "\n\n" . $create_table_row['Create Table'] . ";\n\n");

    // Get all data from the table
    $rows = $mysqli->query("SELECT * FROM `$table_name`");
    while ($row = $rows->fetch_assoc()) {
        // Escape and format values for SQL
        $values = array_map([$mysqli, 'real_escape_string'], array_values($row));
        $values = "'" . implode("','", $values) . "'";
        $insert_query = "INSERT INTO `$table_name` VALUES ($values);\n";
        fwrite($file_handle, $insert_query);
    }
}

// Write a footer to re-enable foreign key checks
fwrite($file_handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");

// Close the file and database connection
fclose($file_handle);
$mysqli->close();

echo "Backup created successfully: $backup_file";
?>
