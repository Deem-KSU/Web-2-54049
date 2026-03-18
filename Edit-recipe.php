<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "db_connection.php";

$error = mysqli_connect_error();

if ($error != null) {
    $output = "<p>Unable to connect to database</p>" . $error;
    exit($output);
}

$recipe_id = $_POST['recipe_id'] ?? $_GET['id'];

$recipe_sql = "SELECT * FROM recipe WHERE id = '$recipe_id'";
$recipe_result = mysqli_query($conn, $recipe_sql);
$recipe = mysqli_fetch_assoc($recipe_result);

// to prevent an unathorizd users to edit recipe
if ($recipe['userID'] != $_SESSION['user_id']) {
    header("Location: my_recipes.php");
    exit();
}
$ingredients_sql = "SELECT * FROM ingredients WHERE recipeID = '$recipe_id'";
$ingredients_result = mysqli_query($conn, $ingredients_sql);

$steps_sql = "SELECT * FROM instructions WHERE recipeID = '$recipe_id' ORDER BY stepOrder";
$steps_result = mysqli_query($conn, $steps_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $video_url = $_POST['video_url'];

    $ingredient_names = $_POST['ingredient_name'];
    $ingredient_qtys = $_POST['ingredient_qty'];

    $steps = $_POST['steps'];

    $photo_name = $_FILES['photo']['name'];
    $video_file_name = $_FILES['video_file']['name'];
    $remove_video = isset($_POST['remove_video']);
    $video = "";

  if ($remove_video) {

    if (!empty($recipe['videoFilePath']) && !filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)) {
        if (file_exists("uploads/" . $recipe['videoFilePath'])) {
            unlink("uploads/" . $recipe['videoFilePath']);
        }
    }

    $video = ""; 

} else if (!empty($video_file_name)) {

    if (!empty($recipe['videoFilePath']) && !filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)) {
        if (file_exists("uploads/" . $recipe['videoFilePath'])) {
            unlink("uploads/" . $recipe['videoFilePath']);
        }
    }
   $video_tmp = $_FILES['video_file']['tmp_name'];
    move_uploaded_file($video_tmp, "uploads/" . $video_file_name);
    $video = $video_file_name;

} else  {

    $video = $video_url;

}
    
    if (!empty($photo_name)) {

    // delet the old one
    if (!empty($recipe['photoFileName']) && file_exists("uploads/" . $recipe['photoFileName'])) {
        unlink("uploads/" . $recipe['photoFileName']);
    }

    $photo_tmp = $_FILES['photo']['tmp_name'];
    move_uploaded_file($photo_tmp, "uploads/" . $photo_name);
}

    $category_sql = "SELECT id FROM recipecategory WHERE categoryName = '$category'";
    $category_result = mysqli_query($conn, $category_sql);

    $category_row = mysqli_fetch_assoc($category_result);
    $category_id = $category_row['id'];
    
   if (empty($photo_name)) {
    $photo_name = $recipe['photoFileName'];
}

if (empty($video_file_name) && !$remove_video) {
    $video = $video_url;
}

$sql = "UPDATE recipe
        SET categoryID='$category_id',
            name='$name',
            description='$description',
            photoFileName='$photo_name',
            videoFilePath='$video'
        WHERE id='$recipe_id'";

$result = mysqli_query($conn, $sql);

mysqli_query($conn, "DELETE FROM ingredients WHERE recipeID='$recipe_id'");
mysqli_query($conn, "DELETE FROM instructions WHERE recipeID='$recipe_id'");

for ($i = 0; $i < count($ingredient_names); $i++) {
        $ingredient_name = $ingredient_names[$i];
        $ingredient_qty = $ingredient_qtys[$i];

        $ingredient_sql = "INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity)
                           VALUES ('$recipe_id', '$ingredient_name', '$ingredient_qty')";

        mysqli_query($conn, $ingredient_sql);
    }
    
    for ($i = 0; $i < count($steps); $i++) {

    $step_text = $steps[$i];
    $step_order = $i + 1;

    $step_sql = "INSERT INTO instructions (recipeID, step, stepOrder)
                 VALUES ('$recipe_id', '$step_text', '$step_order')";

    mysqli_query($conn, $step_sql);
    
  }
  
  header("Location: my_recipes.php");
exit();

}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>SAVORA | Edit Recipe</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styleS.css">
</head>
<body>

  <header class="main-header">
    <img src="image/logo.png" alt="Savora Logo" class="logo">

    <h1 class="Savora-name">SAVORA</h1>

     <a href="signout.php" class="header-btn">Sign Out</a>
  </header>

<main class="recipe-page">

  <section class="recipe-card">
    <h1 class="form-title">Edit Recipe</h1>
<form action="edit_recipe.php" method="POST" enctype="multipart/form-data" class="recipe-form">

  <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
      <div class="form-group">
        <label>Recipe Name</label>
        <input type="text" name="name" value="<?php echo $recipe['name']; ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
       <select name="category" required>
  <option value="" disabled>Select category</option>
  <?php
    $sql = "SELECT * FROM recipecategory";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['id'] == $recipe['categoryID']) {
        echo "<option selected>" . $row['categoryName'] . "</option>";
      } else {
        echo "<option>" . $row['categoryName'] . "</option>";
      }
    }
  ?>
</select>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" required><?php echo $recipe['description']; ?></textarea>
        </div>
      </div>
     
        <div class="form-group">
  <label>Recipe Photo</label>
  <div class="photo-edit-row">
    <input type="file" name="photo" accept="image/*">
    <img src="uploads/<?php echo $recipe['photoFileName']; ?>" alt="Current recipe image" class="current-img">
  </div>
</div>
		
  <div class="form-group">
  <label>Ingredients</label>

<div id="ingredients-list">
  <?php
    $count = 1;
    while ($ingredient = mysqli_fetch_assoc($ingredients_result)) {
  ?>
    <div class="ingredient-row">
      <span class="ingredient-num">Ingredient <?php echo $count; ?>:</span>

      <div class="ingredient-inputs">
        <input type="text" name="ingredient_name[]" class="ing-name" value="<?php echo $ingredient['ingredientName']; ?>" required>
        <input type="text" name="ingredient_qty[]" class="ing-qty" value="<?php echo $ingredient['ingredientQuantity']; ?>" required>
      </div>
    </div>
  <?php
      $count++;
    }
  ?>
</div>

  <button type="button" id="add-ingredient">+ Add Ingredient</button>
</div>
		
    <div class="form-group">
  <label>Instructions</label>

 <div id="steps-list">
  <?php
    $counts = 1;
    while ($step = mysqli_fetch_assoc($steps_result)) {
  ?>
    <div class="step-row">
      <span class="step-num">Step <?php echo $counts; ?>:</span>
      <input type="text" name="steps[]" class="step-input" value="<?php echo $step['step']; ?>" required>
    </div>
  <?php
      $counts++;
    }
  ?>
</div>

  <button type="button" id="add-step">+ Add Step</button>
</div>
      
<div class="form-group">
  <label>Upload Video or URL (optional)</label>

  <div class="video-edit-row" style="display:flex; align-items:flex-start; gap:15px;">
    
<div class="video-inputs" style="flex:1;">
  <input type="file" name="video_file" class="vid-file" accept="video/*">
  <input type="url" name="video_url" value="<?php if (filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)) echo $recipe['videoFilePath']; ?>">

  <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
  <input type="checkbox" name="remove_video" value="1" style="width:auto;">
  Remove current video
</label>
</div>
      
    <?php if (!empty($recipe['videoFilePath']) && !filter_var($recipe['videoFilePath'], FILTER_VALIDATE_URL)) { ?>
      <div style="width:220px; height:160px; overflow:hidden; border-radius:12px; border:1px solid #e3d2c8; flex-shrink:0; margin-left:20px;">
        <video controls width="220" height="160" style="width:220px; height:160px; object-fit:cover; display:block;">
          <source src="uploads/<?php echo $recipe['videoFilePath']; ?>">
        </video>
      </div>
    <?php } ?>

  </div>
</div>

      <div class="form-actions">
        <button type="submit" class="submit-btn">Update Recipe</button>
      </div>

    </form>
  </section>

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

    <div class="footer-bottom">&copy; 2026 Savora. All rights reserved.</div>

  </footer>
  <script src="scriptS.js"></script>

</body>
</html>
