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

    // ---------- ADD TERM ----------
    if ($action === 'add_term') {
        $topic_name = trim($_POST['topic_name'] ?? '');
        $term = trim($_POST['term'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        if ($topic_name && $term && $definition) {
            $stmt = $db->prepare("INSERT INTO topics (user_id, topic_name, term, definition) VALUES (?,?,?,?)");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $topic_name, SQLITE3_TEXT);
            $stmt->bindValue(3, $term, SQLITE3_TEXT);
            $stmt->bindValue(4, $definition, SQLITE3_TEXT);
            $stmt->execute();
        }
    }

    // ---------- EDIT TERM ----------
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

    // ---------- DELETE TERM ----------
    if ($action === 'delete_term') {
        $topic_id = (int) ($_POST['topic_id'] ?? 0);
        if ($topic_id) {
            $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=? AND user_id=?");
            $stmt->bindValue(1, $topic_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    // ---------- DELETE TOPIC ----------
    if ($action === 'delete_topic') {
        $topic_name = trim($_POST['topic_name'] ?? '');
        if ($topic_name) {
            $stmt = $db->prepare("DELETE FROM topics WHERE user_id=? AND topic_name=?");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $topic_name, SQLITE3_TEXT);
            $stmt->execute();
        }
    }


    header("Location: topics.php");
    exit;
}


// Fetch topics
$stmt = $db->prepare("SELECT * FROM topics WHERE user_id=? ORDER BY topic_name ASC");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$topics = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $topics[$row['topic_name']][] = $row;
}

// Subjects & stats
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
    <link rel="stylesheet" href="styles.css">
</head>

<body>

    <div class="offcanvas-md offcanvas-start" tabindex="-1" id="offcanvasResponsive"
        aria-labelledby="offcanvasResponsiveLabel">
        <div class="offcanvas-header bg-primary m-0">
            <h2 class="offcanvas-title text-white" id="offcanvasResponsiveLabel">QuizMania</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasResponsive"
                aria-label="Close"></button>
        </div>
        <aside class="offcanvas-body d-md-none">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="topics.php" class="active">Topics</a></li>
                <li><a href="quiz.php">Quiz</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </aside>
    </div>

    <main id="main">
        <aside class="navbar d-none d-md-block p-4" id="sidebar">
            <ul id="navbar">
                <li><a href="dashboard.php" class="navbar-collapsed">🏠</a></li>
                <li><a href="topics.php" class="active navbar-collapsed">🗂️</a></li>
                <li><a href="quiz.php" class="navbar-collapsed">📝</a></li>
                <li><a href="settings.php" class="navbar-collapsed">⚙️</a></li>
                <div id="hidden-collapse">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="topics.php" class="active">Topics</a></li>
                    <li><a href="quiz.php">Quiz</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </div>
            </ul>
        </aside>

        <section class="col d-block">
            <header class="navbar text-white p-4">
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
                <div class="header-top mb-4 p">
                    <div>
                        <h1>Topics Management 👋</h1>
                        <p class="text-muted">Manage your topics and terms</p>
                    </div>
                </div>

                <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#newTopicForm"
                    aria-expanded="false" aria-controls="newTopicForm">
                    Create New Topic ➕
                </button>

                <div class="collapse mb-4 card p-2" id="newTopicForm">
                    <form method="post" class="g-2 row">
                        <div class="topic-form col-12">
                            <input class="d-none" type="hidden" name="action" value="add_term">
                            <h2><input class="form-control form-control-lg" type="text" name="topic_name"
                                    placeholder="Topic Name" class="new-topic-name" required>
                            </h2>
                            <div class="term-def">
                                <input class="form-control" type="text" name="term" placeholder="Term" required>
                                <input class="form-control form-control-sm" type="text" name="definition"
                                    placeholder="Definition" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 col-12">Create Topic</button>
                    </form>
                </div>

                <div id="topics">
                    <h2>Your Topics</h2>
                    <?php if (empty($topics)): ?>
                        <p class="text-muted">No topics yet.</p>
                    <?php else: ?>
                        <div class="accordion" id="topicsAccordion">
                            <?php foreach ($topics as $tname => $terms): ?>
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header d-flex justify-content-between align-items-center"
                                        id="heading_<?= md5($tname) ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse_<?= md5($tname) ?>" aria-expanded="false"
                                            aria-controls="collapse_<?= md5($tname) ?>">
                                            <?= htmlspecialchars($tname) ?> (<?= count($terms) ?> terms)
                                        </button>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete_topic">
                                            <input type="hidden" name="topic_name" value="<?= htmlspecialchars($tname) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete Topic</button>
                                        </form>
                                    </h2>
                                    <div id="collapse_<?= md5($tname) ?>" class="accordion-collapse collapse"
                                        aria-labelledby="heading_<?= md5($tname) ?>" data-bs-parent="#topicsAccordion">
                                        <div class="accordion-body card">
                                            <?php foreach ($terms as $t): ?>
                                                <div class="subject-item mb-2" id="topic-form">
                                                    <div><?= htmlspecialchars($t['term']) ?> : <?= htmlspecialchars($t['definition']) ?>
                                                    </div>
                                                    <div>
                                                        <form method="post" style="display:inline-block;">
                                                            <input type="hidden" name="action" value="edit_term">
                                                            <input type="hidden" name="topic_id" value="<?= $t['topic_id'] ?>">
                                                            <input type="text" name="term" value="<?= htmlspecialchars($t['term']) ?>"
                                                                required>
                                                            <input type="text" name="definition"
                                                                value="<?= htmlspecialchars($t['definition']) ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                        </form>
                                                        <form method="post" style="display:inline-block;">
                                                            <input type="hidden" name="action" value="delete_term">
                                                            <input type="hidden" name="topic_id" value="<?= $t['topic_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <form method="post" class="mt-2" action="topics.php#direct-topic">
                                                <input type="hidden" name="action" value="add_term">
                                                <input type="hidden" name="topic_name" value="<?= htmlspecialchars($tname) ?>">
                                                <div class="term-row">
                                                    <input type="text" name="term" placeholder="New term" required>
                                                    <input type="text" name="definition" placeholder="Definition" required>
                                                    <button type="submit" class="btn btn-sm btn-success">Add Term</button>
                                                </div>
                                            </form>
                                            <div id="direct-topic"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <div id="direct-quiz"></div>
            <footer class="bg text-white text-center p-4">
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
        </section>
    </main>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="navbar.js"></script>
<script src="DarkMode.js"></script>
</html>