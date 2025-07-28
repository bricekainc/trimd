<?php

require 'vendor/autoload.php';
use Dotenv\Dotenv;
use Telegram\Bot\Api;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get environment variables
$telegramApiKey = getenv('TELEGRAM_BOT_API_KEY');
$shortenerApiKey = getenv('URL_SHORTENER_API_KEY'); // Unused here as we use proxy

// Instantiate Telegram API
$telegram = new Api($telegramApiKey);

// Check for updates (messages from users)
$updates = $telegram->getUpdates();

foreach ($updates as $update) {
    $message = $update->getMessage();
    $chatId = $message->getChat()->getId();
    $text = $message->getText();
    $forwarded = $message->getForwardFrom();

    if ($message->hasText()) {
        if (filter_var($text, FILTER_VALIDATE_URL)) {
            // User sent a URL, shorten it
            $shortUrl = shortenUrl($text);
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Shortened URL: $shortUrl"
            ]);
        } elseif ($forwarded) {
            // Forwarded message with URLs, shorten them
            $links = extractUrls($text);
            $shortenedText = replaceUrlsWithShortened($text, $links);
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $shortenedText
            ]);
        }
    }
}

// Function to extract URLs from text
function extractUrls($text) {
    preg_match_all('/https?\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $text, $matches);
    return $matches[0];
}

// Function to replace URLs with shortened ones
function replaceUrlsWithShortened($text, $urls) {
    foreach ($urls as $url) {
        $shortUrl = shortenUrl($url);
        $text = str_replace($url, $shortUrl, $text);
    }
    return $text;
}

// Function to shorten a URL using the proxy method
function shortenUrl($url) {
    // Proxy endpoint for shortening URLs
    $proxyUrl = "https://corsproxy.io/?https://trimd.cc/shorten";

    $body = new URLSearchParams();
    $body->append('url', $url);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $proxyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body->toString());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Execute cURL and get the response
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse response
    $data = json_decode($response, true);

    return $data['data']['shorturl'] ?? $url; // Return shortened URL or original if an error occurs
}
?>
