<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(false);
    exit();
}

$userID = $_SESSION['user_id'];
$recipeID = $_POST['id'];

$deleteQuery = "DELETE FROM favourites
                WHERE userID = $userID
                AND recipeID = $recipeID";

$result = mysqli_query($conn, $deleteQuery);

echo json_encode($result);
?>