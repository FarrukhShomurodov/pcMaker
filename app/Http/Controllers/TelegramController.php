<?php

namespace App\Http\Controllers;

use App\Models\BotUser;
use App\Services\TelegramService;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramController extends Controller
{
    protected Api $telegram;
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegram = new Api(config('telegram.bot_token'));
        $this->telegramService = $telegramService;
    }

    public function handleWebhook(): void
    {
        $update = $this->telegram->getWebhookUpdates();
        $this->telegram->commandsHandler(true);

        // Обработка сообщений
        if ($update->has('message')) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            $user = BotUser::firstOrCreate(['chat_id' => $chatId]);
//            $user->update(['uname' => $message->from->username]);

            if ($text == '/start') {
                $user->update(['step' => 'choose_language']);

                $keyboard = [
                    ["🇷🇺 Русский", "🇺🇿 O'zbekcha"]
                ];

                $reply_markup = Keyboard::make([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ]);

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Пожалуйста, выберите язык.\n\nIltimos, tilni tanlang.",
                    'reply_markup' => $reply_markup
                ]);
                return;
            }

            $this->telegramService->processMessage($chatId, $text, $user->step, $message);
        }

        // Обработка callback_query
        if ($update->has('callback_query')) {
            $callbackQuery = $update->getCallbackQuery();
            $chatId = $callbackQuery->getMessage()->getChat()->getId();
            $data = $callbackQuery->getData();
            $this->telegramService->processCallbackQuery($chatId, $data, $callbackQuery);
        }
    }

}
