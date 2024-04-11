<?php
require 'database_connection.php';

function slugExists($table, $slug) {

    $conn = open_database_connection();

    $table_name = $table . 's';
    $slug_name = $table . 'Slug';
    if (!$conn->connect_error) {
        $query = "SELECT COUNT(*) FROM $table_name WHERE $slug_name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $stmt->bind_result($count);

        $stmt->fetch();

        // Close the statement and connection
        close_database_connection($stmt);
        close_database_connection($conn);

        return $count > 0; // Return true if count is greater than 0 (slug exists), otherwise false
    } else {
        return false; // Return false if there's an error or connection issue
    }
}

function getDataBySlug($table, $slug) {
	
    $conn = open_database_connection();

    $table_name = $table . 's';
    $slug_name = $table . 'Slug';
    // Check the connection
    if (!$conn->connect_error) {
		// Define columns based on the table value
        $columns = [];
        if ($table === 'plugin') {
            $columns = [
                'slug',
                'title',
                'icon',
                'banner',
                'author',
                'website',
                'sanatizedWebsite',
                'link',
                'description',
                'reqWpVersion',
                'testedWpVersion',
                'reqPhpVersion',
                'times_analyzed'
            ];
        } elseif ($table === 'theme') {
            $columns = [
                'slug',
                'title',
                'link',
                'author',
                'website',
                'sanatizedWebsite',
                'banner',
                'description',
                'reqWpVersion',
                'testedWpVersion',
                'reqPhpVersion',
                'times_analyzed'
            ];
        }
        $columnNames = implode(',', $columns);
        $query = "SELECT $columnNames FROM $table_name WHERE $slug_name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        
        // Bind variables to the result columns using named parameters
        $result = $stmt->get_result();
        $data = $result->fetch_assoc(); // Fetch associative array directly

        // Close the statement and connection
        close_database_connection($stmt);
        close_database_connection($conn);

        return $data;
    } else {
        return null;
    }
}

?>