<?php

namespace App\Services\Telegram;

use App\Models\AdminAssembly;
use App\Models\BasketItem;
use App\Models\BotUser;
use App\Models\Component;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class Basket
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
    }

    private function getUserBasketByTypeAndId($type, $id)
    {
        $botUser = BotUser::where('chat_id', $id)->first();
        if (!$botUser) {
            return null;
        }

        $basket = \App\Models\Basket::where('bot_user_id', $botUser->id)->first();
        if (!$basket) {
            return null;
        }

        // Получаем товар из корзины по типу и id
        switch ($type) {
            case 'product':
                return BasketItem::where('basket_id', $basket->id)
                    ->where('product_id', $id)
                    ->first();

            case 'component':
                return BasketItem::where('basket_id', $basket->id)
                    ->where('component_id', $id)
                    ->first();

            case 'admin_assembly':
                return BasketItem::where('basket_id', $basket->id)
                    ->where('admin_assembly_id', $id)
                    ->first();

            default:
                return null;
        }
    }

    private function addProductToBasket(
        $chatId,
        $productId = null,
        $componentId = null,
        $adminAssemblyId = null,
        $callbackQuery
    ) {
        $itemType = null;
        $item = null;

        if ($adminAssemblyId) {
            $item = AdminAssembly::find($adminAssemblyId);
            $itemType = 'admin_assembly';
        } elseif ($productId) {
            $item = Product::find($productId);
            $itemType = 'product';
        } elseif ($componentId) {
            $item = Component::find($componentId);
            $itemType = 'component';
        } else {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => 'Произошла ошибка. Повторите попытку.',
            ]);
            return;
        }

        if (!$item) {
            $errorMessages = [
                'admin_assembly' => 'Сборка админа не найдена.',
                'product' => 'Продукт не найден.',
                'component' => 'Компонент не найден.',
            ];

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $errorMessages[$itemType] ?? 'Произошла ошибка.',
            ]);
            return;
        }

        $botUser = BotUser::where('chat_id', $chatId)->first();
        if (!$botUser) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден! 😕'
            ]);
            return;
        }

        $basket = \App\Models\Basket::firstOrCreate(['bot_user_id' => $botUser->id]);

        $basketItem = BasketItem::where('basket_id', $basket->id)
            ->where($itemType . '_id', $item->id)
            ->first();

        if ($basketItem) {
            // Проверка доступного количества
            if ($itemType === 'product' && $item->quantity < ($basketItem->product_count + 1)) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'К сожалению, вы превысили доступное количество этого товара.',
                    'show_alert' => true
                ]);
                return;
            } elseif ($itemType === 'component' && $item->quantity < ($basketItem->component_count + 1)) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'К сожалению, вы превысили доступное количество этого компонента.',
                    'show_alert' => true
                ]);
                return;
            } else {
                if (in_array($itemType, ['component', 'product'])) {
                    $basketItem->increment($itemType . '_count');
                }
            }
        } else {
            if (in_array($itemType, ['component', 'product'])) {
                BasketItem::create([
                    'basket_id' => $basket->id,
                    $itemType . '_id' => $item->id,
                    $itemType . '_count' => 1,
                    'price' => $item->price,
                ]);
            } else { // admin_assembly
                BasketItem::create([
                    'basket_id' => $basket->id,
                    $itemType . '_id' => $item->id,
                    'price' => $item->price,
                ]);
            }
        }

        $this->updateBasketTotalPrice($basket->id, $chatId, $callbackQuery);
    }

    private function removeProductFromBasket(
        $chatId,
        $productId = null,
        $componentId = null,
        $adminAssemblyId = null,
        $callbackQuery
    ) {
        $botUser = BotUser::where('chat_id', $chatId)->first();
        if (!$botUser) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден! 😕'
            ]);
            return;
        }

        $basket = Basket::where('bot_user_id', $botUser->id)->first();

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина пуста.'
            ]);
            return;
        }

        $itemType = null;
        $id = null;

        if ($adminAssemblyId) {
            $itemType = 'admin_assembly';
            $id = $adminAssemblyId;
        } elseif ($productId) {
            $itemType = 'product';
            $id = $productId;
        } elseif ($componentId) {
            $itemType = 'component';
            $id = $componentId;
        }

        if (!$itemType || !$id) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => 'Произошла ошибка. Повторите попытку.',
                'show_alert' => true
            ]);
            return;
        }

        $basketItem = BasketItem::where('basket_id', $basket->id)
            ->where($itemType . '_id', $id)
            ->first();

        if ($basketItem) {
            if (($itemType === 'product' && $basketItem->product_count > 1) ||
                ($itemType === 'component' && $basketItem->component_count > 1)
            ) {
                $basketItem->decrement($itemType . '_count');
            } else {
                $basketItem->delete();
            }
        }

        $this->updateBasketTotalPrice($basket->id, $chatId, $callbackQuery);
    }

    private function updateBasketTotalPrice($basketId, $chatId, $callbackQuery)
    {
        $basketItems = BasketItem::where('basket_id', $basketId)->get();
        $totalPrice = $basketItems->sum(function ($item) {
            $itemTotal = $item->price;
            if ($item->product_count) {
                $itemTotal *= $item->product_count;
            } elseif ($item->component_count) {
                $itemTotal *= $item->component_count;
            } elseif ($item->admin_assembly_id) {
                $itemTotal *= 1;
            }
            return $itemTotal;
        });

        $basket = Basket::find($basketId);
        $basket->update(['total_price' => $totalPrice]);

        $this->updateBasketMessage($chatId, $basket, $callbackQuery);
    }

    private function updateBasketMessage($chatId, $basket, $callbackQuery)
    {
        $basketItems = BasketItem::where('basket_id', $basket->id)->get();
        $totalPrice = $basket->total_price;
        $inlineKeyboard = [];

        foreach ($basketItems as $item) {
            if ($item->product_id) {
                $inlineKeyboard[] = [
                    ['text' => '-', 'callback_data' => 'remove:product:' . $item->product_id],
                    ['text' => $item->product_count, 'callback_data' => 'current:product:' . $item->product_id],
                    ['text' => '+', 'callback_data' => 'add:product:' . $item->product_id],
                ];
            }

            if ($item->component_id) {
                $inlineKeyboard[] = [
                    ['text' => '-', 'callback_data' => 'remove:component:' . $item->component_id],
                    ['text' => $item->component_count, 'callback_data' => 'current:component:' . $item->component_id],
                    ['text' => '+', 'callback_data' => 'add:component:' . $item->component_id],
                ];
            }

            if ($item->admin_assembly_id) {
                $inlineKeyboard[] = [
                    ['text' => 'Удалить', 'callback_data' => 'remove:admin_assembly:' . $item->admin_assembly_id],
                ];
            }
        }


        $keyboard = Keyboard::make(['inline_keyboard' => $inlineKeyboard]);

        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery->getMessage()->getMessageId(),
            'text' => "🛍️ Ваша корзина\n\nТекущая стоимость: $totalPrice сум",
            'reply_markup' => $keyboard,
        ]);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Корзина обновлена.',
        ]);
    }


    private function basketItems($chatId)
    {
        $botUser = BotUser::where('chat_id', $chatId)->first();

        if (!$botUser) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден! 😕'
            ]);
            return;
        }

        $basket = $botUser->basket()->with('basketItems')->first();

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина не найдена! 😕'
            ]);
            return;
        }

        $basketItems = $basket->basketItems()->get();

        if ($basketItems->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина пуста! 😕'
            ]);
            return;
        }

        $inlineKeyboard = [];
        $messageText = "🛍️ Ваша корзина:\n\n";
        $mediaGroup = [];

        foreach ($basketItems as $basketItem) {
            if ($basketItem->product_id) {
                $product = Product::find($basketItem->product_id);
                if ($product) {
                    $messageText .= "💻 *{$product->name}*\n"
                        . "🔧 *Бренд:* _{$product->brand}_\n"
                        . "💵 *Цена:* *{$product->price} сум*\n"
                        . "📦 *В наличии:* _{$product->quantity} шт._\n"
                        . "📊 *Количество:* {$basketItem->product_count}\n\n";

                    $photos = json_decode($product->photos, true);
                    if (!empty($photos) && is_array($photos)) {
                        foreach ($photos as $photo) {
                            $photoPath = Storage::url('public/' . $photo);
                            $fullPhotoUrl = env('APP_URL') . $photoPath;

                            $mediaGroup[] = [
                                'type' => 'photo',
                                'media' => $fullPhotoUrl,
                            ];
                        }
                    }

                    $inlineKeyboard[] = [
                        ['text' => '-', 'callback_data' => 'remove:product:' . $product->id],
                        ['text' => $basketItem->product_count, 'callback_data' => 'current:product:' . $product->id],
                        ['text' => '+', 'callback_data' => 'add:product:' . $product->id],
                    ];
                }
            }

            if ($basketItem->component_id) {
                $component = Component::find($basketItem->component_id);
                if ($component) {
                    $messageText .= "🔧 *{$component->name}*\n"
                        . "🔧 *Бренд:* _{$component->brand}_\n"
                        . "💵 *Цена:* *{$component->price} сум*\n"
                        . "📦 *В наличии:* _{$component->quantity} шт._\n"
                        . "📊 *Количество:* {$basketItem->component_count}\n\n";

                    $photos = json_decode($component->photos, true);
                    if (!empty($photos) && is_array($photos)) {
                        foreach ($photos as $photo) {
                            $photoPath = Storage::url('public/' . $photo);
                            $fullPhotoUrl = env('APP_URL') . $photoPath;

                            $mediaGroup[] = [
                                'type' => 'photo',
                                'media' => $fullPhotoUrl,
                            ];
                        }
                    }

                    $inlineKeyboard[] = [
                        ['text' => '-', 'callback_data' => 'remove:component:' . $component->id],
                        [
                            'text' => $basketItem->component_count,
                            'callback_data' => 'current:component:' . $component->id
                        ],
                        ['text' => '+', 'callback_data' => 'add:component:' . $component->id],
                    ];
                }
            }

            if ($basketItem->admin_assembly_id) {
                $adminAssembly = AdminAssembly::find($basketItem->admin_assembly_id);
                if ($adminAssembly) {
                    $messageText .= "*{$adminAssembly->title}*\n"
                        . "{$adminAssembly->description}\n"
                        . "💵 *Цена:* *{$adminAssembly->price} сум*\n\n";

                    $photos = json_decode($adminAssembly->photos, true);
                    if (!empty($photos) && is_array($photos)) {
                        foreach ($photos as $photo) {
                            $photoPath = Storage::url('public/' . $photo);
                            $fullPhotoUrl = env('APP_URL') . $photoPath;

                            $mediaGroup[] = [
                                'type' => 'photo',
                                'media' => $fullPhotoUrl,
                            ];
                        }
                    }

                    $inlineKeyboard[] = [
                        ['text' => 'Удалить', 'callback_data' => 'remove:admin_assembly:' . $adminAssembly->id],
                    ];
                }
            }
        }

        if (!empty($mediaGroup)) {
            $this->telegram->sendMediaGroup([
                'chat_id' => $chatId,
                'media' => json_encode($mediaGroup),
            ]);
        }

        $messageText .= "🛍️ *Общая стоимость:* *{$basket->total_price} сум*";

        $inlineKeyboard[] = [
            ['text' => 'Оформить', 'callback_data' => 'confirm_basket_items_' . $basket->id],
        ];

        $keyboard = Keyboard::make(['inline_keyboard' => $inlineKeyboard]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $messageText,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);
    }
}
