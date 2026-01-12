<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
ini_set('session.cookie_samesite', 'Lax');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit();
}

// 检查用户是否登录
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 验证必需字段
if (!isset($_POST['title']) || !isset($_POST['content']) || !isset($_POST['section_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必需字段: title, content, section_id']);
    exit();
}

$title = trim($_POST['title']);
$content = trim($_POST['content']);
$section_id = intval($_POST['section_id']);
$user_id = $_SESSION['user_id'];

// 基础验证
if (empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '标题和内容不能为空']);
    exit();
}

if (strlen($title) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '标题长度不能超过200个字符']);
    exit();
}

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 开始事务
    $pdo->beginTransaction();

    $processed_content = $content;
    $upload_dir = __DIR__ . '/../../public/img/list/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

// 匹配所有 [img]数字[/img]
    preg_match_all('/\[img\](\d+)\[\/img\]/', $content, $matches);
    error_log("接收到的POST数据: " . print_r($_POST, true));
    error_log("接收到的FILES数据: " . print_r($_FILES, true));
    error_log("匹配到的图片标记: " . print_r($matches[1], true));
    if (!empty($matches[1])) {
        // 检查是否有文件上传
        if (isset($_FILES['imgList']) && is_array($_FILES['imgList']['name'])) {
            foreach ($matches[1] as $index) {
                // 检查对应索引的文件是否存在且无错误
                if (isset($_FILES['imgList']['name'][$index]) &&
                    $_FILES['imgList']['error'][$index] === UPLOAD_ERR_OK) {

                    $file_name = $_FILES['imgList']['name'][$index];
                    $file_tmp = $_FILES['imgList']['tmp_name'][$index];
                    $file_type = $_FILES['imgList']['type'][$index];
                    $file_size = $_FILES['imgList']['size'][$index];
                    $file_error = $_FILES['imgList']['error'][$index];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('不支持的文件类型，仅支持 JPEG, PNG, GIF, WebP 格式');
                    }

                    // 验证文件大小（20MB限制）
                    if ($file_size > 20 * 1024 * 1024) {
                        throw new Exception('文件大小不能超过20MB');
                    }

                    // 生成唯一文件名
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $filename = uniqid('content_img_') . '.' . $file_extension;
                    $file_path = $upload_dir . $filename;

                    // 移动文件
                    if (!move_uploaded_file($file_tmp, $file_path)) {
                        throw new Exception('内容图片上传失败: ' . $file_name);
                    }
                    $web_path = '/img/list/' . $filename;
                    $processed_content = str_replace("[img]{$index}[/img]", "[img]{$web_path}[/img]", $processed_content);
                } else {
                    // 如果文件不存在，保留原标记或替换为占位符
                    error_log("警告: imgList[{$index}] 文件不存在或上传错误");
                }
            }
        } else {
            error_log("警告: 没有收到内容图片文件");
        }
    }



    // 1. 插入帖子主表
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, title, section_id, status, created_at) 
        VALUES (?, ?, ?, 4, NOW())
    ");
    $stmt->execute([$user_id, $title, $section_id]);
    $post_id = $pdo->lastInsertId();

    // 2. 处理图片上传
    $image_path = null;
    $width = null;
    $height = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $project_root = $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']);
        $upload_dir = __DIR__ . '/../../public/img/post/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 验证文件类型
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('不支持的文件类型，仅支持 JPEG, PNG, GIF, WebP 格式');
        }

        // 验证文件大小（20MB限制）
        if ($_FILES['image']['size'] > 20 * 1024 * 1024) {
            throw new Exception('文件大小不能超过20MB');
        }

        // 生成唯一文件名
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('post_') . '.' . $file_extension;
        $image_path = $upload_dir . $filename;

        // 移动文件
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            throw new Exception('文件上传失败');
        }

        // 获取图片尺寸
        list($width, $height) = getimagesize($image_path);
        $image_path = '/img/post/' . $filename;
    }else{
        $image_path = '/img/post/back.jpeg';
        $width = 1280;
        $height = 665;
    }

    // 3. 插入帖子内容表
    $stmt = $pdo->prepare("
        INSERT INTO post_contents (post_id, content_text, image_path, width, height) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$post_id, $processed_content, $image_path, $width ?? null, $height ?? null]);

    // 提交事务
    $pdo->commit();

    // 返回成功响应
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '发帖成功',
        'post' => [
            'post_id' => $post_id,
            'title' => $title,
            'section_id' => $section_id,
            'image_path' => $upload_dir,
            'content' => $processed_content,
        ]
    ]);

} catch (Exception $e) {
    // 回滚事务
    if (isset($pdo)) {
        $pdo->rollBack();
    }

    // 删除已上传的文件（如果有）
    if (isset($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    http_response_code(500);
    error_log("发帖错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '发帖失败: ' . $e->getMessage()]);
}
?>
