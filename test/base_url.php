<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

function getBaseUrl2($option='host') {
    // 判断是否为 HTTPS
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

    // 获取主机名
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];

    if ($option == 'host') {
        return $protocol . $host;
    } else {
        // 获取脚本路径（处理子目录情况）
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;

        return $protocol . $host . $basePath;
    }
}

$baseUrl = getBaseUrl2("");
// 示例输出: https://example.com 或 http://localhost/myapp
echo "<br>".$baseUrl;
$baseUrl = getBaseUrl2();
echo "<br>".$baseUrl;
echo "<br>".SITE_URL;
echo "<br>".$_SERVER['DOCUMENT_ROOT'];
echo "<br>".PUBLIC_PATH;
echo "<br>".UPLOADS_PATH;
