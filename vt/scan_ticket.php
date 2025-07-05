<?php
/**
 * scan_ticket.php
 */
// 禁用HTML错误输出
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: X-API-KEY, X-CLIENT-IP, Content-Type");

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => '仅允许POST请求']));
}

// 获取客户端IP（考虑代理情况）
$clientIp = $_SERVER['HTTP_X_CLIENT_IP'] ??
    $_SERVER['HTTP_CLIENT_IP'] ??
    $_SERVER['HTTP_X_FORWARDED_FOR'] ??
    $_SERVER['REMOTE_ADDR'] ?? '';


$receivedKey = $_SERVER['HTTP_X_API_KEY'];
$clientIp = $_SERVER['HTTP_X_CLIENT_IP'] ?? '';
trigger_error("receivedKey ".$receivedKey);
trigger_error("clientIp ".$clientIp);

// 验证动态密钥
$expectedKey = hash('sha256', API_KEY . $clientIp);
trigger_error("expectedKey ".$expectedKey);

if (!hash_equals($expectedKey, $receivedKey)) {
    error_log("API密钥验证失败: 预期[$expectedKey] 实际[$receivedKey] IP[$clientIp]");
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid API key']));
}

// 手动输入的票号验证
$ticketCode = $_POST['ticket_code'] ?? '';
if (empty($ticketCode)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '缺少票号参数']);
    exit;
}


// 限制请求频率
//session_start();
$rateLimitKey = 'rate_limit_' . md5($_SERVER['REMOTE_ADDR']);
$currentTime = time();

if (isset($_SESSION[$rateLimitKey]) &&
    ($currentTime - $_SESSION[$rateLimitKey]['first_request']) < 60) {
    if ($_SESSION[$rateLimitKey]['count'] > 30) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many requests']);
        exit;
    }
    $_SESSION[$rateLimitKey]['count']++;
} else {
    $_SESSION[$rateLimitKey] = [
        'first_request' => $currentTime,
        'count' => 1
    ];
}

// 记录验票日志
function logValidation($ticketCode, $status, $ip) {
    $logEntry = sprintf(
        "[%s] %s - Ticket: %s - Status: %s - IP: %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'],
        $ticketCode,
        $status,
        $ip
    );
    file_put_contents('validation_log.txt', $logEntry, FILE_APPEND);
}

// 支持的语言设置
$supportedLangs = ['en', 'zh'];
$lang = isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs) ? $_GET['lang'] : 'en';

// 语言包
$messages = [
    'en' => [
        'invalid' => 'Invalid ticket',
        'used' => 'Ticket already used at {time}',
        'valid' => 'Ticket validated successfully',
        'error' => 'System error, please try again',
        'welcome' => 'Welcome to the event!'
    ],
    'zh' => [
        'invalid' => '无效票证',
        'used' => '票证已于 {time} 使用',
        'valid' => '票证验证成功',
        'error' => '系统错误，请重试',
        'welcome' => '欢迎参加活动！'
    ]
];

try {
    // 数据库配置

    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ticketCode = $_POST['ticket_code'] ?? '';

        // 使用预处理语句和事务
        $pdo->beginTransaction();

        // 查询票证
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_code = ?");
        $stmt->execute([$ticketCode]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            echo json_encode([
                'status' => 'invalid',
                'message' => $messages[$lang]['invalid']
            ]);
            logValidation($ticketCode, 'invalid', $_SERVER['REMOTE_ADDR']);
            $pdo->rollBack();
            exit;
        }

        if ($ticket['is_used']) {
            $usedTime = date('Y-m-d H:i:s', strtotime($ticket['used_at']));
            $message = str_replace('{time}', $usedTime, $messages[$lang]['used']);

            echo json_encode([
                'status' => 'used',
                'message' => $message,
                'used_at' => $ticket['used_at']
            ]);
            logValidation($ticketCode, 'used', $_SERVER['REMOTE_ADDR']);
            $pdo->rollBack();
           exit;
        }

        // 标记为已使用
        $update = $pdo->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE ticket_id = ?");
        $update->execute([$ticket['ticket_id']]);
        $pdo->commit();

        echo json_encode([
            'status' => 'valid',
            'message' => $messages[$lang]['valid'],
            'welcome' => $messages[$lang]['welcome'],
            'ticket_code' => $ticket['ticket_code']
        ]);
        logValidation($ticketCode, 'valid', $_SERVER['REMOTE_ADDR']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $messages[$lang]['error']
    ]);
} catch (Exception $e) {
    error_log('System error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $messages[$lang]['error']
    ]);
}

// 在验证完成后添加日志记录
//logValidation($ticketCode, $status, $_SERVER['REMOTE_ADDR']);

?>
