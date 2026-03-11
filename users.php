<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php?error=Please login first");
    exit();
}

if ($_SESSION['user_type'] != 'user') {
    header("Location: login.php?error=You are not authorized to access the user page");
    exit();
}

$userID = $_SESSION['user_id'];

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

$filterCondition = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['category'] != "all") {

    $categoryID = $_POST['category'];

    $filterCondition = "WHERE recipe.categoryID = $categoryID";

}

$recipesQuery = "SELECT recipe.id,
                        recipe.name,
                        recipe.photoFileName,
                        recipe.calories,
                        users.firstName,
                        users.lastName,
                        users.photoFileName AS userPhoto,
                        recipecategory.categoryName,
                        COUNT(DISTINCT likes.id) AS totalLikes,
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

  <!-- Header: Logo + Site Name + Sign Out -->
  <header class="main-header">
    <img src="image/logo.png" alt="Savora Logo" class="logo">
    <h1 class="Savora-name">SAVORA</h1>
     <a href="index.html" class="header-btn">Sign Out</a>
  </header>

  <main class="page-content">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <p><b>Welcome back, <span class="user-name"><?php echo htmlspecialchars($user['firstName']); ?></span></b></p>    
    </div>

    <!-- User Summary -->
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

    <!-- Recipes Section: Title + Calories Filter + Feed -->
    <!-- Recipes Section -->
<section class="feed-wrap">

  <div class="feed-header">
    <h2 class="feed-title">All Recipes</h2>

  <form method="POST">

<div class="calorie-filter">
  <label for="category">Filter by Category</label>

  <div class="filter-controls">
    <select id="category" name="category">

      <option value="all">All Categories</option>

      <?php
      while ($category = mysqli_fetch_assoc($categoryResult)) {
          echo "<option value='".$category['id']."'>".$category['categoryName']."</option>";
      }
      ?>

    </select>

    <button type="submit" class="filter-btn">Apply</button>

  </div>
</div>

</form>

  </div>

  <!-- Recipes Grid -->
 <div class="recipes-feed">

<?php while ($recipe = mysqli_fetch_assoc($recipesResult)) { ?>

<article class="recipe-post">

  <div class="post-head">

    <div class="creator">
      <img src="uploads/<?php echo htmlspecialchars($recipe['userPhoto']); ?>" class="creator-img">
      <span class="creator-name">
        <?php echo htmlspecialchars($recipe['firstName']." ".$recipe['lastName']); ?>
      </span>
    </div>

    <span class="kcal-pill">
      <?php echo htmlspecialchars($recipe['categoryName']); ?>
    </span>

  </div>

  <img src="uploads/<?php echo htmlspecialchars($recipe['photoFileName']); ?>" class="post-img">

  <div class="post-body">

    <h3 class="recipe-name">
      <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="recipe-link">
        <?php echo htmlspecialchars($recipe['name']); ?>
      </a>
    </h3>

  </div>

</article>

<?php } ?>

</div>
</section>

    <!-- Favourite Recipes: Normal Table -->
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
            <tr>
              <td>  <a href="view_recipe.html" class="recipe-link">Chicken Power Bowl</a></td>
              <td><img src="image/Chicken-Power-Bowl.png" alt="Chicken Power Bowl" class="table-img"></td>
              <td><a href="#" class="remove-link">Remove</a></td>
            </tr>

            <tr>
              <td>  <a href="view_recipe.html" class="recipe-link">Avocado Toast</a></td>
              <td><img src="image/Avocado-Toast.png" alt="Avocado Toast" class="table-img"></td>
              <td><a href="#" class="remove-link">Remove</a></td>
            </tr>

            
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <!-- Footer: Logo + Contact Info + Copyright -->
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

</body>
</html>
