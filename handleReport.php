<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo "false";
    exit();
}

if ($_SESSION['user_type'] != 'admin') {
    echo "false";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "false";
    exit();
}

$reportID = (int)$_POST['reportID'];
$creatorID = (int)$_POST['creatorID'];
$action = $_POST['action'];

if ($action == "dismiss") {
    $result = mysqli_query($conn, "DELETE FROM report WHERE id = $reportID");

    if ($result) {
        echo "true";
    } else {
        echo "false";
    }

    exit();
}

if ($action == "block") {
    $userQuery = "SELECT firstName, lastName, emailAddress, photoFileName FROM users WHERE id = $creatorID";
    $userResult = mysqli_query($conn, $userQuery);
    $user = mysqli_fetch_assoc($userResult);

    if ($user) {
        $firstName = mysqli_real_escape_string($conn, $user['firstName']);
        $lastName = mysqli_real_escape_string($conn, $user['lastName']);
        $emailAddress = mysqli_real_escape_string($conn, $user['emailAddress']);

        $checkBlocked = mysqli_query($conn, "SELECT id FROM blockeduser WHERE emailAddress = '$emailAddress'");

        if (mysqli_num_rows($checkBlocked) == 0) {
            mysqli_query($conn, "INSERT INTO blockeduser (firstName, lastName, emailAddress)
            VALUES ('$firstName', '$lastName', '$emailAddress')");
        }

        if (!empty($user['photoFileName']) && $user['photoFileName'] != "default-account-icon.jpg") {
            $userPhoto = "uploads/" . $user['photoFileName'];
            if (file_exists($userPhoto)) {
                unlink($userPhoto);
            }
        }

        $recipesQuery = mysqli_query($conn, "SELECT photoFileName, videoFilePath FROM recipe WHERE userID = $creatorID");

        while ($recipe = mysqli_fetch_assoc($recipesQuery)) {
            if (!empty($recipe['photoFileName'])) {
                $recipePhoto = "uploads/" . $recipe['photoFileName'];
                if (file_exists($recipePhoto)) {
                    unlink($recipePhoto);
                }
            }

            if (!empty($recipe['videoFilePath'])) {
                $recipeVideo = "uploads/" . $recipe['videoFilePath'];
                if (file_exists($recipeVideo)) {
                    unlink($recipeVideo);
                }
            }
        }

        $deleteUser = mysqli_query($conn, "DELETE FROM users WHERE id = $creatorID");

        if ($deleteUser) {
            echo "true|" . $firstName . " " . $lastName . "|" . $emailAddress;
        } else {
            echo "false";
        }

        exit();
    }

    echo "false";
    exit();
}

echo "false";
exit();
?>
