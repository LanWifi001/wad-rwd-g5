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
        if ($topic_name && $term) {
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

    // ---------- START QUIZ ----------
    if ($action === 'start_quiz') {

        if (!isset($_POST['quiz_topics'])) {
            header("Location: topics.php?error=no_topics");
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

        header("Location: topics.php?quiz=1");
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

        header("Location: topics.php?quiz=1");
        exit;
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
    <header class="navbar bg-primary text-white p-4">
        <div class="container-fluid">
            <a class="navbar-brand text-white" style="scale: 1.6;" href="dashboard.php">
                <img src="mania.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
                <strong>QuizMania</strong>
            </a>
        </div>
        <button class="btn text-white d-md-none" style="scale: 1.6;" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive">☰</button>
    </header>

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
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </aside>
    </div>

    <main>
        <aside class="navbar d-none d-md-block p-4">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="topics.php" class="active">Topics</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </aside>
        <section class="col d-block p-4">
            <div class="header-top mb-4">
                <div>
                    <h1>Topics Management 👋</h1>
                    <p class="text-muted">Manage your topics and terms</p>
                </div>
            </div>

            <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#newTopicForm"
                aria-expanded="false" aria-controls="newTopicForm">
                Create New Topic
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
                                    <div class="accordion-body">
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
            <!-- Quiz -->
            <div id="quizzes" class="mb-5">
                <h2>Start Quiz</h2>
                <?php if (empty($subjects)): ?>
                    <p class="text-muted">No topics to take a quiz yet.</p>
                <?php endif; ?>
                <?php if (!empty($subjects)): ?>

                    <form method="post" action="topics.php#direct-quiz">
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
                                                    <input class="col" style="scale: 0.8;" type="checkbox" name="quiz_topics[]"
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
                        <div class="mb-4 p-3 bg-white rounded shadow">
                            <h4>Quiz: <?= htmlspecialchars($q['topic_name']) ?></h4>
                            <p><strong>Term:</strong> <?= htmlspecialchars($q['term']) ?></p>
                            <form method="post" action="topics.php#direct-quiz">
                                <input type="hidden" name="action" value="answer_quiz">
                                <input type="text" name="answer" placeholder="Your answer" required class="form-control mb-2">
                                <button type="submit" class="btn btn-primary answer-btn">Submit Answer</button>
                            </form>
                            <p>Question <?= $_SESSION['quiz']['current'] + 1 ?> of <?= count($_SESSION['quiz']['questions']) ?>
                            </p>
                        </div>
                    <?php elseif (isset($_SESSION['quiz_finished'])): ?>
                        <div class="mb-4 p-3 bg-white rounded shadow">
                            <h4>Quiz Finished!</h4>
                            <p>Your Score: <?= $_SESSION['quiz']['score'] ?> / <?= count($_SESSION['quiz']['questions']) ?></p>
                            <?php unset($_SESSION['quiz'], $_SESSION['quiz_finished']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div id="direct-quiz"></div>

        </section>
    </main>
    <footer class="bg bg-primary text-white text-center p-4">2025 All Rights Reserved</footer>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

</html>
