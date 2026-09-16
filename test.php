<?php

/**
 * Requirements for this endpoint:
 * 1) App must be in Live mode and approved for the "Meta oEmbed Read" feature.
 * 2) Use access_token in the form APP_ID|CLIENT_TOKEN (not app secret).
 * 3) In Dev mode, only admin/developer/tester accounts work.
 */
// ⚠️ Do NOT hardcode secrets in code. Prefer environment variables.
$app_id = getenv('META_APP_ID') ?: '1310103137345062';
// Use CLIENT TOKEN here, **not** app secret. Set META_CLIENT_TOKEN in your env.
$client_token = getenv('META_CLIENT_TOKEN') ?: 'REPLACE_WITH_CLIENT_TOKEN';
$token = $app_id.'|'.$client_token; // Required for Meta oEmbed Read

$post_url = 'https://www.instagram.com/p/DJTBYsJSG2P/';
$api_url = 'https://graph.facebook.com/v19.0/instagram_oembed?omitscript=true&url='.urlencode($post_url).'&access_token='.urlencode($token);

// 使用 cURL 執行請求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);

// 錯誤處理與回應碼顯示
if (curl_errno($ch)) {
    echo '❌ cURL 錯誤: '.curl_error($ch);
} else {
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo '<p>HTTP 回應碼: '.$http_code.'</p>';
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo '<pre>Raw response:\n'.htmlspecialchars($response).'</pre>';
    } else {
        echo '<pre>';
        print_r($decoded);
        echo '</pre>';
    }
    if ($http_code === 400) {
        echo '<p>Hint: A 400 with code 10 usually means your app lacks the "Meta oEmbed Read" approval or is not in Live mode.</p>';
    }
}
curl_close($ch);
