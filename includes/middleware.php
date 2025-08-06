<?php
function verifyCsrfMiddleware() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid CSRF token']);
            } else {
                echo 'Invalid CSRF token';
            }
            exit;
        }
    }
}