<?php

namespace App\Services;

use App\Models\AdminAssembly;
use App\Models\Assembly;
use App\Models\AssemblyComponent;
use App\Models\Basket;
use App\Models\BasketItem;
use App\Models\BotUser;
use App\Models\CategoryCompatibility;
use App\Models\Component;
use App\Models\ComponentCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\TypeCompatibility;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\InputMedia\InputMediaPhoto;

class TelegramService
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
    }

    public function processCallbackQuery($chatId, $data, $callbackQuery)
    {
        if (str_starts_with($data, 'sub_category_')) {
            $subCategoryId = str_replace('sub_category_', '', $data);
            $this->showProductsBySubCategory($chatId, $subCategoryId);
        }

        if (str_starts_with($data, 'confirm_assembly_')) {
            $assemblyId = str_replace('confirm_assembly_', '', $data);
            $this->assemblyConfirmation($chatId, $assemblyId);
        }

        if (str_starts_with($data, 'confirm_basket_items_')) {
            $basketId = str_replace('confirm_basket_items_', '', $data);
            $this->basketConfirmation($chatId, $basketId);
        }

        if (str_starts_with($data, 'delete_assembly_')) {
            $assemblyId = str_replace('delete_assembly_', '', $data);
            $this->deleteAssembly($chatId, $assemblyId, $callbackQuery);
        }

        if (str_starts_with($data, 'component_category_')) {
            $subCategoryId = str_replace('component_category_', '', $data);
            $this->showComponentsByCategory($chatId, $subCategoryId);
        }

        $parts = explode(':', $data);

        if (count($parts) < 2) {
            // $this->telegram->answerCallbackQuery([
            //     'callback_query_id' => $callbackQuery->getId(),
            //     'text' => 'Неверный формат данных.',
            //     'show_alert' => true
            // ]);
            return;
        }

        $action = $parts[0];
        $type = $parts[1];
        $id = isset($parts[2]) ? $parts[2] : null;

        switch ($action) {
            case 'add':
                $this->handleAddAction($chatId, $type, $id, $callbackQuery);
                break;

            case 'remove':
                $this->handleRemoveAction($chatId, $type, $id, $callbackQuery);
                break;

            case 'current':
                $this->handleCurrentAction($chatId, $type, $id, $callbackQuery);
                break;

            default:
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'Неизвестное действие.',
                    'show_alert' => true
                ]);
                break;
        }

    }

    private function handleCurrentAction($chatId, $type, $id, $callbackQuery)
    {
        $count = $this->getCurrentCount($type, $id);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Текущее количество: ' . $count,
            'show_alert' => true
        ]);
    }

    private function handleAddAction($chatId, $type, $id, $callbackQuery)
    {
        switch ($type) {
            case 'product':
                $this->addProductToBasket($chatId, $id, null, null, $callbackQuery);
                break;

            case 'component':
                $this->addProductToBasket($chatId, null, $id, null, $callbackQuery);
                break;

            case 'admin_assembly':
                $this->addProductToBasket($chatId, null, null, $id, $callbackQuery);
                break;

            default:
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'Неизвестный тип объекта.',
                    'show_alert' => true
                ]);
                break;
        }
    }

    private function handleRemoveAction($chatId, $type, $id, $callbackQuery)
    {
        switch ($type) {
            case 'product':
                $this->removeProductFromBasket($chatId, $id, null, null, $callbackQuery);
                break;

            case 'component':
                $this->removeProductFromBasket($chatId, null, $id, null, $callbackQuery);
                break;

            case 'admin_assembly':
                $this->removeProductFromBasket($chatId, null, null, $id, $callbackQuery);
                break;

            default:
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'Неизвестный тип объекта.',
                    'show_alert' => true
                ]);
                break;
        }
    }

    private function getCurrentCount($type, $id)
    {
        $basket = $this->getUserBasketByTypeAndId($type, $id);

        if (!$basket) {
            return 0;
        }

        switch ($type) {
            case 'product':
                return $basket->product_count ?? 0;

            case 'component':
                return $basket->component_count ?? 0;

            case 'admin_assembly':
                return $basket->admin_assembly_id ? 1 : 0; // Предполагается, что количество для сборок админа всегда 1

            default:
                return 0;
        }
    }

    public function processMessage($chatId, $text, $step, $message)
    {
        $commands = [
            '🛍️ Корзина' => 'basketItems',
            '💼 Выбрать сборку' => 'adminAssemblies',
            '🖥️ Собрать компьютер' => 'createAssembly',
            '🔧 Комплектующие' => 'showAdminCategory',
            '🧩 Мои сборки' => 'myAssembly',
            '⚙️ Настройки' => 'setting'
        ];

        if (array_key_exists($text, $commands)) {
            $this->{$commands[$text]}($chatId);
            return;
        }

        switch ($step) {
            case 'choose_language':
                $this->processLanguageChoice($chatId, $text);
                break;
            case 'request_phone':
                $this->processPhoneRequest($chatId, $message);
                break;
            case 'confirm_phone':
                $this->processPhoneConfirmation($chatId, $text);
                break;
            case 'request_name':
                $this->processNameRequest($chatId, $text);
                break;
            case 'select_category':
                $this->selectCategory($chatId, $text);
                break;
            case 'select_component':
                if ($text == 'Отменить') {
                    $this->cancelAssembly($chatId);
                } else if ($text === 'Назад') {
                    $lastCategory = $this->getPrevCategory($chatId);
                    if ($lastCategory) {
                        $this->selectCategory($chatId, $lastCategory->id);
                    } else {
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Что то пошло не так повторите попытку.'
                        ]);
                    }
                } else {
                    $this->selectComponent($chatId, $text);
                }
                break;
            case 'setting':
                if ($text == 'Назад') {
                    $this->showMainMenu($chatId);
                } elseif ($text == 'Язык'){
                    $keyboard = [
                        ["Русский", "O'zbekcha"],
                        ["Назад"]
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
                    $this->updateUserStep($chatId, 'change_lang');
                } elseif ($text == 'Полное имя'){
                    $keyboard = [
                        ["Назад"]
                    ];

                    $reply_markup = Keyboard::make([
                        'keyboard' => $keyboard,
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ]);

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Введите новое полное имя.",
                        'reply_markup' => $reply_markup
                    ]);
                    $this->updateUserStep($chatId, 'change_full_name');
                }

                break;
            case 'change_full_name':
                if ($text !== 'Назад') {
                    $this->changeUserFullName($chatId, $text);
                }

                $this->setting($chatId);
                break;
            case 'change_lang':
               if ($text == 'Русский' || $text == "O'zbekcha") {
                    $this->updateUserLang($chatId, $text == 'Русский' ? 'ru' : 'uz');
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Язык успешно изменен на $text."
                    ]);
                }
                $this->setting($chatId);
                break;
            case 'show_main_menu':
            case 'show_subcategory':
                $this->checkCategory($chatId, $text);
                break;
            default:
                $this->showMainMenu($chatId);
                break;
        }
    }

    // Auth
    private function processLanguageChoice($chatId, $text)
    {
        if ($text === '🇷🇺 Русский') {
            $this->updateUserLang($chatId, 'ru');
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Введите номер телефона",
                'reply_markup' => $this->requestPhoneKeyboard(),
            ]);
            $this->updateUserStep($chatId, 'request_phone');
        } elseif ($text === '🇺🇿 O‘zbekcha') {
            $this->updateUserLang($chatId, 'uz');
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Telefon raqamingizni kiriting",
                'reply_markup' => $this->requestPhoneKeyboard(),
            ]);
            $this->updateUserStep($chatId, 'request_phone');
        }
    }

    private function processPhoneRequest($chatId, $message)
    {
        if ($message->getContact()) {
            $phone = $message->getContact()->getPhoneNumber();
            $this->saveUserPhone($chatId, $phone);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Подтвердите, что этот номер правильный: $phone",
                'reply_markup' => $this->confirmationKeyboard(),
            ]);
            $this->updateUserStep($chatId, 'confirm_phone');
        }
    }

    private function processPhoneConfirmation($chatId, $text)
    {
        if ($text === 'Да') {
            $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "Введите Ф.И.О."]);
            $this->updateUserStep($chatId, 'request_name');
        } elseif ($text === 'Нет') {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Введите номер телефона еще раз",
                'reply_markup' => $this->requestPhoneKeyboard(),
            ]);
            $this->updateUserStep($chatId, 'request_phone');
        }
    }

    private function processNameRequest($chatId, $text)
    {
        $this->saveUserName($chatId, $text);
        $this->showMainMenu($chatId);
    }

    private function requestPhoneKeyboard()
    {
        return new Keyboard(['keyboard' => [[['text' => 'Отправить контакт', 'request_contact' => true]]], 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }

    private function confirmationKeyboard()
    {
        return new Keyboard(['keyboard' => [['Да', 'Нет']], 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }

    private function updateUserLang($chatId, $lang)
    {
        BotUser::where('chat_id', $chatId)->update(['lang' => $lang]);
    }

    private function saveUserPhone($chatId, $phone)
    {
        BotUser::where('chat_id', $chatId)->update(['phone_number' => $phone]);
    }

    private function saveUserName($chatId, $fullName)
    {
        BotUser::where('chat_id', $chatId)->update(['full_name' => $fullName]);
    }

    // Update user step
    private function updateUserStep($chatId, $step)
    {
        BotUser::query()->updateOrCreate(['chat_id' => $chatId], ['step' => $step]);
    }

    // Main menu
    private function showMainMenu($chatId)
    {
        $categories = ProductCategory::all();
        $buttons = $categories->map(fn($cat) => [['text' => $cat->name]])->toArray();
        $buttons[] = [
            ['text' => '🖥️ Собрать компьютер'],
            ['text' => '💼 Выбрать сборку']
        ];
        $buttons[] = [
            ['text' => '🔧 Комплектующие'],
            ['text' => '🛍️ Корзина']
        ];

        $buttons[] = [
            ['text' => '🧩 Мои сборки'],
            ['text' => '⚙️ Настройки'],
        ];

        $keyboard = new Keyboard(['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => false]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Главное меню",
            'reply_markup' => $keyboard,
        ]);

        $this->updateUserStep($chatId, 'show_main_menu');
    }

    // Category check
    private function checkCategory($chatId, $categoryName)
    {
        // Load categories with subCategories
        $categories = ProductCategory::with('subCategories')->where('name', $categoryName)->first();

        if (!$categories) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Категория не существует!'
            ]);
            return;
        }

        $subCategories = $categories->subCategories;

        if ($subCategories->isNotEmpty()) {
            $this->showSubCategories($chatId, $subCategories);
        } else {
            $this->showProducts($chatId, $categories->products);
        }
    }

    // Product
    private function showSubCategories($chatId, $subCategories)
    {
        $buttons = $subCategories->map(fn($subCat) => [
            [
                'text' => $subCat->name,
                'callback_data' => 'sub_category_' . $subCat->id
            ]
        ])->toArray();

        $keyboard = Keyboard::make(['inline_keyboard' => $buttons]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Выберите подкатегорию:",
            'reply_markup' => $keyboard,
        ]);

        $this->updateUserStep($chatId, 'show_subcategory');
    }

    private function showProductsBySubCategory($chatId, $subCategoryId)
    {
        $subCategory = ProductSubCategory::query()->find($subCategoryId);

        $products = $subCategory->products;

        if ($products->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'В этой подкатегории нет продуктов.'
            ]);
            return;
        } else {
            $this->showProducts($chatId, $products);
        }
    }

    private function showProducts($chatId, $products)
    {
        if ($products->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'В этой категории нет продуктов.'
            ]);
            return;
        }

        foreach ($products as $product) {
            $photos = json_decode($product->photos, true);

            $productDescription = $product->description ? "🔧 *Описание:* _{$product->description}_\n" : '';

            $description = "💻 *{$product->name}* 💻\n\n"
            . "🔧 *Бренд:* _{$product->brand}_\n"
            . $productDescription
                . "💵 *Цена:* *{$product->price} сум*\n"
                . "📦 *В наличии:* _{$product->quantity} шт._\n\n"
                . "⚡ _Идеальный выбор для вашего оборудования!_";

            $mediaGroup = [];
            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $index => $photo) {
                    $photoPath = Storage::url('public/' . $photo);
                    $fullPhotoUrl = env('APP_URL') . $photoPath;

                    $mediaGroup[] = [
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ];
                }

                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }

            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add:product:' . $product->id],
                ]
            ]]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛍️ Добавить в корзину",
                'reply_markup' => $keyboard,
            ]);
        }
    }

    private function getUserBasketByTypeAndId($type, $id)
    {
        // Получаем пользователя по chat_id
        $botUser = BotUser::where('chat_id', $id)->first(); // Предполагается, что id соответствует chat_id
        if (!$botUser) {
            return null;
        }

        // Получаем корзину пользователя
        $basket = Basket::where('bot_user_id', $botUser->id)->first();
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

    // Basket
    private function addProductToBasket($chatId, $productId = null, $componentId = null, $adminAssemblyId = null, $callbackQuery)
    {
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

        $basket = Basket::firstOrCreate(['bot_user_id' => $botUser->id]);

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

    private function removeProductFromBasket($chatId, $productId = null, $componentId = null, $adminAssemblyId = null, $callbackQuery)
    {
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
                $itemTotal *= 1; // Предполагается, что количество для сборок админа всегда 1
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
        

        // Создание клавиатуры с кнопками
        $keyboard = Keyboard::make(['inline_keyboard' => $inlineKeyboard]);

        // Обновление текста сообщения корзины
        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery->getMessage()->getMessageId(),
            'text' => "🛍️ Ваша корзина\n\nТекущая стоимость: $totalPrice сум",
            'reply_markup' => $keyboard,
        ]);

        // Ответ на callback_query
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Корзина обновлена.',
        ]);
    }


    private function basketItems($chatId)
    {
        // Получение пользователя по chat_id
        $botUser = BotUser::where('chat_id', $chatId)->first();

        if (!$botUser) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден! 😕'
            ]);
            return;
        }

        // Получение корзины пользователя вместе с товарами
        $basket = $botUser->basket()->with('basketItems')->first();

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина не найдена! 😕'
            ]);
            return;
        }

        // Получение товаров в корзине
        $basketItems = $basket->basketItems()->get();

        if ($basketItems->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина пуста! 😕'
            ]);
            return;
        }

        // Инициализация переменных для сообщения и клавиатуры
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

                    // Добавление фотографий продукта в медиа-группу
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

                    // Добавление кнопок управления количеством продукта
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

                    // Добавление фотографий компонента в медиа-группу
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

                    // Добавление кнопок управления количеством компонента
                    $inlineKeyboard[] = [
                        ['text' => '-', 'callback_data' => 'remove:component:' . $component->id],
                        ['text' => $basketItem->component_count, 'callback_data' => 'current:component:' . $component->id],
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

                    // Добавление фотографий сборки админа в медиа-группу
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

                    // Добавление кнопки удаления сборки админа из корзины
                    $inlineKeyboard[] = [
                        ['text' => 'Удалить', 'callback_data' => 'remove:admin_assembly:' . $adminAssembly->id],
                    ];
                }
            }
        }

        // Отправка группы медиа (фото), если есть фотографии
        if (!empty($mediaGroup)) {
            $this->telegram->sendMediaGroup([
                'chat_id' => $chatId,
                'media' => json_encode($mediaGroup),
            ]);
        }

        // Добавление общей стоимости в сообщение
        $messageText .= "🛍️ *Общая стоимость:* *{$basket->total_price} сум*";

        $inlineKeyboard[] = [
            ['text' => 'Оформить', 'callback_data' => 'confirm_basket_items_' . $basket->id],
        ];
        // Создание клавиатуры с кнопками
        $keyboard = Keyboard::make(['inline_keyboard' => $inlineKeyboard]);

        // Отправка или обновление сообщения с корзиной
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $messageText,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);
    }

    // Admin Assemblies
    private function adminAssemblies($chatId)
    {
        $adminAssemblies = AdminAssembly::all();

        if ($adminAssemblies->count() < 1) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Сборок админа нету в наличии.'
            ]);
            return;
        }

        foreach ($adminAssemblies as $adminAssembly) {
            $photos = json_decode($adminAssembly->photos, true);

            $description = "*{$adminAssembly->title}*\n\n"
            . "{$adminAssembly->description}\n\n"
            . "💵 *Цена:* *{$adminAssembly->price} сум*\n\n";

            $mediaGroup = [];
            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $index => $photo) {
                    $photoPath = Storage::url('public/' . $photo);
                    $fullPhotoUrl = env('APP_URL') . $photoPath;

                    $mediaGroup[] = [
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ];
                }

                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }

            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add:admin_assembly:' . $adminAssembly->id],
                ]
            ]]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛍️ Добавить в корзину",
                'reply_markup' => $keyboard,
            ]);
        }
    }

    // Component
    private function showAdminCategory($chatId)
    {
        $componentCategories = ComponentCategory::all();

        if ($componentCategories->count() < 1) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пусто 😕'
            ]);
            return;
        }

        $buttons = $componentCategories->map(fn($cat) => [
            [
                'text' => $cat->name,
                'callback_data' => 'component_category_' . $cat->id
            ]
        ])->toArray();

        $keyboard = Keyboard::make(['inline_keyboard' => $buttons]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Выберите категорию:",
            'reply_markup' => $keyboard,
        ]);

        $this->updateUserStep($chatId, 'show_component_category');
    }

    private function showComponentsByCategory($chatId, $categoryId)
    {
        $category = ComponentCategory::query()->find($categoryId);

        $components = $category->component;

        if ($components->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'В этой категории нет комплектуюших.'
            ]);
            return;
        } else {
            $this->showComponent($chatId, $components);
        }
    }

    private function showComponent($chatId, $components)
    {
        foreach ($components as $component) {
            $photos = json_decode($component->photos, true);

            $description = "💻 *{$component->name}* 💻\n\n"
            . "🔧 *Бренд:* _{$component->brand}_\n"
            . "💵 *Цена:* *{$component->price} сум*\n"
            . "📦 *В наличии:* _{$component->quantity} шт._\n\n"
            . "⚡ _Идеальный выбор для вашего оборудования!_";

            $mediaGroup = [];
            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $index => $photo) {
                    $photoPath = Storage::url('public/' . $photo);
                    $fullPhotoUrl = env('APP_URL') . $photoPath;

                    $mediaGroup[] = [
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ];
                }

                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }

            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add:component:' . $component->id],
                ]
            ]]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛍️ Добавить в корзину",
                'reply_markup' => $keyboard,
            ]);
        }
    }


    // User Assemblies
    private function createAssembly($chatId)
    {
        $firstCategory = ComponentCategory::query()->first();

        if (!$firstCategory) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Категории для выбора не найдены.",
            ]);
            $this->showMainMenu($chatId);
            return;
        }

        $user = BotUser::query()->where('chat_id', $chatId)->first();

        Assembly::create([
            'bot_user_id' => $user->id,
            'total_price' => 0
        ]);

        $this->updateUserStep($chatId, 'select_category');
        $this->selectCategory($chatId, $firstCategory->id, true);
    }

    private function cancelAssembly($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();

        Assembly::query()->where('bot_user_id', $user->id)->latest()->first()->delete();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Сборка отменина.'
        ]);

        $this->updateUserStep($chatId, 'show_main_menu');
        $this->showMainMenu($chatId);
    }

    private function selectCategory($chatId, $categoryId, $isFirst = false)
    {
        $components = Component::query()->where('component_category_id', $categoryId)
            ->where('quantity', '>', 0)
            ->get();

        if ($components->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Нет компонентов для выбранной категории.",
            ]);
            $this->showMainMenu($chatId);
            return;
        }

        $buttons = $components->map(fn($comp) => [['text' => $comp->name]])->toArray();

        $buttons[] = $isFirst
            ? [['text' => 'Отменить']]
            : [['text' => 'Назад'], ['text' => 'Отменить']];


        $keyboard = new Keyboard(['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
        $this->updateUserStep($chatId, 'select_component');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Выберите компонент для категории: " . ComponentCategory::query()->find($categoryId)->name,
            'reply_markup' => $keyboard,
        ]);
    }


    private function selectComponent($chatId, $component)
    {
        $component = Component::query()->where('name', $component)->first();

        if ($component == null) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Что то пошло не так повторите попытку.",
            ]);
            $this->showMainMenu($chatId);
            return;
        }

        if (!$this->checkCompatibility($chatId, $component)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Этот компонент несовместим с другими в сборке. Попробуйте выбрать другой.",
            ]);
            return;
        }

        $user = BotUser::query()->where('chat_id', $chatId)->first();

        $assembly = Assembly::where('bot_user_id', $user->id)->latest()->first();

        $assembly->total_price += $component->price;
        $assembly->save();

        AssemblyComponent::create([
            'assembly_id' => $assembly->id,
            'component_id' => $component->id
        ]);

        $nextCategory = $this->getNextCategory($chatId);
        if ($nextCategory) {
            $this->selectCategory($chatId, $nextCategory->id);
        } else {
            $this->completeAssembly($chatId);
        }
    }

    private function getNextCategory($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            return null;
        }
        $assembly = Assembly::query()->where('bot_user_id', $user->id)->latest()->first();
        if (!$assembly) {
            return ComponentCategory::query()->first();
        }

        $selectedCategoryIds = AssemblyComponent::query()->where('assembly_id', $assembly->id)
            ->join('components', 'assembly_components.component_id', '=', 'components.id')
            ->pluck('components.component_category_id');

        return ComponentCategory::whereNotIn('id', $selectedCategoryIds)->first();
    }

    private function getPrevCategory($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            return null;
        }

        $assembly = Assembly::query()->where('bot_user_id', $user->id)->latest()->first();

        if (!$assembly) {
            return null;
        }

        $selectedCategoryIds = AssemblyComponent::query()->where('assembly_id', $assembly->id)
            ->join('components', 'assembly_components.component_id', '=', 'components.id')
            ->pluck('components.component_category_id')
            ->toArray();

        if (empty($selectedCategoryIds)) {
            return null;
        }

        $assembly->components()->latest()->first()->delete();

        $lastSelectedCategoryId = end($selectedCategoryIds);

        return ComponentCategory::query()->find($lastSelectedCategoryId);
    }

    private function completeAssembly($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            return;
        }

        $assembly = Assembly::query()->where('bot_user_id', $user->id)->latest()->first();
        if (!$assembly) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: Сборка не найдена.",
            ]);
            return;
        }

        $totalPrice = $assembly->components->sum('component.price');
        $assembly->update(['total_price' => $totalPrice]);

        $text = "🔧 *Сборка завершена!* 🔧\n\n";
        $text = "💻 *Сборка №* {$assembly->id} \n\n";
        $text .= "💰 *Итоговая стоимость:* {$totalPrice} сум\n\n";
        $text .= "📦 *Детали сборки:* \n\n";

        foreach ($assembly->components as $assemblyComponent) {
            $component = $assemblyComponent->component;
            $category = $component->category->name;
            $brand = $component->brand;
            $price = $component->price;
            $name = $component->name;

            $text .= "📂 *Категория*: {$category}\n";
            $text .= "🏷️ *Название*: {$name}\n";
            $text .= "🏢 *Бренд*: {$brand}\n";
            $text .= "💵 *Цена*: {$price} сум\n\n";
        }

        $keyboard = Keyboard::make(['inline_keyboard' => [
            [
                ['text' => 'Оформить', 'callback_data' => 'confirm_assembly_' . $assembly->id],
                ['text' => 'Удалить', 'callback_data' => 'delete_assembly_' . $assembly->id],
            ]
        ]]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);

        $this->updateUserStep($chatId, 'assembly_completed');
        $this->showMainMenu($chatId);
    }

    private function myAssembly($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            return;
        }

        $assemblies = Assembly::query()->where('bot_user_id', $user->id)->get();

        if ($assemblies->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: У вас нет сборок.",
            ]);
            return;
        }

        foreach ($assemblies as $assembly) {
            $text = "💻 *Сборка №{$assembly->id}*\n";
            $text .= "💰 *Итоговая стоимость*: {$assembly->total_price} сум\n\n";
            $text .= "📦 *Детали сборки:* \n\n";

            foreach ($assembly->components as $assemblyComponent) {
                $component = $assemblyComponent->component;
                $category = $component->category->name;
                $brand = $component->brand;
                $price = $component->price;
                $name = $component->name;

                $text .= "📂 *Категория*: {$category}\n";
                $text .= "🏷️ *Название*: {$name}\n";
                $text .= "🏢 *Бренд*: {$brand}\n";
                $text .= "💵 *Цена*: {$price} сум\n\n";
            }

            $keyboard = OrderItem::query()->where('assembly_id', $assembly->id)->exists() ? Keyboard::make(['inline_keyboard' => []]) : Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => 'Оформить', 'callback_data' => 'confirm_assembly_' . $assembly->id],
                    ['text' => 'Удалить', 'callback_data' => 'delete_assembly_' . $assembly->id],
                ]
            ]]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'Markdown',
            ]);
        }
    }

    private function deleteAssembly($chatId, $assemblyId, $callbackQuery)
    {
        Assembly::query()->find($assemblyId)->delete();

        $messageId = $callbackQuery->getMessage()->getMessageId();

        $this->telegram->deleteMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Сборка успешно удалена.',
            'show_alert' => false,
        ]);
    }

    private function checkCompatibility($chatId, $selectedComponent)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден!'
            ]);
            return false;
        }

        $assembly = Assembly::query()->where('bot_user_id', $user->id)->latest()->with('components.component')->first();
        if (!$assembly || $assembly->components->isEmpty()) {
            return true;
        }

        foreach ($assembly->components as $assemblyComponent) {
            $existingComponent = $assemblyComponent->component;

            if (CategoryCompatibility::areCompatible($existingComponent->component_category_id, $selectedComponent->component_category_id)) {
                $existingComponentType = $existingComponent->component_type_id;
                $selectedComponentType = $selectedComponent->component_type_id;


                if (!TypeCompatibility::areCompatible($existingComponentType, $selectedComponentType)) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Типы комплектующих несовместимы! Выберите другой.'
                    ]);
                    return false;
                }
            }
        }

        return true;
    }

    private function assemblyConfirmation($chatId, $assemblyId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();

        if (!$user) {
            return;
        }

        $assembly = Assembly::query()->find($assemblyId);

        if (!$assembly) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: Сборка не найдена.",
            ]);
            return;
        }

        $order = Order::query()->create([
            'bot_user_id' => $user->id,
            'total_price' => $assembly->total_price,
            'status' => 'waiting',
            'type' => 'assembly',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'assembly_id' => $assembly->id,
            'quantity' => 1,
            'price' => $assembly->total_price,
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Благодарим за выбор нашей компании и покупку у нас! \nЕсли вам требуется помощь в быстрой сборке, свяжитесь с нашими администраторами:\n\n📞 Тел: 999340799\n📞 Тел: 931311100\n\nСвязь через Telegram:\n🔹 @meaning_03 (УЗ-РУ)\n🔹 @muhtar_pc (РУ)\n\nМы всегда готовы помочь вам! ✅"
        ]);
    }

    private function basketConfirmation($chatId, $basketId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();

        if (!$user) {
            return;
        }

        $basket = Basket::query()->find($basketId);

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: Карзина не найдена.",
            ]);
            return;
        }

        foreach($basket->basketItems as $item){
            $order = Order::query()->create([
                'bot_user_id' => $user->id,
                'total_price' => $basket->total_price,
                'status' => 'waiting',
                'type' => (!empty($item->product_id) || !empty($item->component_id))  ? 'product' : 'admin_assembly',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'admin_assembly_id' => $item->admin_assembly_id,
                'component_id' => $item->component_id,
                'quantity'=> ($item->product_count ?? $item->component_count) ?? 1,
                'price' => $item->price,
            ]);
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Благодарим за выбор нашей компании и покупку у нас! \nЕсли вам требуется помощь в быстрой сборке, свяжитесь с нашими администраторами:\n\n📞 Тел: 999340799\n📞 Тел: 931311100\n\nСвязь через Telegram:\n🔹 @meaning_03 (УЗ-РУ)\n🔹 @muhtar_pc (РУ)\n\nМы всегда готовы помочь вам! ✅"
        ]);
    }

    // Setting
    private function setting($chatId)
    {
        $user = BotUser::query()->where('chat_id', $chatId)->first();

        $keyboard[] = [
            [
                'text' => 'Полное имя'
            ],
            [
                'text' => 'Язык'
            ],
        ];

        $keyboard[] = [
            [
                'text' => 'Назад',
            ],
        ];

        $reply_markup = new Keyboard([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
        ]);

        $lang = $user->lang == 'ru' ? 'Русский' : "O'zbekcha";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => '*Настройки*' . PHP_EOL .
                'Язык: ' . $lang . PHP_EOL .
                'Полное имя: ' . $user->full_name . PHP_EOL .
                'Номер телефона: ' . $user->phone_number,
            'parse_mode' => 'Markdown',
            'reply_markup' => $reply_markup
        ]);

        $this->updateUserStep($chatId, 'setting');
    }

    private function changeUserFullName($chatId, $fullName){
        BotUser::query()->where('chat_id', $chatId)->update(['full_name' => $fullName]);
    }
}
