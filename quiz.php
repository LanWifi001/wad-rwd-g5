<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: lg-reg.php");
    exit;
}
require_once __DIR__ . "/db.php";

$db = get_db();
$user = $_SESSION['user'];
$user_id = $user['id'];

// ---------- START QUIZ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Start new quiz
    if ($action === 'start_quiz') {
        $selected_topics = $_POST['quiz_topics'] ?? [];
        if (empty($selected_topics)) {
            header("Location: topics.php?error=no_topics");
            exit;
        }

        $placeholders = str_repeat('?,', count($selected_topics) - 1) . '?';
        $query = $db->prepare("SELECT topic_name, term, definition FROM topics WHERE user_id=? AND topic_name IN ($placeholders)");
        $query->bindValue(1, $user_id, SQLITE3_INTEGER);
        foreach ($selected_topics as $i => $topic) {
            $query->bindValue($i + 2, $topic, SQLITE3_TEXT);
        }
        $res = $query->execute();

        $questions = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $questions[] = $row;
        }

        shuffle($questions);

        $_SESSION['quiz'] = [
            'questions' => $questions,
            'current' => 0,
            'score' => 0
        ];

        header("Location: quiz.php");
        exit;
    }

    // Answer question
    if ($action === 'answer_quiz' && isset($_SESSION['quiz'])) {
        $answer = strtolower(trim($_POST['answer'] ?? ''));
        $index = $_SESSION['quiz']['current'];
        $questions = $_SESSION['quiz']['questions'];

        if ($index < count($questions)) {
            $correct = strtolower(trim($questions[$index]['definition']));
            if ($answer === $correct) {
                $_SESSION['quiz']['score']++;
            }
            $_SESSION['quiz']['current']++;
        }

        header("Location: quiz.php");
        exit;
    }

    // Restart quiz
    if ($action === 'restart_quiz') {
        unset($_SESSION['quiz']);
        header("Location: topics.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quiz - QuizMania</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h1 class="mb-4">Quiz Time! 📝</h1>

        <?php if (!isset($_SESSION['quiz']) || empty($_SESSION['quiz']['questions'])): ?>
            <p class="text-muted">No quiz in progress. Go back to <a href="topics.php">Topics</a> to start one.</p>
        <?php else: ?>
            <?php
            $quiz = $_SESSION['quiz'];
            $total = count($quiz['questions']);
            $current_index = $quiz['current'];
            ?>

            <?php if ($current_index < $total): ?>
                <?php $q = $quiz['questions'][$current_index]; ?>
                <div class="card p-4 mb-3">
                    <h4>Topic: <?= htmlspecialchars($q['topic_name']) ?></h4>
                    <p><strong>Term:</strong> <?= htmlspecialchars($q['term']) ?></p>
                    <form method="post">
                        <input type="hidden" name="action" value="answer_quiz">
                        <input type="text" name="answer" class="form-control mb-2" placeholder="Your answer" required>
                        <button type="submit" class="btn btn-primary">Submit Answer</button>
                    </form>
                    <small>Question <?= $current_index + 1 ?> of <?= $total ?></small>
                </div>
            <?php else: ?>
                <div class="card p-4 mb-3">
                    <h4>Quiz Finished! 🎉</h4>
                    <p>Your Score: <?= $quiz['score'] ?> / <?= $total ?></p>
                    <form method="post">
                        <input type="hidden" name="action" value="restart_quiz">
                        <button type="submit" class="btn btn-success">Restart Quiz</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>