<?php
// 设置在返回的响应头之前，确保在所有输出之前
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
ini_set('session.cookie_samesite', 'Lax');
// 处理预检请求 (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => '只允许 POST 请求']);
    exit();
}

// 1. 从请求的JSON主体中获取数据，而不是从URL参数
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

// 2. 获取并验证参数
$section_id = isset($requestData['section_id']) ? intval($requestData['section_id']) : 0;
$last_id = isset($requestData['last_id']) ? intval($requestData['last_id']) : 0;
$limit = isset($requestData['limit']) ? min(intval($requestData['limit']), 20) : 10; // 限制每页最多20条

if ($section_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => '请提供有效的 section_id']);
    exit();
}

try {
    // 3. 你的数据库连接和查询逻辑（这里保持和你之前的一样）
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 简化查询：直接使用数据库中的图片宽高
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
        WHERE p.section_id = ? AND p.status IN (1, 2)
    ";

    $params = [$section_id];

    if ($last_id > 0) {
        $sql .= " AND p.post_id < ?";
        $params[] = $last_id;
    }

    $sql .= " ORDER BY 
    p.status ASC,
    p.last_replied_at DESC LIMIT " . ($limit + 1);

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
            'pagination' => [
                'has_more' => $has_more,
                'next_last_id' => $next_last_id
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage()); // 记录错误到日志
    echo json_encode(['success' => false, 'message' => '数据库错误']);
}
?>