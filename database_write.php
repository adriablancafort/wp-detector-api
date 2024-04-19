<?php
require 'database_connection.php';

function setDataBySlug($table_name, $data) {
	
    $conn = open_database_connection();

    // Check the connection
    if (!$conn->connect_error) {
		// Define columns based on the table value
        $columns = [];
        $placeholders = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $columns[] = $key;
            $placeholders[] = '?';
            $values[] = $value;
        }
        
        $columnNames = implode(',', $columns);
        $placeholderString = implode(',', $placeholders);
        $query = "INSERT INTO $table_name ($columnNames) VALUES ($placeholderString)";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters dynamically
        $types = str_repeat('s', count($values)); // Assuming all values are strings
        $stmt->bind_param($types, ...$values);
        
        $stmt->execute();
        
        // Close the statement and connection
        $stmt->close();
        close_database_connection($conn);

        return true; // Indicate successful insertion
    } else {
        return false; // Indicate failure
    }
}

function updateTimesAnalyzed($table_name, $slug) {
    $conn = open_database_connection();

    // Check the connection
    if (!$conn->connect_error) {
        // Prepare the update query
        if($table_name=='websites') {
            $query = "UPDATE $table_name SET times_analyzed = times_analyzed + 1 WHERE url = ?";
        }
        else {
            $query = "UPDATE $table_name SET times_analyzed = times_analyzed + 1 WHERE slug = ?";
        }
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $slug);
        
        // Execute the update query
        $stmt->execute();

        // Close the statement and connection
        $stmt->close();
        close_database_connection($conn);

        return true; // Indicate successful update
    } else {
        return false; // Indicate failure
    }
}

?>