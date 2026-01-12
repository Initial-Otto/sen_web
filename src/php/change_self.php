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

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的JSON数据']);
    exit();
}

// 验证必需字段 - 放宽条件，只要有任意字段就可以
if (!isset($input['username']) && !isset($input['email']) && !isset($input['avatar_path']) && !isset($input['account'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '至少需要修改一个字段: username, email, account 或 avatar_path']);
    exit();
}

$user_id = $_SESSION['user_id'];
$username = isset($input['username']) ? trim($input['username']) : null;
$email = isset($input['email']) ? trim($input['email']) : null;
$avatar_path = isset($input['avatar_path']) ? trim($input['avatar_path']) : null;
$account = isset($input['account']) ? trim($input['account']) : null;

// 移除严格的格式验证，只进行基本验证
if ($username !== "" && empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '用户名不能为空']);
    exit();
}

if ($email !== "" && empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '邮箱不能为空']);
    exit();
}

if ($account !== "" && empty($account)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '账号不能为空']);
    exit();
}

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 检查邮箱是否已被其他用户使用（如果修改邮箱）
    if ($email !== "") {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '该邮箱已被其他用户使用']);
            exit();
        }
    }

    // 检查用户名是否已被其他用户使用（如果修改用户名）
    if ($username !== "") {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '该用户名已被其他用户使用']);
            exit();
        }
    }

    // 检查账号是否已被其他用户使用（如果修改账号）
    if ($account !== "") {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE account = ? AND user_id != ?");
        $stmt->execute([$account, $user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '该账号已被其他用户使用']);
            exit();
        }
    }

    // 构建动态更新SQL
    $update_fields = [];
    $params = [];

    if ($username !== "") {
        $update_fields[] = "username = ?";
        $params[] = $username;
        $_SESSION['username'] = $username; // 更新session中的用户名
    }

    if ($email !== "") {
        $update_fields[] = "email = ?";
        $params[] = $email;
    }

    if ($avatar_path !== "") {
        $update_fields[] = "avatar_path = ?";
        $params[] = $avatar_path;
    }

    if ($account !== "") {
        $update_fields[] = "account = ?";
        $params[] = $account;
    }

    $params[] = $user_id; // WHERE条件参数

    if (count($update_fields) > 0) {
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 获取更新后的用户信息
        $stmt = $pdo->prepare("SELECT user_id, account, username, email, experience, level, avatar_path FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => '个人简介更新成功',
            'user' => $updated_user
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '没有需要更新的字段']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '服务器内部错误，请稍后重试']);
}
?>