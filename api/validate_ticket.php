<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 数据库配置
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

// 启用错误报告用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 模拟响应用于测试（正式环境应移除）
// echo json_encode(['success' => true, 'message' => 'Test response', 'ticket' => ['code' => 'TEST123', 'status' => 'Valid', 'welcome_message' => 'Welcome!']]);
// exit;

$response = ['success' => false, 'message' => 'Initialization error'];


// 支持的语言
$supported_langs = ['en', 'zh'];
$lang = isset($_GET['lang']) && in_array($_GET['lang'], $supported_langs) ? $_GET['lang'] : 'en';

// 语言包
$messages = [
    'en' => [
        'invalid_request' => 'Invalid request',
        'ticket_not_found' => 'Ticket not found',
        'ticket_used' => 'Ticket already used on {time}',
        'ticket_valid' => 'Ticket is valid',
        'scan_success' => 'Scan successful',
        'welcome' => 'Welcome to our event!'
    ],
    'zh' => [
        'invalid_request' => '无效请求',
        'ticket_not_found' => '未找到票证',
        'ticket_used' => '票证已于 {time} 使用',
        'ticket_valid' => '票证有效',
        'scan_success' => '扫描成功',
        'welcome' => '欢迎参加我们的活动!'
    ]
];

try {
    // 获取原始POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 记录接收到的数据（调试用）
    file_put_contents('api_log.txt', date('Y-m-d H:i:s')." - Received: ".$input.PHP_EOL, FILE_APPEND);

    if (!$data || !isset($data['ticket_code'])) {
        $response['message'] = 'Invalid request data';
        echo json_encode($response);
        exit;
    }


    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $ticket_code = $data['ticket_code'];

    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['ticket_code'])) {
            echo json_encode(['success' => false, 'message' => $messages[$lang]['invalid_request']]);
            exit;
        }

        $ticket_code = $data['ticket_code'];

        // 查询票证
        $stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_code = :ticket_code");
        $stmt->bindParam(':ticket_code', $ticket_code);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => $messages[$lang]['ticket_not_found']]);
            exit;
        }

        // 检查票证是否已使用
        if ($ticket['is_used']) {
            $used_time = date('Y-m-d H:i:s', strtotime($ticket['used_at']));
            $message = str_replace('{time}', $used_time, $messages[$lang]['ticket_used']);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        // 标记为已使用
        $update = $conn->prepare("UPDATE tickets SET is_used = TRUE, used_at = NOW() WHERE ticket_id = :ticket_id");
        $update->bindParam(':ticket_id', $ticket['ticket_id']);
        $update->execute();

        echo json_encode([
            'success' => true,
            'message' => $messages[$lang]['scan_success'],
            'ticket' => [
                'code' => $ticket['ticket_code'],
                'status' => $messages[$lang]['ticket_valid'],
                'welcome_message' => $messages[$lang]['welcome']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $messages[$lang]['invalid_request']]);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    $response['message'] = 'System error: ' . $e->getMessage();
}

// 记录响应数据（调试用）
file_put_contents('api_log.txt', date('Y-m-d H:i:s')." - Response: ".json_encode($response).PHP_EOL, FILE_APPEND);

echo json_encode($response);

?>