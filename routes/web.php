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
use Illuminate\Support\Facades\Storage;
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
});

Route::resource('admin-assembly', AdminAssemblyController::class)->parameter('admin-assembly', 'adminAssembly');

Route::prefix('telegram')->group(function () {
    Route::get('/webhook', function () {
        $telegram = new Api(config('telegram.bot_token'));
        $hook = $telegram->setWebhook(['url' => env('TELEGRAM_WEBHOOK_URL')]);

        return dd($hook);
    });
    Route::post('/webhook', [TelegramController::class, 'handleWebhook']);
});


Route::get("/test", function (){
    $products = \App\Models\Product::query()->first();

    $photos = json_decode($products->photos, true);

    foreach ($photos as $index => $photo) {
        $photoPath = Storage::url('public/' . $photo);
        $fullPhotoUrl = env('APP_URL') . $photoPath;
        dd($fullPhotoUrl);
    }
});
