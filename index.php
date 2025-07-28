<?php

require 'vendor/autoload.php';
use BricekaInc\Trimd\TelegramBot;
use BricekaInc\Trimd\UrlShortener;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get environment variables
$telegramApiKey = getenv('TELEGRAM_BOT_API_KEY');

// Instantiate TelegramBot and UrlShortener
$telegramBot = new TelegramBot($telegramApiKey);
$urlShortener = new UrlShortener();

// Send a welcome message when the bot is first started
$telegramBot->sendMessage($chatId, "Hello! I'm your URL Shortener bot. I can shorten URLs that you send me or those in forwarded messages. Just send me a URL, or forward a message with a link, and I'll shorten it for you. For more features, check out [Trimd](https://trimd.cc).");

// Check for updates (messages from users)
$updates = $telegramBot->getUpdates();

foreach ($updates as $update) {
    $message = $update->getMessage();
    $chatId = $message->getChat()->getId();
    $text = $message->getText();
    $forwarded = $message->getForwardFrom();

    if ($message->hasText()) {
        if (filter_var($text, FILTER_VALIDATE_URL)) {
            // User sent a URL, shorten it
            $shortUrl = $urlShortener->shortenUrl($text);
            $telegramBot->sendMessage($chatId, "Shortened URL: $shortUrl");
        } elseif ($forwarded) {
            // Forwarded message with URLs, shorten them
            $links = extractUrls($text);
            if (empty($links)) {
                $telegramBot->sendMessage($chatId, "I couldn't detect any links in this forwarded message. Please forward a message containing a link, or send me a link directly.");
            } else {
                $shortenedText = replaceUrlsWithShortened($text, $links, $urlShortener);
                $telegramBot->sendMessage($chatId, $shortenedText);
            }
        } else {
            // No valid link found in the user's message
            $telegramBot->sendMessage($chatId, "I couldn't detect a link in your message. Please send a link or forward a message containing a link, and I'll shorten it for you. Also, check out [Trimd](https://trimd.cc) for more features!");
        }
    }
}

// Function to extract URLs from text
function extractUrls($text) {
    preg_match_all('/https?\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $text, $matches);
    return $matches[0];
}

// Function to replace URLs with shortened ones
function replaceUrlsWithShortened($text, $urls, $urlShortener) {
    foreach ($urls as $url) {
        $shortUrl = $urlShortener->shortenUrl($url);
        $text = str_replace($url, $shortUrl, $text);
    }
    return $text;
}
