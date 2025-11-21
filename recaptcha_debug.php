<?php
/**
 * reCAPTCHA Debugging Page
 * Use this to test reCAPTCHA configuration
 */

$recaptcha_secret = '6LcH0hAsAAAAAHQMpu0W5r0mZZI-Ylw3nr43yp4q';
$recaptcha_sitekey = '6LcH0hAsAAAAAF5K3G_IlRndhaA3DB4BIm11oehR';

// Test verification if form submitted
$verification_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    
    // Try cURL first
    if (function_exists('curl_init')) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $recaptcha_verify_url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query(['secret' => $recaptcha_secret, 'response' => $recaptcha_token]),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ));
        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);
        $verification_result = ['method' => 'cURL', 'response' => json_decode($response, true), 'error' => $curl_error];
    } else {
        // Fallback to file_get_contents
        $response = @file_get_contents($recaptcha_verify_url . '?secret=' . urlencode($recaptcha_secret) . '&response=' . urlencode($recaptcha_token));
        $verification_result = ['method' => 'file_get_contents', 'response' => json_decode($response, true), 'error' => null];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reCAPTCHA Debug - iOrganize</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .status { margin: 20px 0; padding: 15px; border-radius: 4px; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .details { background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #3498db; }
        .details code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        form { margin: 20px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2980b9; }
        .g-recaptcha { margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: bold; }
        .mono { font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 reCAPTCHA Configuration Debug</h1>
        
        <div class="status info">
            <strong>ℹ️ This page helps diagnose reCAPTCHA issues.</strong>
            <p>Fill out the form below to test if reCAPTCHA is working correctly on your server.</p>
        </div>

        <!-- Configuration Status -->
        <div class="details">
            <h3>📋 Configuration Status</h3>
            <table>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Site Key</td>
                    <td><code class="mono"><?php echo substr($recaptcha_sitekey, 0, 10) . '...' . substr($recaptcha_sitekey, -5); ?></code></td>
                    <td><?php echo $recaptcha_sitekey ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td>Secret Key</td>
                    <td><code class="mono"><?php echo substr($recaptcha_secret, 0, 10) . '...' . substr($recaptcha_secret, -5); ?></code></td>
                    <td><?php echo $recaptcha_secret ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td>cURL Available</td>
                    <td><?php echo function_exists('curl_init') ? 'Yes' : 'No'; ?></td>
                    <td><?php echo function_exists('curl_init') ? '✅' : '⚠️'; ?></td>
                </tr>
                <tr>
                    <td>SSL Verification</td>
                    <td><?php echo ini_get('curl.cainfo') ? 'Configured' : 'Default'; ?></td>
                    <td>ℹ️</td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo PHP_VERSION; ?></td>
                    <td>ℹ️</td>
                </tr>
            </table>
        </div>

        <!-- Test Form -->
        <div class="details">
            <h3>🧪 Test reCAPTCHA</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Click the checkbox below and submit:</label>
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_sitekey); ?>"></div>
                </div>
                <button type="submit">Test Verification</button>
            </form>
        </div>

        <!-- Verification Result -->
        <?php if ($verification_result): ?>
            <div class="details">
                <h3>📊 Verification Result</h3>
                <p><strong>Method Used:</strong> <?php echo htmlspecialchars($verification_result['method']); ?></p>
                
                <?php if ($verification_result['error']): ?>
                    <div class="status error">
                        <strong>Error:</strong> <?php echo htmlspecialchars($verification_result['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($verification_result['response']['success'])): ?>
                    <div class="status <?php echo $verification_result['response']['success'] ? 'success' : 'error'; ?>">
                        <strong><?php echo $verification_result['response']['success'] ? '✅ Success' : '❌ Failed'; ?></strong>
                        <p>reCAPTCHA verification <?php echo $verification_result['response']['success'] ? 'passed' : 'failed'; ?></p>
                    </div>
                    
                    <div class="details" style="margin-top: 15px;">
                        <h4>Full Response:</h4>
                        <pre class="mono"><?php echo json_encode($verification_result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                    </div>
                <?php else: ?>
                    <div class="status error">
                        <strong>Invalid Response</strong>
                        <p>Server did not return a valid reCAPTCHA response. Check network/SSL issues.</p>
                        <pre class="mono"><?php echo json_encode($verification_result['response'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Troubleshooting Guide -->
        <div class="details">
            <h3>🔧 Troubleshooting</h3>
            <h4>If reCAPTCHA doesn't show grid puzzles:</h4>
            <ul style="margin-left: 20px;">
                <li><strong>Localhost/Local IP:</strong> Google reCAPTCHA v2 may not show challenges on localhost. Test with a real domain or add IP to allowlist in Google Cloud Console.</li>
                <li><strong>High Trust Score:</strong> If your IP/account has low risk, Google won't show challenges. This is normal and expected behavior.</li>
                <li><strong>Wrong Keys:</strong> Ensure Site Key matches the keys configured in <code>login.php</code>.</li>
                <li><strong>Domain Mismatch:</strong> The domain where the form is displayed must match the domain registered in Google Cloud Console.</li>
                <li><strong>SSL/Network Issues:</strong> Check browser console (F12) for CORS or SSL errors.</li>
                <li><strong>Browser Cache:</strong> Clear browser cache and cookies, then reload the page.</li>
            </ul>
        </div>

        <div class="details">
            <h3>ℹ️ How to Register/Update reCAPTCHA Keys</h3>
            <ol style="margin-left: 20px;">
                <li>Go to <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin Console</a></li>
                <li>Select your website or create a new site</li>
                <li>Choose reCAPTCHA v2 (Checkbox)</li>
                <li>Add your domain(s) to the allowlist</li>
                <li>Copy the Site Key and Secret Key</li>
                <li>Update <code>login.php</code> with new keys</li>
            </ol>
        </div>
    </div>

    <script>
        // Log reCAPTCHA API status
        document.addEventListener('DOMContentLoaded', function() {
            console.log('grecaptcha available:', typeof grecaptcha !== 'undefined');
            if (typeof grecaptcha !== 'undefined') {
                console.log('reCAPTCHA API loaded successfully');
            }
        });
    </script>
</body>
</html>
