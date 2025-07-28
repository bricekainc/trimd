<?php

require 'vendor/autoload.php';
use Telegram\Bot\Api;

// Get environment variables directly (no need for .env file)
$telegramApiKey = getenv('TELEGRAM_BOT_API_KEY');

// If the API key is missing, handle the error
if (!$telegramApiKey) {
    die("Error: TELEGRAM_BOT_API_KEY is not set.");
}

// Instantiate Telegram API
$telegram = new Api($telegramApiKey);

// Define a function to send a welcome message
function sendWelcomeMessage($telegram, $chatId) {
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Hello! I'm your URL Shortener bot. I can shorten URLs that you send me or those in forwarded messages. Just send me a URL, or forward a message with a link, and I'll shorten it for you. For more features, check out [Trimd](https://trimd.cc)."
    ]);
}

// Check for updates (messages from users)
$updates = $telegram->getUpdates();

foreach ($updates as $update) {
    $message = $update->getMessage();
    $chatId = $message->getChat()->getId();
    $text = $message->getText();
    $forwarded = $message->getForwardFrom();

    // If it's the first time the bot is receiving a message, send the welcome message
    if ($message->isCommand() && $message->getText() == "/start") {
        sendWelcomeMessage($telegram, $chatId);
    }

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
            if (empty($links)) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "I couldn't detect any links in this forwarded message. Please forward a message containing a link, or send me a link directly."
                ]);
            } else {
                $shortenedText = replaceUrlsWithShortened($text, $links);
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $shortenedText
                ]);
            }
        } else {
            // No valid link found in the user's message
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "I couldn't detect a link in your message. Please send a link or forward a message containing a link, and I'll shorten it for you. Also, check out [Trimd](https://trimd.cc) for more features!"
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
