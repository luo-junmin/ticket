<?php
// generate_qr.php
require '../vendor/autoload.php'; // 引入 Composer 的 QR 码生成库
use Endroid\QrCode\QrCode;

function generateQRCode($ticket_code)
{
    $qr = new QrCode($ticket_code);
    $path = 'qrcodes/' . $ticket_code . '.png';
    file_put_contents($path, $qr->writeString());
    return $path;
}

echo generateQRCode("TICKET-B9425738-30-1-1");