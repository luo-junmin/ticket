<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//use TCPDF; // 引入 TCPDF 类
//echo "-- 1 --<br>";
//include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/assets/phpqrcode/qrlib.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/libs/phpqrcode/phpqrcode.php'; // 添加这行

class Ticket {
    private $pdo;
    private $lang;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->lang = Language::getInstance();
    }

    public function getUserOrders($userId)
    {
        // Get order details
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM orders 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $orderDetails = $stmt->fetchAll();

        if (empty($orderDetails)) {
            return false;
        }
        return $orderDetails;
    }

    public function generateTickets($orderId) {
        // Get order details
        $stmt = $this->pdo->prepare("
            SELECT od.*, z.zone_name, z.zone_category
            FROM order_details od
            JOIN ticket_zones z ON od.zone_id = z.zone_id
            WHERE od.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $orderDetails = $stmt->fetchAll();

        if (empty($orderDetails)) {
            return false;
        }

        // Generate tickets
        foreach ($orderDetails as $detail) {
            for ($i = 0; $i < $detail['quantity']; $i++) {
                $ticketCode = $this->generateTicketCode($orderId, $detail['zone_id'], $i);
                $qrCodePath = $this->generateQRCode($ticketCode);

                $stmt = $this->pdo->prepare("
                    INSERT INTO tickets (order_id, ticket_code, qr_code_path) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$orderId, $ticketCode, $qrCodePath]);
            }
        }

        return true;
    }

    private function generateTicketCode($orderId, $zoneId, $index) {
        return 'TICKET-' . strtoupper(bin2hex(random_bytes(4))) . '-' .
            $orderId . '-' . $zoneId . '-' . ($index + 1);
    }

    private function generateQRCode($ticketCode) {
        $qrCodePath = '/assets/qrcodes/' . $ticketCode . '.png';
//        $fullPath = __DIR__ . '/../public' . $qrCodePath;
        $fullPath = __DIR__ . "/../.." . PUBLIC_PATH . $qrCodePath;

        // Create directory if not exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Generate QR code
        QRcode::png($ticketCode, $fullPath, QR_ECLEVEL_L, 10);

        return $qrCodePath;
    }

    public function getTicketsByOrder($orderId, $userId = null) {
        $sql = "
            SELECT t.*, e.title, e.event_date, e.location, 
                   z.zone_name, z.zone_category, od.price_per_ticket,
                   d.name AS discount_name
            FROM tickets t
            JOIN orders o ON t.order_id = o.order_id
            JOIN events e ON o.event_id = e.event_id
            JOIN order_details od ON o.order_id = od.order_id
            JOIN ticket_zones z ON od.zone_id = z.zone_id
            LEFT JOIN discount_types d ON od.discount_id = d.discount_id
            WHERE t.order_id = ?
        ";

        if ($userId) {
            $sql .= " AND o.user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId, $userId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId]);
        }

        return $stmt->fetchAll();
    }

    public function verifyTicket($ticketCode) {
        $this->pdo->beginTransaction();

        try {
            // Lock ticket record
            $stmt = $this->pdo->prepare("
                SELECT * FROM tickets 
                WHERE ticket_code = ? 
                FOR UPDATE
            ");
            $stmt->execute([$ticketCode]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => $this->lang->get('invalid_ticket')];
            }

            if ($ticket['is_used']) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => $this->lang->get('ticket_used') . ': ' .
                        date('Y-m-d H:i', strtotime($ticket['used_at']))
                ];
            }

            // Mark as used
            $stmt = $this->pdo->prepare("
                UPDATE tickets 
                SET is_used = TRUE, used_at = NOW() 
                WHERE ticket_id = ?
            ");
            $stmt->execute([$ticket['ticket_id']]);

            $this->pdo->commit();
            return ['success' => true, 'ticket' => $ticket];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    public function sendConfirmationEmail($orderId, $email) {
        $tickets = $this->getTicketsByOrder($orderId);
        if (empty($tickets)) return false;

        $eventTitle = $tickets[0]['title'];
        $eventDate = date('Y-m-d H:i', strtotime($tickets[0]['event_date']));
        $location = $tickets[0]['location'];
        $qr_code_path = $tickets[0]['qr_code_path'];
        $ticketCount = count($tickets);


        // Create PDF tickets
        $pdfPath = $this->generatePdfTickets($orderId);

        // Send email
        include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = 'tls';
            $mail->Port = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_USER, SITE_NAME);
            $mail->addAddress($email);
            $mail->addReplyTo(ADMIN_EMAIL, SITE_NAME);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $this->lang->get('ticket_confirmation') . ' - Order #' . $orderId;

            $mail->Body = "
                <h1>" . $this->lang->get('thank_you') . "</h1>
                <p>" . $this->lang->get('order_confirmed') . " #$orderId.</p>
                
                <h2>" . $this->lang->get('event_details') . "</h2>
                <p><strong>" . $this->lang->get('event') . ":</strong> $eventTitle</p>
                <p><strong>" . $this->lang->get('date') . ":</strong> $eventDate</p>
                <p><strong>" . $this->lang->get('location') . ":</strong> $location</p>
                <p><strong>" . $this->lang->get('ticket_count') . ":</strong> $ticketCount</p>
                
                <h2>" . $this->lang->get('your_tickets') . "</h2>
                <img src=". __DIR__ . "/../.." . PUBLIC_PATH . $qr_code_path." >                
                <p>" . $this->lang->get('ticket_attachment') . "</p>
                <p>" . $this->lang->get('ticket_qr_instructions') . "</p>
                
                <p>" . $this->lang->get('contact_support') . "</p>
            ";

            // Attach PDF
            $mail->addAttachment($pdfPath, 'Tickets_Order_' . $orderId . '.pdf');

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    private function generatePdfTickets($orderId) {
        $tickets = $this->getTicketsByOrder($orderId);
        if (empty($tickets)) return false;

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(SITE_NAME);
        $pdf->SetAuthor(SITE_NAME);
        $pdf->SetTitle('Tickets for Order #' . $orderId);
        $pdf->SetSubject('Event Tickets');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);

        // Add a page
        $pdf->AddPage();

        // Generate ticket content
        foreach ($tickets as $ticket) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, $ticket['title'], 0, 1, 'C');

            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i', strtotime($ticket['event_date'])), 0, 1);
            $pdf->Cell(0, 10, 'Location: ' . $ticket['location'], 0, 1);
            $pdf->Cell(0, 10, 'Zone: ' . $ticket['zone_name'], 0, 1);
            $pdf->Cell(0, 10, 'Category: ' . strtoupper($ticket['zone_category']), 0, 1);
            $pdf->Cell(0, 10, 'Price: SGD ' . number_format($ticket['price_per_ticket'], 2), 0, 1);
            $pdf->Cell(0, 10, 'Ticket Code: ' . $ticket['ticket_code'], 0, 1);

            // Add QR code
//            $pdf->Image($_SERVER['DOCUMENT_ROOT'] .'/ticket/public' . $ticket['qr_code_path'], 140, $pdf->GetY(), 40, 40);
            $pdf->Image(__DIR__ . "/../.." . PUBLIC_PATH . $ticket['qr_code_path'], 140, $pdf->GetY(), 40, 40);

            $pdf->Ln(20);

            // Add page break if not last ticket
            if ($ticket !== end($tickets)) {
                $pdf->AddPage();
            }
        }

//        $pdfPath = $_SERVER['DOCUMENT_ROOT'] .'/ticket/public/assets/tickets/order_' . $orderId . '.pdf';
        $pdfPath = __DIR__ . "/../.." . PUBLIC_PATH.'/assets/tickets/order_' . $orderId . '.pdf';
        $pdf->Output($pdfPath, 'F');

        return $pdfPath;
    }
}