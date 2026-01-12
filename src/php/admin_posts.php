<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 数据库配置
$db_host = 'localhost';
$db_name = 'forum_db';
$db_user = 'root';
$db_pass = 'root';

// 获取动作参数
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // 连接数据库
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 根据动作执行相应操作
    switch ($action) {
        case 'get_posts':
            getPosts($pdo);
            break;
        case 'approve_post':
            approvePost($pdo);
            break;
        case 'reject_post':
            rejectPost($pdo);
            break;
        case 'set_top':
            setTopPost($pdo);
            break;
        case 'set_normal':
            setNormalPost($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}

// 获取帖子
function getPosts($pdo) {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

    // 基础查询
    $sql = "SELECT p.*, u.username, pc.content_text 
            FROM posts p 
            LEFT JOIN users u ON p.user_id = u.user_id 
            LEFT JOIN post_contents pc ON p.post_id = pc.post_id 
            WHERE p.status != 3"; // 排除已删除的帖子

    // 根据筛选条件添加WHERE子句
    switch ($filter) {
        case 'pending':
            $sql .= " AND p.status = 4"; // 待审核
            break;
        case 'top':
            $sql .= " AND p.status = 1"; // 已置顶
            break;
        case 'all':
        default:
            $sql .= " AND p.status = 2"; // 只显示正常帖子
            break;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'posts' => $posts]);
}

// 审核通过帖子
function approvePost($pdo) {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的帖子ID']);
        return;
    }

    $sql = "UPDATE posts SET status = 2 WHERE post_id = ? AND status = 4";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$post_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '帖子审核通过成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '帖子不存在或状态不符合要求']);
    }
}

// 拒绝帖子
function rejectPost($pdo) {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的帖子ID']);
        return;
    }

    $sql = "UPDATE posts SET status = 3 WHERE post_id = ? AND status = 4";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$post_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '帖子已拒绝并删除']);
    } else {
        echo json_encode(['success' => false, 'message' => '帖子不存在或状态不符合要求']);
    }
}

// 设置置顶
function setTopPost($pdo) {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的帖子ID']);
        return;
    }

    // 验证帖子存在且状态正常
    $check_sql = "SELECT post_id FROM posts WHERE post_id = ? AND status = 2";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$post_id]);

    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => '帖子不存在或状态不符合要求']);
        return;
    }

    $sql = "UPDATE posts SET status = 1 WHERE post_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$post_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => '帖子置顶成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '操作失败']);
    }
}

// 取消置顶
function setNormalPost($pdo) {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的帖子ID']);
        return;
    }

    $sql = "UPDATE posts SET status = 2 WHERE post_id = ? AND status = 1";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$post_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '帖子取消置顶成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '帖子不存在或状态不符合要求']);
    }
}
?>
