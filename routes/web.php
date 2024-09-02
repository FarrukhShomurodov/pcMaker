<?php

use App\Http\Controllers\Admin\AdminAssemblyController;
use App\Http\Controllers\Admin\ComponentCategoryController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ComponentTypeController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductSubCategoryController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Api;

Route::get('/', function () {
    return view('layouts.app');
});

Route::prefix('product')->name('product.')->group(function () {
    Route::resource('items', ProductController::class);
    Route::resource('category', ProductCategoryController::class)->parameter('product-category', 'productCategory');
    Route::resource('sub-category', ProductSubCategoryController::class)->parameter('product-sub-category', 'productSubCategory');
});

Route::prefix('component')->name('component.')->group(function () {
    Route::resource('items', ComponentController::class);
    Route::resource('category', ComponentCategoryController::class)->parameter('component-category', 'componentCategory');
    Route::resource('type', ComponentTypeController::class)->parameter('component-type', 'componentType');
});

Route::resource('admin-assembly', AdminAssemblyController::class)->parameter('admin-assembly', 'adminAssembly');

Route::prefix('telegram')->group(function () {
//    Route::get('/webhook', function () {
//        $telegram = new Api(config('telegram.bot_token'));
//        $hook = $telegram->setWebhook(['url' => 'https://event-in.online/telegram/webhook']);
//
//        return dd($hook);
//    });
    Route::post('/webhook', [TelegramController::class, 'handleWebhook']);
});
