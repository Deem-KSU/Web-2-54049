<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('db_connection.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php?error=Please login first");
    exit();
}

if ($_SESSION['user_type'] != 'user') {
    header("Location: login.php?error=You are not authorized to access the user page");
    exit();
}

$userID = intval($_SESSION['user_id']);
$selectedCategory = "all";
$filterCondition = "";

$userQuery = "SELECT firstName, lastName, emailAddress, photoFileName
              FROM users
              WHERE id = $userID";
$userResult = mysqli_query($conn, $userQuery);

if (!$userResult) {
    die("User Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($userResult) == 0) {
    die("No user found with this ID");
}

$user = mysqli_fetch_assoc($userResult);

$recipesCountQuery = "SELECT COUNT(*) AS totalRecipes
                      FROM recipe
                      WHERE userID = $userID";
$recipesCountResult = mysqli_query($conn, $recipesCountQuery);

if (!$recipesCountResult) {
    die("Recipes Count Query Error: " . mysqli_error($conn));
}

$recipesCountRow = mysqli_fetch_assoc($recipesCountResult);
$totalRecipes = $recipesCountRow['totalRecipes'];

$totalLikesQuery = "SELECT COUNT(likes.recipeID) AS totalLikes
                    FROM recipe
                    LEFT JOIN likes ON recipe.id = likes.recipeID
                    WHERE recipe.userID = $userID";
$totalLikesResult = mysqli_query($conn, $totalLikesQuery);

if (!$totalLikesResult) {
    die("Total Likes Query Error: " . mysqli_error($conn));
}

$totalLikesRow = mysqli_fetch_assoc($totalLikesResult);
$totalLikes = $totalLikesRow['totalLikes'];

$categoryQuery = "SELECT id, categoryName FROM recipecategory";
$categoryResult = mysqli_query($conn, $categoryQuery);

if (!$categoryResult) {
    die("Category Query Error: " . mysqli_error($conn));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category'])) {
    $selectedCategory = $_POST['category'];

    if ($selectedCategory != "all") {
        $categoryID = intval($selectedCategory);
        $filterCondition = "WHERE recipe.categoryID = $categoryID";
    }
}

$recipesQuery = "SELECT recipe.id,
                        recipe.name,
                        recipe.photoFileName,
                        users.firstName,
                        users.lastName,
                        users.photoFileName AS userPhoto,
                        recipecategory.categoryName,
                        COUNT(DISTINCT likes.userID) AS totalLikes,
                        COUNT(DISTINCT comment.id) AS totalComments
                 FROM recipe
                 JOIN users ON recipe.userID = users.id
                 JOIN recipecategory ON recipe.categoryID = recipecategory.id
                 LEFT JOIN likes ON recipe.id = likes.recipeID
                 LEFT JOIN comment ON recipe.id = comment.recipeID
                 $filterCondition
                 GROUP BY recipe.id
                 ORDER BY recipe.id DESC";
$recipesResult = mysqli_query($conn, $recipesQuery);

if (!$recipesResult) {
    die("Recipes Query Error: " . mysqli_error($conn));
}

$favQuery = "SELECT recipe.id,
                    recipe.name,
                    recipe.photoFileName
             FROM favourites
             JOIN recipe ON favourites.recipeID = recipe.id
             WHERE favourites.userID = $userID";
$favResult = mysqli_query($conn, $favQuery);

if (!$favResult) {
    die("Favourite Query Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>SAVORA | User</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styleD.css">
</head>
<body>

  <header class="main-header">
    <img src="image/logo.png" alt="Savora Logo" class="logo">
    <h1 class="Savora-name">SAVORA</h1>
    <a href="signout.php" class="header-btn">Sign Out</a>
  </header>

  <main class="page-content">

    <div class="welcome-banner">
      <p><b>Welcome back, <span class="user-name"><?php echo htmlspecialchars($user['firstName']); ?></span></b></p>
    </div>

    <section class="user-summary">
      <div class="user-top">
        <div class="user-info">
          <h3>My Information</h3>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($user['firstName'] . " " . $user['lastName']); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($user['emailAddress']); ?></p>
        </div>

        <img src="uploads/<?php echo htmlspecialchars($user['photoFileName']); ?>" alt="User photo" class="user-photo">
      </div>

      <div class="user-bottom">
        <div class="stats">
          <div class="stat">
            <span class="num"><?php echo $totalRecipes; ?></span>
            <span>Total Recipes</span>
          </div>

          <div class="stat">
            <span class="num"><?php echo $totalLikes; ?></span>
            <span>Total Likes</span>
          </div>
        </div>

        <a href="my_recipes.php" class="my-recipes-link">My Recipes →</a>
      </div>
    </section>

    <section class="feed-wrap">
      <div class="feed-header">
        <h2 class="feed-title">All Recipes</h2>

        <form method="POST">
          <div class="calorie-filter">
            <label for="category">Filter by Category</label>

            <div class="filter-controls">
<select id="category" name="category"> 
    <option value="all" <?php echo ($selectedCategory == "all") ? "selected" : ""; ?>>All Categories</option>

                <?php while ($category = mysqli_fetch_assoc($categoryResult)) { ?>
                  <option value="<?php echo $category['id']; ?>" <?php echo ($selectedCategory == $category['id']) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($category['categoryName']); ?>
                  </option>
                <?php } ?>
              </select>

            </div>
          </div>
        </form>
      </div>

<div class="recipes-feed" id="recipes-feed">        <?php if (mysqli_num_rows($recipesResult) > 0) { ?>
          <?php while ($recipe = mysqli_fetch_assoc($recipesResult)) { ?>
            <article class="recipe-post">
              <div class="post-head">
                <div class="creator">
                  <img src="uploads/<?php echo htmlspecialchars($recipe['userPhoto']); ?>" class="creator-img" alt="Creator photo">
                  <span class="creator-name">
                    <?php echo htmlspecialchars($recipe['firstName'] . " " . $recipe['lastName']); ?>
                  </span>
                </div>

                <span class="kcal-pill">
                  <?php echo htmlspecialchars($recipe['categoryName']); ?>
                </span>
              </div>

              <img src="uploads/<?php echo htmlspecialchars($recipe['photoFileName']); ?>" class="post-img" alt="Recipe photo">

              <div class="post-body">
                <h3 class="recipe-name">
                  <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="recipe-link">
                    <?php echo htmlspecialchars($recipe['name']); ?>
                  </a>
                </h3>

                <p class="recipe-meta">❤️ <?php echo $recipe['totalLikes']; ?> Likes</p>
              </div>
            </article>
          <?php } ?>
        <?php } else { ?>
          <p class="no-recipes">No recipes found.</p>
        <?php } ?>
      </div>
    </section>

    <section class="fav-section">
      <h2 class="fav-title">My Favourite Recipes ♥</h2>

      <div class="fav-table-wrap">
        <table class="fav-table">
          <thead>
            <tr>
              <th>Recipe Name</th>
              <th>Recipe Photo</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($favResult) > 0) { ?>
              <?php while ($fav = mysqli_fetch_assoc($favResult)) { ?>
                <tr>
                  <td>
                    <a href="view_recipe.php?id=<?php echo $fav['id']; ?>" class="recipe-link">
                      <?php echo htmlspecialchars($fav['name']); ?>
                    </a>
                  </td>

                  <td>
                    <img src="uploads/<?php echo htmlspecialchars($fav['photoFileName']); ?>" class="table-img" alt="Recipe photo">
                  </td>

                  <td>
                   <a href="remove_favourite.php?id=<?php echo $fav['id']; ?>" 
                  class="remove-link"
                  data-id="<?php echo $fav['id']; ?>">
                  Remove
                 </a>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="3" class="no-fav">No favourite recipes yet.</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <footer class="main-footer">
    <div class="footer-content">
      <img src="image/logo.png" alt="Savora Logo" class="footer-logo">

      <div class="contact-info">
        <p>Email: info@savora.com</p>
        <p>Phone: +966 50 000 0000</p>
        <p>Riyadh, Saudi Arabia</p>
      </div>
    </div>

    <div class="footer-bottom">
      &copy; 2026 Savora. All rights reserved.
    </div>
  </footer>

    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {

    $('#category').change(function () {
        let category = $(this).val();

        $.ajax({
            url: 'filter_recipes.php',
            type: 'GET',
            data: { category: category },
            dataType: 'json',

            success: function (recipes) {
                let output = '';

                if (recipes.length > 0) {
                    recipes.forEach(function (recipe) {
                        output += `
                        <article class="recipe-post">
                            <div class="post-head">
                                <div class="creator">
                                    <img src="uploads/${recipe.userPhoto}" class="creator-img">
                                    <span class="creator-name">${recipe.firstName} ${recipe.lastName}</span>
                                </div>
                                <span class="kcal-pill">${recipe.categoryName}</span>
                            </div>

                            <img src="uploads/${recipe.photoFileName}" class="post-img">

                            <div class="post-body">
                                <h3 class="recipe-name">
                                    <a href="view_recipe.php?id=${recipe.id}" class="recipe-link">${recipe.name}</a>
                                </h3>
                                <p class="recipe-meta">❤️ ${recipe.totalLikes} Likes</p>
                            </div>
                        </article>`;
                    });
                } else {
                    output = '<p class="no-recipes">No recipes found.</p>';
                }

                $('#recipes-feed').html(output);
            }
        });
    });

    $('.remove-link').click(function (e) {
        e.preventDefault();

        let link = $(this);
        let recipeID = link.data('id');

        $.ajax({
            url: 'remove_favourite.php',
            type: 'POST',
            data: { id: recipeID },
            dataType: 'json',

            success: function (response) {
                if (response == true) {
                    link.closest('tr').remove();
                }
            }
        });
    });

});

</script>
    
    
    
</body>
</html>