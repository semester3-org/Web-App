<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        try {
            $this->mail->isSMTP();
            $this->mail->Host = MAIL_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = MAIL_USERNAME;
            $this->mail->Password = MAIL_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = MAIL_PORT;
            $this->mail->Timeout = 15;
            
            $this->mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            throw new Exception("Email Service Error: " . $e->getMessage());
        }
    }
    
    public function sendResetCode($email, $name, $code) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $name);
            $this->mail->Subject = 'Kode Reset Password KostHub';
            $this->mail->Body = $this->getEmailTemplate($name, $code);
            $this->mail->AltBody = "Kode verifikasi Anda: {$code}. Kode ini berlaku selama 10 menit.";
            
            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Gagal mengirim email: " . $e->getMessage());
        }
    }
    
    private function getEmailTemplate($name, $code) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { background-color: white; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; color: #28a745; font-size: 24px; margin-bottom: 20px; }
                .code-box { background-color: #f8f9fa; border: 2px dashed #28a745; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .code { font-size: 36px; font-weight: bold; color: #28a745; letter-spacing: 8px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🏠 KostHub - Reset Password</h2>
                </div>
                
                <p>Halo, <strong>{$name}</strong></p>
                <p>Anda telah meminta untuk mereset password akun KostHub Anda. Gunakan kode verifikasi berikut:</p>
                
                <div class='code-box'>
                    <div class='code'>{$code}</div>
                </div>
                
                <p><strong>Penting:</strong></p>
                <ul>
                    <li>Kode ini berlaku selama <strong>10 menit</strong></li>
                    <li>Jangan bagikan kode ini kepada siapa pun</li>
                    <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                </ul>
                
                <div class='footer'>
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                    <p>&copy; 2024 KostHub. Semua hak dilindungi.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>