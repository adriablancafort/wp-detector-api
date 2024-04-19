<?php

// Open a connection to the database
function open_database_connection() 
{
    $servername = "localhost";
    $username = "id21953222_wpdetector";
    $password = "W3B3%i2@";
    $dbname = "id21953222_wpdetector";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    }

    return $conn;
}

// Close the connection to the database
function close_database_connection($conn) 
{
    if ($conn) {
        $conn->close();
    }
}
?>