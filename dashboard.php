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
    if ($action === 'add_term') {
        $topic_name = trim($_POST['topic_name'] ?? '');
        $term = trim($_POST['term'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        if ($topic_name && $term) {
            $stmt = $db->prepare("INSERT INTO topics (user_id, topic_name, term, definition) VALUES (?,?,?,?)");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $topic_name, SQLITE3_TEXT);
            $stmt->bindValue(3, $term, SQLITE3_TEXT);
            $stmt->bindValue(4, $definition, SQLITE3_TEXT);
            $stmt->execute();
        }
    }

    // Edit term
    if ($action === 'edit_term') {
        $topic_id = (int) ($_POST['topic_id'] ?? 0);
        $term = trim($_POST['term'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        if ($topic_id && $term) {
            $stmt = $db->prepare("UPDATE topics SET term=?, definition=? WHERE topic_id=? AND user_id=?");
            $stmt->bindValue(1, $term, SQLITE3_TEXT);
            $stmt->bindValue(2, $definition, SQLITE3_TEXT);
            $stmt->bindValue(3, $topic_id, SQLITE3_INTEGER);
            $stmt->bindValue(4, $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    // Delete term
    if ($action === 'delete_term') {
        $topic_id = (int) ($_POST['topic_id'] ?? 0);
        if ($topic_id) {
            $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=? AND user_id=?");
            $stmt->bindValue(1, $topic_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    // Delete entire topic
    if ($action === 'delete_topic') {
        $topic_name = trim($_POST['topic_name'] ?? '');
        if ($topic_name) {
            $stmt = $db->prepare("DELETE FROM topics WHERE user_id=? AND topic_name=?");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $topic_name, SQLITE3_TEXT);
            $stmt->execute();
        }
    }

    header("Location: dashboard.php");
    exit;
}

// ---------- Fetch Topics ----------
$stmt = $db->prepare("SELECT * FROM topics WHERE user_id=? ORDER BY topic_name ASC");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$topics = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $topics[$row['topic_name']][] = $row;
}

// ---------- Subjects & Stats ----------
$subjects = [];
$total_terms = 0;
foreach ($topics as $tname => $terms) {
    $subjects[] = ['topic_name' => $tname, 'topic_count' => count($terms)];
    $total_terms += count($terms);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QuizMania Topics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-image: url("study.jpg");
            /* Optional: Further styling for the background image */
            background-repeat: no-repeat;
            /* Prevents the image from repeating */
            background-position: center;
            /* Centers the image */
            background-size: cover;
            /* Scales the image to cover the entire element */
            background-attachment: fixed;
            /* Keeps the image fixed while scrolling */
        }

        aside {
            width: 250px;
            background: #fff;
            height: 100vh;
            /* position: fixed; */
            padding: 25px;
            box-shadow: 2px 0 15px rgba(0, 0, 0, .05);
        }

        aside h2 {
            font-weight: 700;
            margin-bottom: 25px;
            color: #007bff;
        }

        aside ul {
            list-style: none;
            padding: 0;
        }

        aside li a {
            text-decoration: none;
            display: block;
            padding: 12px;
            border-radius: 10px;
            color: #555;
            font-weight: 500;
            transition: .2s;
        }

        aside li a.active,
        aside li a:hover {
            background: #007bff;
            color: #fff;
        }

        main {
            display: grid;
            grid-template-columns: 1fr 4fr;
        }
        section {
            background-color: #f0f2f7;
        }

        .sidebar {
            width: 250px;
        }

        .sidebar a {
            text-decoration: none;
            display: block;
            padding: 12px;
            border-radius: 8px;
            color: #555;
            font-weight: 500;
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: #007bff;
            color: #fff;
        }

        .subject-item,
        .card-hover {
            transition: 0.2s;
        }

        .subject-item:hover,
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .term-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .term-row input {
            flex: 1;
            padding: 5px;
        }

        .term-row button {
            flex: none;
        }

        @media (max-width: 769px) {
            main {
                display: block;
            }
        }
    </style>
</head>

<body class="container">
    <header class="navbar bg-primary text-white p-4">
        <h2>QuizMania</h2><button class="btn btn-primary d-md-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive">☰</button>
    </header>

    <div class="offcanvas-md offcanvas-start" tabindex="-1" id="offcanvasResponsive"
        aria-labelledby="offcanvasResponsiveLabel">
        <div class="offcanvas-header bg-primary m-0">
            <h2 class="offcanvas-title" id="offcanvasResponsiveLabel">QuizMania</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive"
                aria-label="Close"></button>
        </div>
        <aside class="offcanvas-body d-md-none text-white">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="topics.php">Topics</a></li>
            </ul>
            <form method="post" action="logout.php" class="logout-bottom mt-4">
                <button type="submit" class="btn btn-danger w-100">Logout</button>
            </form>
        </aside>
    </div>

    <main>
        <aside class="navbar d-none d-md-block p-4">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="topics.php">Topics</a></li>
            </ul>
            <form method="post" action="logout.php" class="logout-bottom mt-4">
                <button type="submit" class="btn btn-danger w-100">Logout</button>
            </form>
        </aside>

        <section class="col d-block p-4">
            <div class="header-top mb-4">
                <div>
                    <h1>Welcome back <strong><?= htmlspecialchars($user['name']) ?></strong>! 👋</h1>
                    <p class="text-muted">Ready to start another learning journey?</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card p-3 card-hover text-center">
                        <h6>Total Terms</h6><strong><?= $total_terms ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 card-hover text-center">
                        <h6>Subjects</h6><strong><?= count($subjects) ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 card-hover text-center">
                        <h6>Study Streak</h6><strong>5 days 🔥</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 card-hover text-center">
                        <h6>Progress</h6>
                        <div class="progress mt-2">
                            <div class="progress-bar" style="width:72%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#newTopicForm">
                Create New Topic
            </button>

            <div class="collapse mb-4" id="newTopicForm">
                <form method="post">
                    <input type="hidden" name="action" value="add_term">
                    <div class="term-row">
                        <input type="text" name="topic_name" placeholder="Topic Name" required>
                        <input type="text" name="term" placeholder="Term" required>
                        <input type="text" name="definition" placeholder="Definition">
                        <button type="submit" class="btn btn-success">Create Topic</button>
                    </div>
                </form>
            </div>

            <h2>Your Topics</h2>

            <?php if (empty($topics)): ?>
                <p class="text-muted">No topics yet.</p>
            <?php else: ?>
                <div class="accordion" id="topicsAccordion">

                    <?php foreach ($topics as $tname => $terms): ?>
                        <div class="accordion-item mb-2">

                            <h2 class="accordion-header" id="heading_<?= md5($tname) ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 px-2">

                                    <!-- Accordion Button -->
                                    <button class="accordion-button collapsed flex-grow-1" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse_<?= md5($tname) ?>">
                                        <?= htmlspecialchars($tname) ?> (<?= count($terms) ?> terms)
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="post" class="ms-2">
                                        <input type="hidden" name="action" value="delete_topic">
                                        <input type="hidden" name="topic_name" value="<?= htmlspecialchars($tname) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>

                                </div>
                            </h2>

                            <div id="collapse_<?= md5($tname) ?>" class="accordion-collapse collapse"
                                data-bs-parent="#topicsAccordion">
                                <div class="accordion-body">

                                    <?php foreach ($terms as $t): ?>
                                        <div class="subject-item mb-2 d-flex justify-content-between align-items-center">

                                            <!-- Term text -->
                                            <div class="me-3">
                                                <strong><?= htmlspecialchars($t['term']) ?></strong> :
                                                <?= htmlspecialchars($t['definition']) ?>
                                            </div>

                                            <!-- Edit + Delete buttons -->
                                            <div class="d-flex gap-1">

                                                <form method="post" class="d-flex gap-1">
                                                    <input type="hidden" name="action" value="edit_term">
                                                    <input type="hidden" name="topic_id" value="<?= $t['topic_id'] ?>">
                                                    <input type="text" name="term" value="<?= htmlspecialchars($t['term']) ?>"
                                                        required>
                                                    <input type="text" name="definition"
                                                        value="<?= htmlspecialchars($t['definition']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                </form>

                                                <form method="post">
                                                    <input type="hidden" name="action" value="delete_term">
                                                    <input type="hidden" name="topic_id" value="<?= $t['topic_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>

                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Add term -->
                                    <form method="post" class="mt-3">
                                        <input type="hidden" name="action" value="add_term">
                                        <input type="hidden" name="topic_name" value="<?= htmlspecialchars($tname) ?>">

                                        <div class="term-row">
                                            <input type="text" name="term" placeholder="New term" required>
                                            <input type="text" name="definition" placeholder="Definition">
                                            <button type="submit" class="btn btn-success btn-sm">Add</button>
                                        </div>
                                    </form>

                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        </section>
    </main>
    <footer class="bg bg-primary text-white text-center p-4">2025 All Rights Reserved</footer>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

</html>