<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        try {
            // Konfigurasi SMTP - Ganti dengan detail SMTP Anda
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com'; // Untuk Gmail
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'your-email@gmail.com'; // Email Anda
            $this->mail->Password   = 'your-app-password'; // App Password Gmail Anda
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Pengaturan email
            $this->mail->setFrom('your-email@gmail.com', 'KostHub');
            $this->mail->isHTML(true);
        } catch (Exception $e) {
            error_log("Mailer Error: {$this->mail->ErrorInfo}");
        }
    }
    
    public function sendResetToken($email, $token) {
        try {
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Reset Password - KostHub';
            
            // Template email yang menarik
            $this->mail->Body = $this->getEmailTemplate($token);
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email tidak bisa dikirim. Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    private function getEmailTemplate($token) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { background-color: white; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; color: #28a745; font-size: 24px; margin-bottom: 20px; }
                .token-box { background-color: #f8f9fa; border: 2px dashed #28a745; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .token { font-size: 32px; font-weight: bold; color: #28a745; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🏠 KostHub - Reset Password</h2>
                </div>
                
                <p>Halo,</p>
                <p>Anda telah meminta untuk mereset password akun KostHub Anda. Gunakan kode verifikasi berikut:</p>
                
                <div class='token-box'>
                    <div class='token'>$token</div>
                </div>
                
                <p><strong>Penting:</strong></p>
                <ul>
                    <li>Kode ini berlaku selama <strong>15 menit</strong></li>
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
