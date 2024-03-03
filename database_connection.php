<?php

$servername = "localhost";
$username = "id21953222_wpdetector";
$password = "W3B3%i2@";
$dbname = "id21953222_wpdetector";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to select all elements from the "websites" table
$sql = "SELECT url, wp, themes, plugins, times_analyzed, last_analyzed FROM websites";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        echo "url: " . $row["url"]. "\n";
        echo "wp: " . $row["wp"]. "\n";
        echo "themes: " . $row["themes"]. "\n";
        echo "plugins: " . $row["plugins"]. "\n";
        echo "times_analyzed: " . $row["times_analyzed"]. "\n";
        echo "last_analyzed: " . $row["last_analyzed"]. "\n";
    }
} else {
    echo "0 results";
}

$conn->close();