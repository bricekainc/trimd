<?php

require 'vendor/autoload.php';
use Dotenv\Dotenv;
use Telegram\Bot\Api;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get environment variables
$telegramApiKey = getenv('TELEGRAM_BOT_API_KEY');
$shortenerApiKey = getenv('URL_SHORTENER_API_KEY');

// Instantiate Telegram API
$telegram = new Api($telegramApiKey);

// Check if it's a command
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

// Function to shorten a URL
function shortenUrl($url) {
    global $shortenerApiKey;

    $apiUrl = "https://api.trimd.cc/shorten?url=" . urlencode($url) . "&api_key=" . $shortenerApiKey;
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    return $data['data']['shorturl'] ?? $url; // Return shortened URL or original if an error occurs
}
?>
