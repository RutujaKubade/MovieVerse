<?php
$conn = new mysqli("localhost", "root", "", "movie_library");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>