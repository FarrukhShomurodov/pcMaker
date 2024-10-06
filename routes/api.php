<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SubCategoryController;
use Illuminate\Support\Facades\Route;


Route::get('/sub-categories/{productCategory}', [SubCategoryController::class, 'fetchByCategory']);
Route::get('/api/orders/show/{order}', [OrderController::class, 'show']);
