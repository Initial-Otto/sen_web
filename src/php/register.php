<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允许POST请求']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['account']) || !isset($input['pw']) || !isset($input['comPw'])) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必需字段: account, pw, comPw']);
    exit();
}

$account = trim($input['account']);
$password = $input['pw'];
$confirmPassword = $input['comPw'];

if (empty($account) || empty($password) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode(['error' => '所有字段都不能为空']);
    exit();
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => '密码长度至少8位']);
    exit();
}

if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    http_response_code(400);
    echo json_encode(['error' => '密码必须包含字母和数字']);
    exit();
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => '两次输入的密码不一致']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=forum_db;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 判断输入是否为邮箱格式
    $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL) !== false;

    // 检查账号/邮箱是否已存在
    if ($isEmail) {
        // 输入的是邮箱：同时检查username和email字段
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE account = ? OR email = ?");
        $stmt->execute([$account, $account]);
    } else {
        // 输入的不是邮箱：只检查username字段
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE account = ?");
        $stmt->execute([$account]);
    }

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => '该账号已被注册']);
        exit();
    }

    // 生成随机用户名
    $randomUsername = generateRandomUsername();

    // 确保用户名唯一
    $maxRetries = 5;
    $retryCount = 0;
    while ($retryCount < $maxRetries) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$randomUsername]);
        if (!$stmt->fetch()) {
            break;
        }
        $randomUsername = generateRandomUsername();
        $retryCount++;
    }

    if ($retryCount >= $maxRetries) {
        $randomUsername = $randomUsername . '_' . rand(1000, 9999);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $defaultAvatar = 'img/head/1(1).jpeg';

    // 根据输入类型决定数据插入方式
    if ($isEmail) {
        // 输入的是邮箱：同时设置username和email
        $stmt = $pdo->prepare("INSERT INTO users (account, username, email, password_hash, avatar_path, experience, level) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $account,
            $randomUsername,
            $account, // 邮箱作为email字段
            $passwordHash,
            $defaultAvatar,
            0,
            0
        ]);
    } else {
        // 输入的不是邮箱：只设置username，email为NULL
        $stmt = $pdo->prepare("INSERT INTO users (account, username, email, password_hash, avatar_path, experience, level) VALUES (?, ?, NULL, ?, ?, ?, ?)");
        $stmt->execute([
            $account,
            $randomUsername,
            $passwordHash,
            $defaultAvatar,
            0,
            0
        ]);
    }

    http_response_code(200);
    echo json_encode([
        'success' => '注册成功',
        'account' => $account,
        'username' => $randomUsername,
        'avatar' => $defaultAvatar,
        'input_type' => $isEmail ? 'email' : 'account'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode(['error' => '服务器内部错误，请稍后重试']);
}

/**
 * 生成随机用户名函数
 */
function generateRandomUsername() {
    $adjectives = ['Happy', 'Clever', 'Brave', 'Swift', 'Gentle', 'Witty', 'Calm', 'Lucky', 'Proud', 'Smart'];
    $nouns = ['Tiger', 'Eagle', 'Lion', 'Dragon', 'Wolf', 'Fox', 'Bear', 'Hawk', 'Falcon', 'Panther'];
    $numbers = rand(100, 999);

    $adjective = $adjectives[array_rand($adjectives)];
    $noun = $nouns[array_rand($nouns)];

    return $adjective . $noun . $numbers;
}
?>