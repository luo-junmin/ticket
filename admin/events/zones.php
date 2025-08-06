<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Event.php';
// 生成随机 nonce
$nonce = base64_encode(random_bytes(16));
$_SESSION['csp_nonce'] = $nonce;

// 设置 CSP 头部
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; img-src 'self' data:; style-src 'self' 'unsafe-inline'");

$event = new Event();
$events = $event->getAllEvents();

// 检查是否有特定事件ID
$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// 获取选定活动的标题
$selected_event_title = '';
if ($selected_event_id > 0) {
    foreach ($events as $event) {
        if ($event['event_id'] == $selected_event_id) {
            $selected_event_title = htmlspecialchars($event['title']) . ' (' . date('Y-m-d', strtotime($event['event_date'])) . ')';
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zone Management | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Zone Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/ticket/admin/events/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Event Management
                    </a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Select activity</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="zones.php">
                                <div class="mb-3">
                                    <select class="form-select" name="event_id" id="eventSelector">

<!--                                    <select class="form-select" name="event_id" onchange="this.form.submit()">-->
                                        <option value="">-- Select activity --</option>
                                        <?php foreach ($events as $event): ?>
                                            <option value="<?= $event['event_id'] ?>"
                                                <?= $selected_event_id == $event['event_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($event['title']) ?> (<?= date('Y-m-d', strtotime($event['event_date'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($selected_event_id > 0): ?>
                <div class="alert alert-info">
                    Current Events: <strong><?= $selected_event_title ?></strong>
<!--                    <strong>--><?php //= htmlspecialchars($event->getEventTitle($selected_event_id)) ?><!--</strong>-->
                    <a href="zones_manage.php?event_id=<?= $selected_event_id ?>" class="btn btn-sm btn-primary ms-3">
                        <i class="bi bi-gear"></i> Managing Zones
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Please select an activity from above to manage its area</div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script nonce="<?php echo $nonce; ?>">
// <script>
    document.getElementById('eventSelector').addEventListener('change', function() {
        // 找到最近的form元素
        let form = this.closest('form');
        if (form) {
            form.submit();
        } else {
            // 如果没有form元素，创建一个临时form
            form = document.createElement('form');
            form.method = 'GET';
            form.action = window.location.pathname;

            // 添加所有查询参数
            const params = new URLSearchParams(window.location.search);
            params.set('event_id', this.value);

            // 添加隐藏input来保持其他参数
            params.forEach((value, key) => {
                if (key !== 'event_id') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
            });

            document.body.appendChild(form);
            form.submit();
        }
    });
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_footer.php'; ?>
</body>
</html>