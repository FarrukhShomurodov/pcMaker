<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::all();

        return view('orders.index', compact('orders'));
    }

    public function showProductsOrder(): View
    {
        $orders = Order::query()->where('type', 'product')->get();
        return view('product-orders.index', compact('orders'));
    }

    public function showAssemblyOrder(): View
    {
        $orders = Order::query()->where('type', 'assembly')->get();
        return view('assembly-orders.index', compact('orders'));
    }

    public function showAdminAssemblyOrder(): View
    {
        $orders = Order::query()->where('type', 'admin_assembly')->get();
        return view('admin-assembly-orders.index', compact('orders'));
    }

}
