<?php
// change_password.php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 处理预检请求
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

if (!isset($input['account']) || !isset($input['oldPw']) || !isset($input['pw']) || !isset($input['comPw'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必需字段: account, oldPw, pw, comPw']);
    exit();
}

$account = trim($input['account']);
$oldPassword = $input['oldPw'];
$newPassword = $input['pw'];
$confirmPassword = $input['comPw'];

// 基础验证
if (empty($account) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '所有字段都不能为空']);
    exit();
}

// 验证新密码长度
if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '新密码长度至少8位']);
    exit();
}

// 验证新密码复杂度
if (!preg_match('/[a-zA-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '新密码必须包含字母和数字']);
    exit();
}

// 验证确认密码
if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '两次输入的新密码不一致']);
    exit();
}

// 验证新旧密码是否相同
if ($oldPassword === $newPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '新密码不能与旧密码相同']);
    exit();
}

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查询用户信息
    $stmt = $pdo->prepare("SELECT user_id, account, username, password_hash FROM users WHERE account = ?");
    $stmt->execute([$account]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '账号不存在']);
        exit();
    }

    // 验证旧密码
    if (!password_verify($oldPassword, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '旧密码错误']);
        exit();
    }

    // 加密新密码
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // 更新密码
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $updateStmt->execute([$newPasswordHash, $user['user_id']]);

    if ($updateStmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => '密码修改成功']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '密码修改失败，请稍后重试']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '服务器内部错误，请稍后重试']);
}
?>
