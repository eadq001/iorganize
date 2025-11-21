<?php
require_once 'config/config.php';
require_once 'config/email.php';

// Test email sending
$test_email = 'eadq1999@gmail.com';
$test_code = '123456';

echo "<h1>Testing Email Configuration</h1>";
echo "<p>Sending test email to: <strong>$test_email</strong></p>";

// Send test email
$result = sendVerificationCode($test_email, $test_code);

if ($result) {
    echo "<p style='color: green; font-weight: bold;'>✓ Email sent successfully!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Failed to send email</p>";
    echo "<p>Check your PHP error logs for details.</p>";
}

echo "<hr>";
echo "<h2>Configuration Details:</h2>";
echo "<ul>";
echo "<li>SMTP Host: " . SMTP_HOST . "</li>";
echo "<li>SMTP Port: " . SMTP_PORT . "</li>";
echo "<li>SMTP User: " . SMTP_USER . "</li>";
echo "<li>SMTP Pass: " . (strpos(SMTP_PASS, '*') !== false ? SMTP_PASS : str_repeat('*', strlen(SMTP_PASS))) . "</li>";
echo "<li>From Email: " . FROM_EMAIL . "</li>";
echo "</ul>";

echo "<h2>Troubleshooting:</h2>";
echo "<ol>";
echo "<li><strong>Using regular Gmail password?</strong> You must use an <strong>App Password</strong> instead. Generate one at: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
echo "<li><strong>2FA not enabled?</strong> App Passwords only work if 2FA is enabled on your Google account.</li>";
echo "<li><strong>Check PHP error logs</strong> for detailed error messages from PHPMailer.</li>";
echo "</ol>";
?>
