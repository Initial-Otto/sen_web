<?php
// login.php
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

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['account']) || !isset($input['pw'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必需字段: account, pw']);
    exit();
}

$account = trim($input['account']);
$password = $input['pw'];

// 基础验证
if (empty($account) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '账号和密码不能为空']);
    exit();
}

try {
    // 连接数据库 - 请根据您的实际配置修改
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查询用户信息：根据account字段查询（可能是邮箱或用户名）
    $stmt = $pdo->prepare("SELECT user_id, account, username, email, password_hash, experience, level, avatar_path FROM users WHERE account = ? OR email = ?");
    $stmt->execute([$account,$account]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // 登录成功
        session_start();
        $baseUrl = "http://localhost:8081";
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['account'] = $user['account'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['level'] = $user['level'];
        $_SESSION['experience'] = $user['experience'];
        $avatarUrl = $user['avatar_path'] ? $baseUrl . '/' . ltrim($user['avatar_path'], '/') : null;
        $_SESSION['avatar_path'] = $avatarUrl;
        $user['avatar_path'] = $avatarUrl;

         // 根据你的实际域名修改


        session_write_close(); // 强制写入会话数据
        // 返回用户信息（密码哈希除外）
        unset($user['password_hash']);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'user' => $user,
            'login_time' => time()
        ]);
    } else {
        // 登录失败
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '账号或密码错误']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '服务器内部错误，请稍后重试']);
}
?>
