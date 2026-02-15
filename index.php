<?php
function redirectTo($url) {
    header("Location: $url");
    exit;
}

function getDeviceType() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

    if (strpos($userAgent, 'mobile') !== false) {
        // Check for specific tablet keywords
        if (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'tablet';
        }
        return 'phone';
    }
    return 'desktop';
}

// Determine device type and redirect
$deviceType = getDeviceType();

switch ($deviceType) {
    case 'phone':
        redirectTo('UHMU/login_M.html');
        break;
    case 'tablet':
        redirectTo('UHMU/login_T.html');
        break;
    case 'desktop':
        redirectTo('UHMU/login_D.html');
        break;
    default:
        break;
}
?>