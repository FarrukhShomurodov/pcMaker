<?php

namespace App\Services\Telegram;

use App\Models\Assembly;
use App\Models\AssemblyComponent;
use App\Models\Basket;
use App\Models\BotUser;
use App\Models\CategoryCompatibility;
use App\Models\Component;
use App\Models\ComponentCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TypeCompatibility;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class UserAssembly
{
    protected Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
    }

    private function createAssembly($chatId): void
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

    private function cancelAssembly($chatId): void
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

    private function selectCategory($chatId, $categoryId, $isFirst = false): void
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


    private function selectComponent($chatId, $component): void
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

    private function completeAssembly($chatId): void
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

        $keyboard = Keyboard::make([
            'inline_keyboard' => [
                [
                    ['text' => 'Оформить', 'callback_data' => 'confirm_assembly_' . $assembly->id],
                    ['text' => 'Удалить', 'callback_data' => 'delete_assembly_' . $assembly->id],
                ]
            ]
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);

        $this->updateUserStep($chatId, 'assembly_completed');
        $this->showMainMenu($chatId);
    }

    private function myAssembly($chatId): void
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

            $keyboard = OrderItem::query()->where('assembly_id', $assembly->id)->exists() ? Keyboard::make(
                ['inline_keyboard' => []]
            ) : Keyboard::make([
                'inline_keyboard' => [
                    [
                        ['text' => 'Оформить', 'callback_data' => 'confirm_assembly_' . $assembly->id],
                        ['text' => 'Удалить', 'callback_data' => 'delete_assembly_' . $assembly->id],
                    ]
                ]
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'Markdown',
            ]);
        }
    }

    private function deleteAssembly($chatId, $assemblyId, $callbackQuery): void
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

    private function checkCompatibility($chatId, $selectedComponent): bool
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

            if (CategoryCompatibility::areCompatible(
                $existingComponent->component_category_id,
                $selectedComponent->component_category_id
            )) {
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

    private function assemblyConfirmation($chatId, $assemblyId): void
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

    private function basketConfirmation($chatId, $basketId): void
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

        foreach ($basket->basketItems as $item) {
            $order = Order::query()->create([
                'bot_user_id' => $user->id,
                'total_price' => $basket->total_price,
                'status' => 'waiting',
                'type' => (!empty($item->product_id) || !empty($item->component_id)) ? 'product' : 'admin_assembly',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'admin_assembly_id' => $item->admin_assembly_id,
                'component_id' => $item->component_id,
                'quantity' => ($item->product_count ?? $item->component_count) ?? 1,
                'price' => $item->price,
            ]);

            $item->delete();
        }

        $basket->delete();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Благодарим за выбор нашей компании и покупку у нас! \nЕсли вам требуется помощь в быстрой сборке, свяжитесь с нашими администраторами:\n\n📞 Тел: 999340799\n📞 Тел: 931311100\n\nСвязь через Telegram:\n🔹 @meaning_03 (УЗ-РУ)\n🔹 @muhtar_pc (РУ)\n\nМы всегда готовы помочь вам! ✅"
        ]);
    }
}
