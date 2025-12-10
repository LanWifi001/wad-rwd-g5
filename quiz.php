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

    // ---------- START QUIZ ----------
    if ($action === 'start_quiz') {

        if (!isset($_POST['quiz_topics'])) {
            header("Location: quiz.php?error=no_topics");
            exit;
        }

        $selected = $_POST['quiz_topics'];

        // Fetch ALL selected questions
        $placeholders = str_repeat('?,', count($selected) - 1) . '?';
        $query = $db->prepare("
            SELECT topic_name, term, definition 
            FROM topics 
            WHERE user_id=? AND topic_name IN ($placeholders)
        ");
        $query->bindValue(1, $user_id, SQLITE3_INTEGER);
        foreach ($selected as $i => $topic) {
            $query->bindValue($i + 2, $topic, SQLITE3_TEXT);
        }

        $res = $query->execute();

        $questions = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $questions[] = $row;
        }

        shuffle($questions); // randomize

        $_SESSION['quiz'] = [
            'questions' => $questions,
            'current' => 0,
            'score' => 0
        ];

        header("Location: quiz.php?quiz=1");
        exit;
    }

    // ---------- ANSWER QUIZ ----------
    if ($action === 'answer_quiz' && isset($_SESSION['quiz'])) {
        $answer = trim($_POST['answer']);
        $index = $_SESSION['quiz']['current'];
        $questions = $_SESSION['quiz']['questions'];

        if ($index < count($questions)) {
            $correct = strtolower(trim($questions[$index]['definition']));

            if ($answer && strtolower($answer) === $correct) {
                $_SESSION['quiz']['score']++;
            }

            $_SESSION['quiz']['current']++;
        }

        if ($_SESSION['quiz']['current'] >= count($questions)) {
            $_SESSION['quiz_finished'] = true;
        }

        header("Location: quiz.php?quiz=1");
        exit;
    }
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
                <li><a href="topics.php">Topics</a></li>
                <li><a href="quiz.php" class="active">Quiz</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </aside>
    </div>

    <main id="main">
        <aside class="navbar d-none d-md-block p-4" id="sidebar">
            <ul id="navbar">
                <li><a href="dashboard.php" class="navbar-collapsed">🏠</a></li>
                <li><a href="topics.php" class="navbar-collapsed">🗂️</a></li>
                <li><a href="quiz.php" class="active navbar-collapsed">📝</a></li>
                <li><a href="settings.php" class="navbar-collapsed">⚙️</a></li>
                <div id="hidden-collapse">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="topics.php">Topics</a></li>
                    <li><a href="quiz.php" class="active">Quiz</a></li>
                    <li><a href="settings.php">Settings</a></li>
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
            <section class="px-4 pt-2">
                <div class="header-top mb-4">
                    <div>
                        <h1>Take a quiz!</h1>
                        <p class="text-muted">Test your knowledge!</p>
                    </div>
                </div>


                <!-- Quiz -->
                <div id="quizzes" class="mb-5">
                    <h2>Available Quizzes</h2>
                    <?php if (empty($subjects)): ?>
                        <p class="text-muted">No topics to take a quiz yet.</p>
                    <?php endif; ?>
                    <?php if (!empty($subjects)): ?>

                        <form method="post" action="quiz.php#direct-quiz">
                            <input type="hidden" name="action" value="start_quiz">
                            <div class="form-check row g-3">
                                <div class="container-fluid">
                                    <div class="row g-1">
                                        <?php foreach ($subjects as $subject): ?>
                                            <div class="col-6 col-sm-4 quiz">
                                                <!-- Card as label -->
                                                <label class="card h-100 card-hover" style="cursor:pointer;">
                                                    <img src="quiz.jpg" class="card-img-top col" alt="...">
                                                    <div class="card-body text-center row p-2 m-2">
                                                        <input class="col" style="scale: 0.8;" type="checkbox"
                                                            name="quiz_topics[]"
                                                            value="<?= htmlspecialchars($subject['topic_name']) ?>">
                                                        <div class="col-10">
                                                            <h5 class="card-title col card-topic">
                                                                <?= htmlspecialchars($subject['topic_name']) ?>
                                                            </h5>
                                                            <p class="card-text col card-topic">Start Quiz!</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-2">Start Quiz</button>
                        </form>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['quiz']) && isset($_GET['quiz'])): ?>
                        <?php if (!empty($_SESSION['quiz']['questions']) && $_SESSION['quiz']['current'] < count($_SESSION['quiz']['questions'])): ?>
                            <?php $q = $_SESSION['quiz']['questions'][$_SESSION['quiz']['current']]; ?>
                            <div class="card mb-4 p-3 bg-white rounded shadow">
                                <h4>Quiz: <?= htmlspecialchars($q['topic_name']) ?></h4>
                                <p><strong>Term:</strong> <?= htmlspecialchars($q['term']) ?></p>
                                <form method="post" action="quiz.php#direct-quiz">
                                    <input type="hidden" name="action" value="answer_quiz">
                                    <input type="text" name="answer" placeholder="Your answer" required
                                        class="form-control mb-2">
                                    <button type="submit" class="btn btn-primary answer-btn">Submit Answer</button>
                                </form>
                                <p>Question <?= $_SESSION['quiz']['current'] + 1 ?> of
                                    <?= count($_SESSION['quiz']['questions']) ?>
                                </p>
                            </div>
                        <?php elseif (isset($_SESSION['quiz_finished'])): ?>
                            <div class="card mb-4 p-3 bg-white rounded shadow">
                                <h4>Quiz Finished!</h4>
                                <p>Your Score: <?= $_SESSION['quiz']['score'] ?> / <?= count($_SESSION['quiz']['questions']) ?>
                                </p>
                                <?php unset($_SESSION['quiz'], $_SESSION['quiz_finished']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div id="direct-quiz"></div>
            </section>
            <footer class="bg bg-primary text-white text-center p-4">2025 All Rights Reserved</footer>
        </section>
    </main>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="navbar.js"></script>
<script src="DarkMode.js"></script>
</html>