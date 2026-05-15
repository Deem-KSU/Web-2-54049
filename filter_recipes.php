<?php
include('db_connection.php');

$category = $_GET['category'] ?? 'all';

$filterCondition = "";

if ($category != "all") {
    $categoryID = intval($category);
    $filterCondition = "WHERE recipe.categoryID = $categoryID";
}

$query = "SELECT recipe.id,
                 recipe.name,
                 recipe.photoFileName,
                 users.firstName,
                 users.lastName,
                 users.photoFileName AS userPhoto,
                 recipecategory.categoryName,
                 COUNT(DISTINCT likes.userID) AS totalLikes
          FROM recipe
          JOIN users ON recipe.userID = users.id
          JOIN recipecategory ON recipe.categoryID = recipecategory.id
          LEFT JOIN likes ON recipe.id = likes.recipeID
          $filterCondition
          GROUP BY recipe.id
          ORDER BY recipe.id DESC";

$result = mysqli_query($conn, $query);

$recipes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $recipes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($recipes);
?>