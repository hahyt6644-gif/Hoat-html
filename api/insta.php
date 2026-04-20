<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$username = $_GET['username'] ?? 'mrbeast';

// 1. MASSIVE PROXY POOL (9 Total Proxies)
$proxies = [
    // --- UAE OwlProxies (SOCKS5) ---
    ['id' => 'UAE_SOCKS_946', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'D1j47nCoeV40_custom_zone_AE_st__city_sid_94694348_time_5', 'pass' => '2537493', 'type' => CURLPROXY_SOCKS5_HOSTNAME],
    ['id' => 'UAE_SOCKS_098', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'D1j47nCoeV40_custom_zone_AE_st__city_sid_47987098_time_5', 'pass' => '2537493', 'type' => CURLPROXY_SOCKS5_HOSTNAME],

    // --- NEW US OwlProxies (SOCKS5) ---
    ['id' => 'US_SOCKS_3512', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'UwQYjY8pLd50_custom_zone_US_st__city_sid_04703512_time_5', 'pass' => '2537559', 'type' => CURLPROXY_SOCKS5_HOSTNAME],
    ['id' => 'US_SOCKS_9568', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'UwQYjY8pLd50_custom_zone_US_st__city_sid_04379568_time_5', 'pass' => '2537559', 'type' => CURLPROXY_SOCKS5_HOSTNAME],
    ['id' => 'US_SOCKS_5448', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'UwQYjY8pLd50_custom_zone_US_st__city_sid_66285448_time_5', 'pass' => '2537559', 'type' => CURLPROXY_SOCKS5_HOSTNAME],
    ['id' => 'US_SOCKS_5858', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'UwQYjY8pLd50_custom_zone_US_st__city_sid_78495858_time_5', 'pass' => '2537559', 'type' => CURLPROXY_SOCKS5_HOSTNAME],
    ['id' => 'US_SOCKS_6448', 'host' => 'change4.owlproxy.com', 'port' => 7778, 'user' => 'UwQYjY8pLd50_custom_zone_US_st__city_sid_90206448_time_5', 'pass' => '2537559', 'type' => CURLPROXY_SOCKS5_HOSTNAME],

    // --- US HTTP Proxies ---
    ['id' => 'US_HTTP_PURE', 'host' => 'px015601.pointtoserver.com', 'port' => 10780, 'user' => 'purevpn0s4863210', 'pass' => 'cq4naylqyfc1', 'type' => CURLPROXY_HTTP],
    ['id' => 'US_HTTP_MODESTO', 'host' => '136.179.19.164', 'port' => 3128, 'user' => 'harishankarchoubey', 'pass' => 'HvCjWdoIrK6szj8v', 'type' => CURLPROXY_HTTP]
];

// Shuffle to ensure we use different IPs every request
shuffle($proxies);

$finalData = null;
$workingProxyId = "None";
$attempts = [];

// 2. RETRY LOOP
foreach ($proxies as $proxy) {
    $url = "https://www.instagram.com/api/v1/users/web_profile_info/?username=" . urlencode($username);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Set Proxy
    curl_setopt($ch, CURLOPT_PROXY, $proxy['host']);
    curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'] . ":" . $proxy['pass']);
    curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['type']);
    
    // Headers (Using Mobile Android String for better guest access)
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36",
        "X-IG-App-ID: 1217981644879628", // Android App ID (Higher Trust than Web)
        "X-ASBD-ID: 129477",
        "Accept: */*",
        "X-Requested-With: XMLHttpRequest",
        "Referer: https://www.instagram.com/$username/"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $decoded = json_decode($response, true);
        if (isset($decoded['data']['user'])) {
            $finalData = $decoded;
            $workingProxyId = $proxy['id'];
            break; 
        }
    }
    $attempts[] = ["id" => $proxy['id'], "code" => $httpCode, "error" => $curlError];
}

// 3. EXTRACTION
if ($finalData) {
    $user = $finalData['data']['user'];
    $reels = [];
    $seen = [];
    
    $sources = [
        $user['edge_clips_video_timeline'] ?? null,
        $user['edge_owner_to_timeline_media'] ?? null
    ];
    
    foreach ($sources as $source) {
        if (!$source) continue;
        foreach ($source['edges'] as $edge) {
            $node = $edge['node'];
            if ($node['is_video'] && !isset($seen[$node['shortcode']])) {
                $reels[] = [
                    'url' => "https://www.instagram.com/reels/" . $node['shortcode'] . "/",
                    'timestamp' => $node['taken_at_timestamp']
                ];
                $seen[$node['shortcode']] = true;
            }
        }
    }
    
    usort($reels, function($a, $b) { return $b['timestamp'] <=> $a['timestamp']; });

    echo json_encode([
        "status" => "success",
        "username" => $username,
        "proxy_used" => $workingProxyId,
        "total_found" => count($reels),
        "reels" => array_column($reels, 'url')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "All 9 proxies failed to fetch data. Instagram is likely requiring a login for this username.",
        "debug_log" => $attempts
    ], JSON_PRETTY_PRINT);
}
