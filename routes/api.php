<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SubCategoryController;
use Illuminate\Support\Facades\Route;


Route::get('/sub-categories/{productCategory}', [SubCategoryController::class, 'fetchByCategory']);
Route::get('/orders/show/{order}', [OrderController::class, 'show']);
Route::put('/orders/status/{order}', [OrderController::class, 'changeStatus']);
