<?php
// /ticket/includes/admin_header.php
// 在admin_header.php中添加
//header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline';");

function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>

<!-- includes/admin_header.php -->
<!-- Font Awesome CSS -->
<link href="/ticket/assets/css/admin.css" rel="stylesheet">
<link href="/ticket/assets/css/all.min.css" rel="stylesheet">
<link href="/ticket/assets/css/bootstrap.min.css" rel="stylesheet">
<link href="/ticket/assets/fonts/bootstrap-icons.css" rel="stylesheet">
<!--<script src="/ticket/assets/js/bootstrap.bundle.min.js"></script>-->
<!-- 确保加载了Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>