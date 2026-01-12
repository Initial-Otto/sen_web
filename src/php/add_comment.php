<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 检查用户是否登录
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit();
}

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$post_id = isset($input['post_id']) ? intval($input['post_id']) : 0;
$content_text = isset($input['content_text']) ? trim($input['content_text']) : '';

if ($post_id <= 0 || empty($content_text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '帖子ID和评论内容不能为空']);
    exit();
}

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 获取当前楼层号
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $floor_number = $result['count'] + 1;

    // 插入评论
    $stmt = $pdo->prepare("
        INSERT INTO comments (post_id, user_id, floor_number, content_text) 
        VALUES (?, ?, ?, ?)
    ");
    $update = $pdo->prepare("
        UPDATE posts set last_replied_at=NOW() WHERE post_id= ?
    ");
    $stmt->execute([$post_id, $_SESSION['user_id'], $floor_number, $content_text]);
    $update->execute([$post_id]);

    echo json_encode([
        'success' => true,
        'message' => '评论成功'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库错误']);
}
?>
