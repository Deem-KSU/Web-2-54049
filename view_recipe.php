<?php

session_start();
include("db_connection.php");

if(!isset($_SESSION['user_id'])){
header("Location: login.php");
exit();
}

$userID = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

if(!isset($_GET['id'])){
echo "Recipe not found";
exit();
}

$recipeID = intval($_GET['id']);

$recipeQuery="
SELECT recipe.*,users.firstName,users.lastName,
users.photoFileName AS userPhoto,
recipecategory.categoryName
FROM recipe
JOIN users ON recipe.userID=users.id
JOIN recipecategory ON recipe.categoryID=recipecategory.id
WHERE recipe.id=$recipeID
";

$recipeResult=mysqli_query($conn,$recipeQuery);
$recipe=mysqli_fetch_assoc($recipeResult);

$ingredients=mysqli_query($conn,"
SELECT * FROM ingredients
WHERE recipeID=$recipeID
");

$instructions=mysqli_query($conn,"
SELECT * FROM instructions
WHERE recipeID=$recipeID
ORDER BY stepOrder
");

$comments=mysqli_query($conn,"
SELECT comment.*,users.firstName,users.photoFileName
FROM comment
JOIN users ON comment.userID=users.id
WHERE recipeID=$recipeID
ORDER BY date DESC
");

$likeCheck=mysqli_query($conn,"
SELECT * FROM likes
WHERE userID=$userID AND recipeID=$recipeID
");

$liked=mysqli_num_rows($likeCheck);

$favCheck=mysqli_query($conn,"
SELECT * FROM favourites
WHERE userID=$userID AND recipeID=$recipeID
");

$favourited=mysqli_num_rows($favCheck);

$reportCheck=mysqli_query($conn,"
SELECT * FROM report
WHERE userID=$userID AND recipeID=$recipeID
");

$reported=mysqli_num_rows($reportCheck);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Savora | View Recipe</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styleN.css">

</head>
<body>

<header class="main-header">
<img src="Image/logo.png" alt="Savora Logo" class="logo">
<h1 class="Savora-name">SAVORA</h1>
<a href="signout.php" class="header-btn">Sign Out</a>
</header>


<div class="recipe-actions">

<?php if($userID != $recipe['userID'] && $userType != 'admin'){ ?>

<form action="report_recipe.php" method="post" style="display:inline;">
<input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
<button <?php if($reported) echo "disabled"; ?>>🚩 Report</button>
</form>

<form action="like_recipe.php" method="post" style="display:inline;">
<input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
<button <?php if($liked) echo "disabled"; ?>>👍 Like</button>
</form>

<form action="favourite_recipe.php" method="post" style="display:inline;">
<input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
<button <?php if($favourited) echo "disabled"; ?>>🤍 Add to favourites</button>
</form>

<?php } ?>

</div>


<main class="recipe-card">


<div class="recipe-left">
<img src="image/<?php echo $recipe['photoFileName']; ?>" alt="Recipe Photo">
</div>


<div class="recipe-right">

<h2><?php echo $recipe['name']; ?></h2>


<div class="creator">
<img src="image/<?php echo $recipe['userPhoto']; ?>" alt="Creator Photo">

<div>
<span class="creator-label">Recipe Creator</span>
<strong>
<?php echo $recipe['firstName']." ".$recipe['lastName']; ?>
</strong>
</div>

</div>


<section>

<h3>Details</h3>

<div class="details-box">

<div class="detail-row">
<span class="detail-label">Category:</span>
<span class="category">
<?php echo $recipe['categoryName']; ?>
</span>
</div>

<div class="detail-row">
<span class="detail-label">Description:</span>

<p class="description">
<?php echo $recipe['description']; ?>
</p>

</div>

</div>

</section>


<section>

<h3>Ingredients</h3>

<ul>

<?php while($ingredient=mysqli_fetch_assoc($ingredients)){ ?>

<li>
<?php echo $ingredient['ingredientName']." ".$ingredient['ingredientQuantity']; ?>
</li>

<?php } ?>

</ul>

</section>


<section>

<h3>Instructions</h3>

<ol>

<?php while($step=mysqli_fetch_assoc($instructions)){ ?>

<li><?php echo $step['step']; ?></li>

<?php } ?>

</ol>

</section>

<section>

<h3>Video</h3>
<br>

<?php if(!empty($recipe['videoFilePath'])){ ?>

<a href="uploads/<?php echo $recipe['videoFilePath']; ?>" 
class="tutorial-btn" target="_blank">
View Tutorial
</a>

<?php } else { ?>

<a class="tutorial-btn"
style="pointer-events:none; opacity:0.5; cursor:not-allowed;">
No Video Tutorial
</a>

<?php } ?>

</section>


<section>

<h3>Comments</h3>

<form action="add_comment.php" method="post">

<div class="comment-box">

<input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">

<input type="text" name="comment" placeholder="Write a comment">

<button type="submit">Add Comment</button>

</div>

</form>


<?php while($comment=mysqli_fetch_assoc($comments)){ ?>

<div class="comment">

<img src="image/<?php echo $comment['photoFileName']; ?>" alt="User Photo">

<div class="comment-content">

<strong><?php echo $comment['firstName']; ?></strong>

<span class="comment-time">
<?php echo $comment['date']; ?>
</span>

<p><?php echo $comment['comment']; ?></p>

</div>

</div>

<?php } ?>


</section>

</div>

</main>


<footer class="main-footer">

<div class="footer-content">

<img src="image/logo.png" alt="Savora Logo" class="footer-logo">

<div class="contact-info">
<p>Email: info@Savora.com</p>
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
