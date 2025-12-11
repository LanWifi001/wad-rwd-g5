<?php
session_start();
if (!isset($_SESSION['user']))
    header("Location: lg-reg.php");
require_once __DIR__ . "/db.php";

$db = get_db();
$user = $_SESSION['user'];
$user_id = $user['id'];

// ---------- Handle POST Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add new term / topic
    if ($action === 'delete_acc') {
        // delete the user
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->execute();

        // destroy session
        session_destroy();

        // redirect home or login page
        header("Location: lg-reg.php");
        exit;
    }
    header("Location: dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QuizMania Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>

    <div class="offcanvas-md offcanvas-start" tabindex="-1" id="offcanvasResponsive"
        aria-labelledby="offcanvasResponsiveLabel">
        <div class="offcanvas-header bg-primary m-0">
            <h2 class="offcanvas-title" id="offcanvasResponsiveLabel">QuizMania</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive"
                aria-label="Close"></button>
        </div>
        <aside class="offcanvas-body d-md-none text-white">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="topics.php">Topics</a></li>
                <li><a href="quiz.php">Quiz</a></li>
                <li><a href="settings.php" class="active">Settings</a></li>
            </ul>
        </aside>
    </div>

    <main id="main">
        <aside class="navbar d-none d-md-block p-4" id="sidebar">
            <ul id="navbar">
                <li><a href="dashboard.php" class="navbar-collapsed">🏠</a></li>
                <li><a href="topics.php" class="navbar-collapsed">🗂️</a></li>
                <li><a href="quiz.php" class="navbar-collapsed">📝</a></li>
                <li><a href="settings.php" class="active navbar-collapsed">⚙️</a></li>
                <div id="hidden-collapse">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="topics.php">Topics</a></li>
                    <li><a href="quiz.php">Quiz</a></li>
                    <li><a href="settings.php" class="active">Settings</a></li>
                </div>
            </ul>
        </aside>

        <section class="col d-block">
            <header class="navbar bg-primary text-white p-4">
                <div class="container-fluid">
                    <div class="navbar-brand text-white g-2" style="scale: 1.6;">
                        <button class="btn text-white navbar-btn mb-1 d-none d-md-inline-block" style="scale: 1.2;"
                            type="button">☰</button>
                        <a href="dashboard.php" class="text-white text-decoration-none"><img src="mania.png" alt="Logo"
                                width="30" height="24" class="d-inline-block align-text-top">
                            <strong>QuizMania</strong></a>
                    </div>
                </div>
                <button class="btn text-white d-md-none" style="scale: 1.6;" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive">☰</button>
            </header>
            <section class="px-4 pt-2 mb-4">
                <div class="header-top mb-4">
                    <div>
                        <h1>Settings ⚙️</h1>
                    </div>
                </div>

                <!-- Stats Cards -->

                <div id="accordion">
                    <div class="card">
                        <h5 class="mb-0">
                            <button class="btn btn-white w-100 h-100" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                Dark Mode
                            </button>
                        </h5>

                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-bs-parent="#accordion">
                            <div class="card-body">
                                <div>
                                    <div id="li-dark" class="dark-cont">Dark Mode <button id="toggle-dark"
                                            class="dark-mode">
                                            <p>◑</p>
                                        </button></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h5 class="mb-0">
                            <button class="btn btn-white w-100 h-100" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Logout
                            </button>
                        </h5>

                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-bs-parent="#accordion">
                            <div class="card-body">
                                <div>
                                    <p>Are you sure you want to log out?</p>
                                </div>
                                <form method="post" action="logout.php" class="logout-bottom mt-4">
                                    <button type="submit" class="btn btn-danger w-50">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h5 class="mb-0">
                            <button class="btn btn-white w-100 h-100" data-bs-toggle="collapse"
                                data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Delete Account
                            </button>
                        </h5>

                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                            data-bs-parent="#accordion">
                            <div class="card-body">
                                <div>
                                    <p>Are you sure you want to delete your account?</p>
                                </div>
                                <form method="post" class="logout-bottom mt-4">
                                    <input type="hidden" name="action" value="delete_acc">
                                    <button type="submit" class="btn btn-danger w-50">Delete Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
            <footer class="bg bg-primary text-white text-center p-4">
                <div class="top-footer">
                    <div class="footer-body">
                        <strong><b>|</b> QuizMania</strong><br><br>
                        <p>Organize your study sessions and stay smart with your studies.</p>
                    </div>
                    <div class="footer-body">
                        <strong><b>|</b> About our Website</strong><br><br>
                        <p>QuizMania is a simple and user-friendly web application built to help students review more
                            effectively. It lets users store, manage, and study topic-based definitions and terms, while
                            also offering features like quizzes, dark mode, and user account management.</p>
                    </div>
                    <div class="footer-body">
                        <strong><b>|</b> Contact</strong><br><br>
                        <p>La Verdad Christian College
                            <br>Apalit, Pampanga, 2016
                        </p>
                        <p>Phone: 09656337780</p>
                    </div>
                </div>
                <span></span>

                <div class="bottom-footer">&#169; 2025 All Rights Reserved</div>
            </footer>
    </main>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="navbar.js"></script>
<script src="DarkMode.js"></script>

</html>
<div class="settings-menu">

</div>