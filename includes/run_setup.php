<?php
require_once 'db.php';

$sql_file = __DIR__ . '/setup.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found.");
}

$queries = file_get_contents($sql_file);

// Execute multi-query
if (mysqli_multi_query($conn, $queries)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Database setup successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
