<?php
namespace BricekaInc\Trimd;

use Telegram\Bot\Api;

class TelegramBot {
    protected $telegram;

    public function __construct($apiKey) {
        $this->telegram = new Api($apiKey);
    }

    // Function to send a message to a user
    public function sendMessage($chatId, $message) {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message
        ]);
    }

    // Function to get updates (messages)
    public function getUpdates() {
        return $this->telegram->getUpdates();
    }
}
