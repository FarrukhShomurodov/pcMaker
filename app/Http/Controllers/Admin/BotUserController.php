<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotUser;
use Illuminate\View\View;

class BotUserController extends Controller
{
    public function index(): View
    {
        $botUsers = BotUser::all();
        return view('user.bot-users', compact('botUsers'));
    }
}
