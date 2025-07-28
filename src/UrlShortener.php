<?php
namespace BricekaInc\Trimd;

class UrlShortener {
    // Function to shorten a URL
    public function shortenUrl($url) {
        $proxyUrl = "https://corsproxy.io/?https://trimd.cc/shorten";
        $body = new URLSearchParams();
        $body->append('url', $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $proxyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body->toString());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return $data['data']['shorturl'] ?? $url;
    }
}
