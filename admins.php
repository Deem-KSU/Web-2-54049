<?php
session_start();

include('db_connection.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php?error=Please login first");
    exit();
}

if ($_SESSION['user_type'] != 'admin') {
    header("Location: login.php?error=You are not authorized to access the admin page");
    exit();
}

$adminID = $_SESSION['user_id'];

$adminQuery = "SELECT firstName, lastName, emailAddress FROM users WHERE id = $adminID";
$adminResult = mysqli_query($conn, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

$reportsQuery = "
SELECT 
    report.id AS reportID,
    report.recipeID,
    recipe.name AS recipeName,
    users.id AS creatorID,
    users.firstName,
    users.lastName,
    users.photoFileName
FROM report
JOIN recipe ON report.recipeID = recipe.id
JOIN users ON recipe.userID = users.id
ORDER BY report.id DESC
";
$reportsResult = mysqli_query($conn, $reportsQuery);

$blockedQuery = "SELECT firstName, lastName, emailAddress FROM blockeduser ORDER BY id DESC";
$blockedResult = mysqli_query($conn, $blockedQuery);

$reportsCount = mysqli_num_rows($reportsResult);
$blockedCount = mysqli_num_rows($blockedResult);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>SAVORA | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styleR.css">

 
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>

  <header class="main-header">
    <img src="image/logo.png" alt="Savora Logo" class="logo">
    <h1 class="Savora-name">SAVORA</h1>
    <a class="header-btn" href="signout.php">Sign Out</a>
  </header>

  <main class="page-content">
    <section class="container">
      <div class="page-hero">
        <div class="hero-text">
          <h2 class="page-title">Welcome, <?php echo htmlspecialchars($admin['firstName']); ?></h2>
          <p class="page-subtitle">Manage reports and ensure the community stays safe.</p>
        </div>
      </div>

      <section class="card admin-info">
        <h3 class="section-title">My Information</h3>
        <div class="info-item">
          <span class="info-value"><span class="info-label">Name: </span><?php echo htmlspecialchars($admin['firstName'] . " " . $admin['lastName']); ?></span>
        </div>
        <div class="info-item">
          <span class="info-value"><span class="info-label">Email:</span><?php echo htmlspecialchars($admin['emailAddress']); ?></span>
        </div>
      </section>

      <section class="card">
        <div class="section-head">
          <h3 class="section-title">Pending Recipe Reports</h3>
          <div class="pill"><?php echo $reportsCount; ?> Reports</div>
        </div>

        <div class="table-wrap">
          <?php if ($reportsCount > 0) { ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Recipe</th>
                <th>Creator</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($report = mysqli_fetch_assoc($reportsResult)) { ?>
              
             
              <tr id="report-row-<?php echo $report['reportID']; ?>">

                <td>
                  <a class="table-link" href="view_recipe.php?id=<?php echo $report['recipeID']; ?>">
                    <?php echo htmlspecialchars($report['recipeName']); ?>
                  </a>
                </td>
                <td class="creator-cell">
                  <img class="avatar" src="uploads/<?php echo htmlspecialchars($report['photoFileName']); ?>" alt="Creator Photo">
                  <span><?php echo htmlspecialchars($report['firstName'] . " " . $report['lastName']); ?></span>
                </td>
                <td>
                  <form class="action-form" action="handleReport.php" method="post">
                    <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                    <input type="hidden" name="recipeID" value="<?php echo $report['recipeID']; ?>">
                    <input type="hidden" name="creatorID" value="<?php echo $report['creatorID']; ?>">

                    <label class="radio-option">
                      <input type="radio" name="action" value="block" required>
                      Block user
                    </label>

                    <label class="radio-option">
                      <input type="radio" name="action" value="dismiss">
                      Dismiss report
                    </label>

                    <button class="btn danger" type="submit">Submit</button>
                  </form>
                </td>
              </tr>

              <?php } ?>
            </tbody>
          </table>
          <?php } else { ?>
            <p>No pending reports found.</p>
          <?php } ?>
        </div>
      </section>

      <section class="card">
        <div class="section-head">
          <h3 class="section-title">Blocked Users</h3>
          <div class="pill"><?php echo $blockedCount; ?> Users</div>
        </div>

        <div class="table-wrap">
          <?php if ($blockedCount > 0) { ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
              </tr>
            </thead>
           <tbody id="blocked-users-body">
              <?php while ($blocked = mysqli_fetch_assoc($blockedResult)) { ?>
              <tr>
                <td><?php echo htmlspecialchars($blocked['firstName'] . " " . $blocked['lastName']); ?></td>
                <td><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
          <?php } else { ?>
            <p>No blocked users found.</p>
          <?php } ?>
        </div>
      </section>
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

 
  <script>
  $(document).ready(function () {
    $(".action-form").submit(function (e) {
      e.preventDefault();

      var form = $(this);
      var reportID = form.find("input[name='reportID']").val();

      $.ajax({
        url: "handleReport.php",
        type: "POST",
        data: form.serialize(),
        success: function (response) {
     var data = response.split("|");

if (data[0].trim() === "true") {

  $("#report-row-" + reportID).remove();

  var currentCount = parseInt($(".pill").first().text());
  var newCount = currentCount - 1;

  $(".pill").first().text(newCount + " Reports");

  if (data.length === 3) {
    $("#blocked-users-body").prepend(
      "<tr><td>" + data[1] + "</td><td>" + data[2] + "</td></tr>"
    );
  }

  if ($("tbody:first tr").length === 0) {
    $(".table-wrap").first().html("<p>No pending reports found.</p>");
  }

} else {
  alert("Action failed");
}
        }
      });
    });
  });
  </script>

</body>
</html>
