<?php

use App\Http\Controllers\Api\SubCategoryController;
use Illuminate\Support\Facades\Route;


Route::get('/sub-categories/{productCategory}', [SubCategoryController::class, 'fetchByCategory']);
