<?php
// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
ini_set('session.cookie_samesite', 'Lax');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许 POST 请求']);
    exit();
}

// 获取请求数据
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

// 验证参数
$section_id = isset($requestData['section_id']) ? intval($requestData['section_id']) : 0;
$last_id = isset($requestData['last_id']) ? intval($requestData['last_id']) : 0;
$limit = isset($requestData['limit']) ? min(intval($requestData['limit']), 20) : 10;

if ($section_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '请提供有效的 section_id']);
    exit();
}

try {
    // 数据库连接
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查询正常帖 (status = 2) 并支持分页
    $sql = "
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
        WHERE p.section_id = ? AND p.status = 2  -- 只获取正常帖
    ";

    $params = [$section_id];

    // 分页逻辑
    if ($last_id > 0) {
        $sql .= " AND p.post_id < ?";
        $params[] = $last_id;
    }

    $sql .= " ORDER BY p.post_id DESC LIMIT " . ($limit + 1);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 处理分页信息
    $has_more = count($posts) > $limit;
    if ($has_more) {
        array_pop($posts);
    }

    // 获取最后一条ID用于下次分页
    $next_last_id = 0;
    if (!empty($posts)) {
        $next_last_id = end($posts)['post_id'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'posts' => $posts,
            'type' => 'normal',  // 标识这是正常帖
            'pagination' => [
                'has_more' => $has_more,
                'next_last_id' => $next_last_id
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库错误']);
}
?>
