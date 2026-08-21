<?php
include 'db.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'like' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id = intval($_POST['article_id'] ?? 0);
    if ($article_id > 0) {
        $stmt = $conn->prepare("UPDATE articles SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($action === 'comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id = intval($_POST['article_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($article_id > 0 && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (article_id, comment_text) VALUES (?, ?)");
        $stmt->bind_param("is", $article_id, $comment);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);