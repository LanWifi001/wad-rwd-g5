<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . "/db.php";

$register_error = "";
$login_error = "";
$success = "";

// REGISTER
if (isset($_POST['register'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  if (!$name || !$email || !$password) {
    $register_error = "All fields are required!";
  } else {
    $db = get_db();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bindValue(1, $email, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result->fetchArray()) {
      $register_error = "Email already registered.";
    } else {
      // Insert new user
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
      $stmt->bindValue(1, $name, SQLITE3_TEXT);
      $stmt->bindValue(2, $email, SQLITE3_TEXT);
      $stmt->bindValue(3, $hashedPassword, SQLITE3_TEXT);

      if ($stmt->execute()) {
        $success = "Registration successful! You can now sign in.";
      } else {
        $register_error = "Registration failed. Try again.";
      }
    }
  }
}

// LOGIN
if (isset($_POST['login'])) {
  $email = trim($_POST["email"]);
  $password = trim($_POST["password"]);

  $db = get_db();
  $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
  $stmt->bindValue(1, $email, SQLITE3_TEXT);
  $result = $stmt->execute();
  $user = $result->fetchArray(SQLITE3_ASSOC);

  if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user"] = [
      "id" => $user["id"],
      "name" => $user["username"],
      "email" => $user["email"]
    ];
    header("Location: dashboard.php");
    exit;
  }

  $login_error = "Invalid email or password.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuizMania - Login/Register</title>
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,800" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="assets/bootstrap.min.css" rel="stylesheet">
  <style>
    .alert-danger {
      color: red;
    }

    .alert-success {
      color: green;
    }
  </style>
</head>

<body>
  <div class="container" id="container">

    <!-- REGISTER -->
    <div class="form-container sign-up-container">
      <form method="POST">
        <h1>QUIZMANIA</h1>

        <?php if ($register_error || $success): ?>
          <script>
            document.getElementById("container").classList.add("right-panel-active");
          </script>
          <?php if ($register_error): ?>
            <div class="alert alert-danger"><?= $register_error ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <input type="text" placeholder="Name" name="name" required />
        <input type="email" placeholder="Email" name="email" required />
        <input type="password" placeholder="Password" name="password" required />
        <button name="register">Sign Up</button>
      </form>
    </div>

    <!-- LOGIN -->
    <div class="form-container sign-in-container">
      <form method="POST">
        <h1>QUIZMANIA</h1>
        <?php if ($login_error): ?>
          <script>
            document.getElementById("container").classList.add("left-panel-active");
          </script>
          <div class="alert alert-danger"><?= $login_error ?></div>
        <?php endif; ?>

        <input type="email" placeholder="Email" name="email" required />
        <input type="password" placeholder="Password" name="password" required />
        <button name="login">Sign In</button>
      </form>
    </div>

    <!-- OVERLAY -->
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>Welcome Back!</h1>
          <p>To keep connected with us please login with your personal info</p>
          <button class="ghost" id="signIn">Sign In</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Hello!</h1>
          <p>Enter your personal details and start your journey with us</p>
          <button class="ghost" id="signUp">Sign Up</button>
        </div>
      </div>
    </div>

  </div>

  <script src="assets/bootstrap.bundle.min.js"></script>
  <script src="script.js"></script>
</body>

</html>