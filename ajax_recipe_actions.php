<?php
session_start();
include("db_connection.php");

if(!isset($_SESSION['user_id'])){
    echo "false"; exit();
}

$userID   = $_SESSION['user_id'];
$recipeID = intval($_POST['recipeID']);
$action   = $_POST['action'];

if($action == "favourite"){
    $check = mysqli_query($conn, "SELECT * FROM favourites WHERE userID=$userID AND recipeID=$recipeID");
    if(mysqli_num_rows($check) == 0){
        $result = mysqli_query($conn, "INSERT INTO favourites (userID, recipeID) VALUES ($userID, $recipeID)");
        echo $result ? "true" : "false";
    } else { echo "false"; }

} elseif($action == "like"){
    $check = mysqli_query($conn, "SELECT * FROM likes WHERE userID=$userID AND recipeID=$recipeID");
    if(mysqli_num_rows($check) == 0){
        $result = mysqli_query($conn, "INSERT INTO likes (userID, recipeID) VALUES ($userID, $recipeID)");
        echo $result ? "true" : "false";
    } else { echo "false"; }

} elseif($action == "report"){
    $check = mysqli_query($conn, "SELECT * FROM report WHERE userID=$userID AND recipeID=$recipeID");
    if(mysqli_num_rows($check) == 0){
        $result = mysqli_query($conn, "INSERT INTO report (userID, recipeID) VALUES ($userID, $recipeID)");
        echo $result ? "true" : "false";
    } else { echo "false"; }

} else {
    echo "false";
}
?>