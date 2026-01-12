<?php
// check_login.php - 检查用户登录状态
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:8081"); // 你的前端地址
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
try{
// 检查用户是否已登录
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // 用户已登录，返回用户信息
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'account' => $_SESSION['account'] ?? '',
                'level' => $_SESSION['level'] ?? 0,
                'experience' => $_SESSION['experience'] ?? 0,
                'email' => $_SESSION['email'] ?? '',
                'avatar_path' => $_SESSION['avatar_path'] ?? '',
            ]
        ]);
    } else {
        // 用户未登录
        $_SESSION = array();

        // 如果需要，可以销毁会话cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 销毁会话
        session_destroy();

        // 返回未登录状态
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'message' => '用户未登录'
        ]);
    }
}catch (Exception $e) {
    // 错误处理
    error_log("检查登录状态错误: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'message' => '服务器错误，无法检查登录状态'
    ]);
}
?>
