<?php

namespace App\Services;

use App\Models\AdminAssembly;
use App\Models\Basket;
use App\Models\BasketItem;
use App\Models\BotUser;
use App\Models\Component;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
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
        switch ($text) {
            case '🛍️ Корзина':
                $this->basketItems($chatId);
                break;
            case '💼 Выбрать сборку':
                $this->adminAssemblies($chatId);
                break;
            default:
                if ($step === 'show_main_menu' || $step === 'show_subcategory') {
                    $this->checkCategory($chatId, $text);
                }
                break;
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
            case 'show_main_menu':
                $this->showMainMenu($chatId);
                break;
//            default:
//                $this->showMainMenu($chatId);
//                break;
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
        BotUser::updateOrCreate(['chat_id' => $chatId], ['step' => $step]);
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
                    $mediaGroup[] = InputMediaPhoto::make([
                        'type' => 'photo',
                        'media' => '`$fullPhotoUrl`',
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

        foreach ($basketItems as $basketItem) {
            $product = Product::find($basketItem->product_id);
            $adminAssembly = AdminAssembly::find($basketItem->admin_assembly_id);

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
                            'media' => '`$fullPhotoUrl`', // Use the correct photo URL
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
                            'media' => `$fullPhotoUrl`, // Use the correct photo URL
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
                        'media' => '`$fullPhotoUrl`',
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
}
