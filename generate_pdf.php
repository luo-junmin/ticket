<?php
// generate_pdf.php
require_once $_SERVER['DOCUMENT_ROOT'].'/ticket/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/ticket/includes/autoload.php';

use Dompdf\Dompdf;

if (!isset($_GET['order_id'])) {
    die('订单ID缺失');
}

$orderId = (int)$_GET['order_id'];
$ticket = new Ticket();
$tickets = $ticket->getTicketsByOrder($orderId, $_SESSION['user_id']);

if (empty($tickets)) {
    die('找不到票务信息');
}

ob_start();
include $_SERVER['DOCUMENT_ROOT'].'/ticket/templates/pdf_ticket.php';
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
$pdfPath = $_SERVER['DOCUMENT_ROOT'].PUBLIC_PATH.'/assets/tickets/order_'.$orderId.'.pdf';

// 确保目录存在
if (!file_exists(dirname($pdfPath))) {
    mkdir(dirname($pdfPath), 0755, true);
}

file_put_contents($pdfPath, $output);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="tickets_'.$orderId.'.pdf"');
echo $output;