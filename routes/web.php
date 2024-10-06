<?php

use App\Http\Controllers\Admin\AdminAssemblyController;
use App\Http\Controllers\Admin\BotUserController;
use App\Http\Controllers\Admin\CategoryCompatibilityController;
use App\Http\Controllers\Admin\ComponentCategoryController;
use App\Http\Controllers\Admin\ComponentCompatibilityController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ComponentTypeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductSubCategoryController;
use App\Http\Controllers\Admin\TypeCompatibilityController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Api;

Route::get('/', function () {
    return view('layouts.app');
});

Route::prefix('product')->name('product.')->group(function () {
    Route::resource('items', ProductController::class)->parameter('items', 'product');
    Route::resource('category', ProductCategoryController::class)->parameter('category', 'productCategory');
    Route::resource('sub-category', ProductSubCategoryController::class)->parameter('sub-category', 'productSubCategory');
});

Route::prefix('component')->name('component.')->group(function () {
    Route::resource('items', ComponentController::class)->parameter('items', 'component');
    Route::resource('category', ComponentCategoryController::class)->parameter('category', 'componentCategory');
    Route::resource('type', ComponentTypeController::class)->parameter('type', 'componentType');
    Route::resource('compatibility', TypeCompatibilityController::class)->parameter('compatibility', 'typeCompatibility');
    Route::resource('category-compatibility', CategoryCompatibilityController::class)->parameter('category-compatibility', 'categoryCompatibility');
});

Route::resource('admin-assembly', AdminAssemblyController::class)->parameter('admin-assembly', 'adminAssembly');

// Orders
Route::resource('orders', OrderController::class);
Route::get('orders/assembly', [OrderController::class, 'showAssemblyOrder'])->name('order.assemblies');
Route::get('orders/admin-assembly', [OrderController::class, 'showAdminAssemblyOrder'])->name('order.admin.assemblies');
Route::get('orders/products', [OrderController::class, 'showProductsOrder'])->name('order.products');

Route::get('bot-users', [BotUserController::class, 'index'])->name('bot-users');

Route::prefix('telegram')->group(function () {
    Route::get('/webhook', function () {
        $telegram = new Api(config('telegram.bot_token'));
        $hook = $telegram->setWebhook(['url' => env('TELEGRAM_WEBHOOK_URL')]);

        dd($hook);
    });
    Route::post('/webhook', [TelegramController::class, 'handleWebhook']);
});
