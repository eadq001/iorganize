<?php
/**
 * Email Configuration
 * PHPMailer setup for sending verification codes and emails
 */

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'eadq1999@gmail.com');
define('SMTP_PASS', 'odbp cuaw jyyi ilrb');
define('FROM_EMAIL', 'eadq1999@gmail.com');
define('FROM_NAME', 'iOrganize');

/**
 * Send verification code via email
 */
function sendVerificationCode($email, $code) {
    try {
        // Check if SMTP credentials are configured
        if (SMTP_USER === 'your-gmail@gmail.com' || SMTP_PASS === 'your-app-password') {
            error_log("SMTP credentials not configured in config/email.php");
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code - ' . SITE_NAME;
        $mail->Body = getVerificationEmailTemplate($code);
        $mail->AltBody = "Your verification code is: $code. This code will expire in 15 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = "PHPMailer Error: " . $e->getMessage();
        error_log($errorMsg);
        logSecurityEvent('email_failed', "Failed to send verification code to: $email - " . $e->getMessage(), 'medium');
        return false;
    }
}

/**
 * Generate HTML email template
 */
function getVerificationEmailTemplate($code) {
    return "<div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>" . SITE_NAME . "</h1>
        </div>
        <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #ddd;'>
            <h2 style='color: #333; font-size: 20px; margin-top: 0;'>Email Verification</h2>
            <p style='color: #666; font-size: 16px; line-height: 1.6;'>Thank you for signing up! Please use the code below to verify your email address.</p>
            <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px solid #667eea;'>
                <p style='margin: 0; font-size: 14px; color: #999; text-transform: uppercase; letter-spacing: 2px;'>Your Verification Code</p>
                <p style='margin: 10px 0 0 0; font-size: 36px; font-weight: bold; color: #667eea; font-family: monospace; letter-spacing: 5px;'>$code</p>
            </div>
            <p style='color: #999; font-size: 12px; line-height: 1.6;'><strong>This code will expire in 15 minutes.</strong><br>If you did not create this account, please ignore this email.</p>
            <p style='color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;'>" . SITE_NAME . " - Do not reply to this email</p>
        </div>
    </div>";
}

/**
 * Generate random verification code
 */
function generateVerificationCode($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Send welcome email after successful registration
 */
function sendWelcomeEmail($email, $username) {
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . SITE_NAME . '!';
        $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>" . SITE_NAME . "</h1>
            </div>
            <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #ddd;'>
                <h2 style='color: #333; font-size: 20px; margin-top: 0;'>Welcome, $username!</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Your account has been successfully created. You can now login and start organizing your life with " . SITE_NAME . ".</p>
                <a href='" . SITE_URL . "/login.php' style='display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; margin: 20px 0; font-weight: bold;'>Go to Login</a>
                <p style='color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;'>" . SITE_NAME . " - Do not reply to this email</p>
            </div>
        </div>";
        $mail->AltBody = "Welcome to " . SITE_NAME . "! You can now login to your account.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

