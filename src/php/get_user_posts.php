<?php
// get_user_posts.php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查询当前用户的所有帖子
    $stmt = $pdo->prepare("
        SELECT 
            p.post_id,
            p.title,
            p.created_at,
            p.last_replied_at,
            u.username,
            u.account,
            u.level,
            u.avatar_path,
            pc.content_text,
            pc.image_path,
            pc.width,
            pc.height
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN post_contents pc ON p.post_id = pc.post_id
        WHERE p.user_id = ? AND (p.status = 1 OR p.status = 2)
        ORDER BY p.created_at DESC
        LIMIT 50
    ");

    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回帖子数据
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '获取用户帖子成功',
        'posts' => $posts,
        'has_more' => false, // 用户帖子通常不需要分页
        'next_id' => 0
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '服务器内部错误']);
}
?>
