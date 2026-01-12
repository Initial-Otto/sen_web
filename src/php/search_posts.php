<?php
// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// 启用错误显示（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

try {
    // 获取请求数据
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON解析错误: ' . json_last_error_msg());
    }

    // 验证参数
    $keyword = isset($requestData['keyword']) ? trim($requestData['keyword']) : '';
    $section_id = isset($requestData['section_id']) ? intval($requestData['section_id']) : 0;
    $last_id = isset($requestData['last_id']) ? intval($requestData['last_id']) : 0;
    $limit = isset($requestData['limit']) ? min(intval($requestData['limit']), 20) : 10;

    if (empty($keyword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '请提供搜索关键词']);
        exit();
    }

    // 数据库连接
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 测试连接
    $pdo->query("SELECT 1");

    // 使用LIKE搜索替代全文搜索
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
            pc.height,
            -- 计算相关度得分
            (CASE 
                WHEN p.title LIKE :keyword_like THEN 3
                WHEN pc.content_text LIKE :keyword_like THEN 2
                ELSE 1
            END) as relevance_score
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN post_contents pc ON p.post_id = pc.post_id
        WHERE (p.status = 1 OR p.status = 2)  -- 正常帖子
          AND (p.title LIKE :keyword_like OR pc.content_text LIKE :keyword_like)
    ";

    // 添加版块筛选
    if ($section_id > 0) {
        $sql .= " AND p.section_id = :section_id";
    }

    // 分页逻辑
    if ($last_id > 0) {
        $sql .= " AND p.post_id < :last_id";
    }

    $sql .= " ORDER BY p.post_id DESC LIMIT " . ($limit + 1);

    $stmt = $pdo->prepare($sql);

    // 绑定参数
    $params = [':keyword_like' => '%' . $keyword . '%'];
    if ($section_id > 0) {
        $params[':section_id'] = $section_id;
    }
    if ($last_id > 0) {
        $params[':last_id'] = $last_id;
    }

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
            'type' => 'search',
            'keyword' => $keyword,
            'pagination' => [
                'has_more' => $has_more,
                'next_last_id' => $next_last_id
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
}