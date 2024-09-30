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

        if (str_starts_with($data, 'component_category_')) {
            $subCategoryId = str_replace('component_category_', '', $data);
            $this->showComponentsByCategory($chatId, $subCategoryId);
        }

        if (str_starts_with($data, 'add_product_to_bin')) {
            $productId = str_replace('add_product_to_bin', '', $data);
            $this->addProductToBasket($chatId, $productId, null, null, $callbackQuery);
        }

        if (str_starts_with($data, 'remove_product_from_bin')) {
            $productId = str_replace('remove_product_from_bin', '', $data);
            $this->removeProductFromBasket($chatId, $productId, null, null, $callbackQuery);
        }

        if (str_starts_with($data, 'add_component_to_bin')) {
            $componentId = str_replace('add_component_to_bin', '', $data);
            $this->addProductToBasket($chatId, null, $componentId, null, $callbackQuery);
        }

        if (str_starts_with($data, 'remove_component_from_bin')) {
            $componentId = str_replace('remove_component_from_bin', '', $data);
            $this->removeProductFromBasket($chatId, null, $componentId, null, $callbackQuery);
        }

        if (str_starts_with($data, 'add_admin_assembly_to_bin')) {
            $adminAssemblyId = str_replace('add_admin_assembly_to_bin', '', $data);
            $this->addProductToBasket($chatId, null, null, $adminAssemblyId, $callbackQuery);
        }

        if (str_starts_with($data, 'remove_admin_assembly_from_bin')) {
            $adminAssemblyId = str_replace('remove_admin_assembly_from_bin', '', $data);
            $this->removeProductFromBasket($chatId, null, null, $adminAssemblyId, $callbackQuery);
        }
    }

    public function processMessage($chatId, $text, $step, $message)
    {
        // Handle pre-defined commands
        $commands = [
            '🛍️ Корзина' => 'basketItems',
            '💼 Выбрать сборку' => 'adminAssemblies',
            '🖥️ Собрать компьютер' => 'createAssembly',
            '🔧 Комплектующие' => 'showAdminCategory',
            '🧩 Мои сборки' => 'myAssembly'
        ];

        if (array_key_exists($text, $commands)) {
            $this->{$commands[$text]}($chatId);
            return;
        }

        // Handle user steps
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
        ];

        $keyboard = new Keyboard(['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => true]);

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

                    $mediaGroup[] = InputMediaPhoto::make([
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ]);

                }
                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }

            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add_product_to_bin' . $product->id],
                ]
            ]]);


            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🛍️ Добавить в корзину",
                'reply_markup' => $keyboard,
            ]);
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

        if ($itemType === 'admin_assembly' && !$item) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Сборка админа не найдена.'
            ]);
            return;
        }

        if ($itemType === 'product' && !$item) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Продукт не найден.'
            ]);
            return;
        }

        if ($itemType === 'component' && !$item) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Компонент не найден.'
            ]);
            return;
        }

        $botUser = BotUser::where('chat_id', $chatId)->first();
        $basket = Basket::firstOrCreate(['bot_user_id' => $botUser->id]);

        $basketItem = BasketItem::where('basket_id', $basket->id)
            ->where($itemType . '_id', $item->id)
            ->first();

        if ($basketItem) {
            if ($itemType === 'product' && $item->quantity < $basketItem->product_count + 1) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'К сожалению, вы превысили доступное количество этого товара.',
                    'show_alert' => true
                ]);
                return;
            } elseif ($itemType === 'component' && $item->quantity < $basketItem->component_count + 1) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'text' => 'К сожалению, вы превысили доступное количество этого компонента.',
                    'show_alert' => true
                ]);
                return;
            } else {
                if ($itemType === 'component' || $itemType === 'product') {
                    $basketItem->increment($itemType . '_count');
                }
            }
        } else {
            if ($itemType === 'component' || $itemType === 'product') {
                BasketItem::create([
                    'basket_id' => $basket->id,
                    $itemType . '_id' => $item->id,
                    $itemType . '_count' => 1,
                    'price' => $item->price,
                ]);
            } else {
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
        $basket = Basket::where('bot_user_id', $botUser->id)->first();

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина пуста.'
            ]);
            return;
        }

        $basketItem = null;
        if ($productId) {
            $basketItem = BasketItem::where('basket_id', $basket->id)
                ->where('product_id', $productId)
                ->first();
        } elseif ($componentId) {
            $basketItem = BasketItem::where('basket_id', $basket->id)
                ->where('component_id', $componentId)
                ->first();
        } elseif ($adminAssemblyId) {
            $basketItem = BasketItem::where('basket_id', $basket->id)
                ->where('admin_assembly_id', $adminAssemblyId)
                ->first();
        }

        if ($basketItem) {
            if (($productId && $basketItem->product_count > 1) ||
                ($componentId && $basketItem->component_count > 1)) {
                $basketItem->decrement($productId ? 'product_count' : 'component_count');
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
            return $item->price * $item->product_count;
        });

        $basket = Basket::find($basketId);
        $basket->update(['total_price' => $totalPrice]);

        $this->updateBasketMessage($chatId, $basket, $callbackQuery);
    }

    private function updateBasketMessage($chatId, $basket, $callbackQuery)
    {
        $basketItems = BasketItem::where('basket_id', $basket->id)->get();
        $productQuantities = [];
        $componentQuantities = [];
        $adminAssemblyQuantities = [];
        $keyboard = ['inline_keyboard' => []];

        foreach ($basketItems as $item) {
            if ($item->product_id) {
                $productQuantities[$item->product_id] = $item->product_count;
            }
            if ($item->component_id) {
                $componentQuantities[$item->component_id] = $item->component_count;
            }
            if ($item->admin_assembly_id) {
                $adminAssemblyQuantities[$item->admin_assembly_id] = 1;
            }
        }

        foreach ($productQuantities as $productId => $count) {
            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '-', 'callback_data' => 'remove_product_from_bin' . $productId],
                    ['text' => $count, 'callback_data' => 'current_product_count' . $productId],
                    ['text' => '+', 'callback_data' => 'add_product_to_bin' . $productId],
                ]
            ]]);
        }

        foreach ($componentQuantities as $componentId => $count) {
            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '-', 'callback_data' => 'remove_component_from_bin' . $componentId],
                    ['text' => $count, 'callback_data' => 'current_component_count' . $componentId],
                    ['text' => '+', 'callback_data' => 'add_component_to_bin' . $componentId],
                ]
            ]]);

        }

        foreach ($adminAssemblyQuantities as $adminAssemblyId => $count) {
            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => 'Удалить', 'callback_data' => 'remove_admin_assembly_from_bin' . $adminAssemblyId],
                ]
            ]]);
        }

        $totalPrice = $basket->total_price;
        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery->getMessage()->getMessageId(),
            'text' => "🛍️ Добавить в корзину\n\nТекущая стоимость: $totalPrice сум",
            'reply_markup' => $keyboard,
        ]);
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Корзина обновлена.',
        ]);
    }

    private function basketItems($chatId)
    {
        // Retrieve the bot user
        $botUser = BotUser::where('chat_id', $chatId)->first();

        if (!$botUser) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пользователь не найден! 😕'
            ]);
            return;
        }

        // Retrieve the basket associated with the bot user
        $basket = $botUser->basket()->first();

        if (!$basket) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина не найдена! 😕'
            ]);
            return;
        }

        // Retrieve the basket items
        $basketItems = $basket->basketItems()->get();

        if ($basketItems->count() === 0) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Корзина пуста! 😕'
            ]);
            return;
        }

        // Initialize arrays
        $productQuantities = [];
        $componentQuantities = [];

        foreach ($basketItems as $basketItem) {
            $product = Product::find($basketItem->product_id);
            $adminAssembly = AdminAssembly::find($basketItem->admin_assembly_id);
            $component = Component::find($basketItem->component_id);

            if ($product) {
                $productQuantities[$basketItem->product_id] = $basketItem->product_count;

                $photos = json_decode($product->photos, true);
                $description = "💻 *{$product->name}* 💻\n\n"
                    . "🔧 *Бренд:* _{$product->brand}_\n"
                    . "💵 *Цена:* *{$product->price} сум*\n"
                    . "📦 *В наличии:* _{$product->quantity} шт._\n\n"
                    . "⚡ _Идеальный выбор для вашего оборудования!_";

                $mediaGroup = [];
                if (!empty($photos) && is_array($photos)) {
                    foreach ($photos as $index => $photo) {
                        $photoPath = Storage::url('public/' . $photo);
                        $fullPhotoUrl = env('APP_URL') . $photoPath;

                        $mediaGroup[] = InputMediaPhoto::make([
                            'type' => 'photo',
                            'media' => $fullPhotoUrl, // Use the correct photo URL
                            'caption' => $index === 0 ? $description : '',
                            'parse_mode' => 'Markdown'
                        ]);
                    }

                    $this->telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($mediaGroup)
                    ]);
                }

                $keyboard = Keyboard::make(['inline_keyboard' => [
                    [
                        ['text' => '-', 'callback_data' => 'remove_product_from_bin' . $product->id],
                        ['text' => $productQuantities[$product->id] ?? '0', 'callback_data' => 'current_product_count' . $product->id],
                        ['text' => '+', 'callback_data' => 'add_product_to_bin' . $product->id],
                    ]
                ]]);

                $totalPrice = $basket->total_price;

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🛍️ Добавить в корзину\n\nТекущая стоимость: $totalPrice сум",
                    'reply_markup' => $keyboard,
                ]);
            }

            if ($component) {
                $componentQuantities[$basketItem->component_id] = $basketItem->component_count;

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

                        $mediaGroup[] = InputMediaPhoto::make([
                            'type' => 'photo',
                            'media' => $fullPhotoUrl, // Use the correct photo URL
                            'caption' => $index === 0 ? $description : '',
                            'parse_mode' => 'Markdown'
                        ]);
                    }

                    $this->telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($mediaGroup)
                    ]);
                }

                $keyboard = Keyboard::make(['inline_keyboard' => [
                    [
                        ['text' => '-', 'callback_data' => 'add_component_to_bin' . $component->id],
                        ['text' => $componentQuantities[$component->id] ?? '0', 'callback_data' => 'current_component_count' . $component->id],
                        ['text' => '+', 'callback_data' => 'remove_component_from_bin' . $component->id],
                    ]
                ]]);

                $totalPrice = $basket->total_price;

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🛍️ Добавить в корзину\n\nТекущая стоимость: $totalPrice сум",
                    'reply_markup' => $keyboard,
                ]);
            }

            if ($adminAssembly) {
                $photos = json_decode($adminAssembly->photos, true);
                $description = "*{$adminAssembly->title}* \n\n"
                    . "{$adminAssembly->description}\n\n"
                    . "💵 *Цена:* *{$adminAssembly->price} сум* \n\n";

                $mediaGroup = [];
                if (!empty($photos) && is_array($photos)) {
                    foreach ($photos as $index => $photo) {
                        $photoPath = Storage::url('public/' . $photo);
                        $fullPhotoUrl = env('APP_URL') . $photoPath;

                        $mediaGroup[] = InputMediaPhoto::make([
                            'type' => 'photo',
                            'media' => $fullPhotoUrl, // Use the correct photo URL
                            'caption' => $index === 0 ? $description : '',
                            'parse_mode' => 'Markdown'
                        ]);
                    }

                    $this->telegram->sendMediaGroup([
                        'chat_id' => $chatId,
                        'media' => json_encode($mediaGroup)
                    ]);
                }

                $keyboard = Keyboard::make(['inline_keyboard' => [
                    [
                        ['text' => 'Удалить', 'callback_data' => 'remove_admin_assembly_from_bin' . $adminAssembly->id],
                    ]
                ]]);

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🛍️ Добавить в корзину",
                    'reply_markup' => $keyboard,
                ]);
            }
        }
    }

    // Admin Assemblies
    private function adminAssemblies($chatId)
    {
        $adminAssemblies = AdminAssembly::all();

        if ($adminAssemblies->count() < 1) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Сборов админа нету в наличии.'
            ]);
            return;
        }
        foreach ($adminAssemblies as $adminAssembly) {
            $photos = json_decode($adminAssembly->photos, true);

            $description = "*{$adminAssembly->title}* \n\n"
                . "{$adminAssembly->description}\n\n"
                . "💵 *Цена:* *{$adminAssembly->price} сум* \n\n";

            $mediaGroup = [];
            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $index => $photo) {
                    $photoPath = Storage::url('public/' . $photo);
                    $fullPhotoUrl = env('APP_URL') . $photoPath;

                    $mediaGroup[] = InputMediaPhoto::make([
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ]);

                }
                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }
            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add_admin_assembly_to_bin' . $adminAssembly->id],
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

                    $mediaGroup[] = InputMediaPhoto::make([
                        'type' => 'photo',
                        'media' => $fullPhotoUrl,
                        'caption' => $index === 0 ? $description : '',
                        'parse_mode' => 'Markdown'
                    ]);

                }
                $this->telegram->sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($mediaGroup)
                ]);
            }

            $keyboard = Keyboard::make(['inline_keyboard' => [
                [
                    ['text' => '+', 'callback_data' => 'add_component_to_bin' . $component->id],
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

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);

        $this->updateUserStep($chatId, 'assembly_completed');
        $this->showMainMenu($chatId);
    }

    private function myAssembly($chatId)
    {
        // Получаем пользователя по chat_id
        $user = BotUser::query()->where('chat_id', $chatId)->first();
        if (!$user) {
            return;
        }

        // Получаем все сборки пользователя
        $assemblies = Assembly::query()->where('bot_user_id', $user->id)->get();

        if ($assemblies->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: У вас нет сборок.",
            ]);
            return;
        }

        // Проходимся по каждой сборке и формируем сообщение
        foreach ($assemblies as $assembly) {
            $text = "💻 *Сборка №{$assembly->id}*\n";
            $text .= "💰 *Итоговая стоимость*: {$assembly->total_price} сум\n\n";
            $text .= "📦 *Детали сборки:* \n\n";

            // Формируем описание для каждого компонента сборки
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

            // Отправляем собранную информацию пользователю
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        }

        // Обновляем шаг пользователя
        $this->updateUserStep($chatId, 'assembly_viewed');
        $this->showMainMenu($chatId);
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
}
