<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    public function show(Order $order)
    {
        $orderDetails = [];

        if ($order->type === 'assembly') {
            $orderDetails = $order->items->map(function ($item) {
                return [
                    'number' => $item->assembly->id,
                    'component' => $item->assembly ? $item->assembly->components->map(function ($component) {
                        return [
                            'name' => $component->component->name,
                            'category' => $component->component->category->name,
                            'price' => $component->component->price,
                        ];
                    }) : [],
                ];
            });
        } elseif ($order->type === 'admin_assembly') {
            $assemblyAdmin = $order->items()->first()->assemblyAdmin()->first();
            $orderDetails = $assemblyAdmin ? 
            [
                'id' => $assemblyAdmin->id,
                'title' => $assemblyAdmin->title,
                'description' => $assemblyAdmin->description,
                'price' => $assemblyAdmin->price,
            ] : [];
        } else {
            return response()->json($order->items);
            if ($order->items->component_id){
                $orderDetails = $order->items->map(function ($item) {
                    return [
                        'component' => $item->component ? [
                            'id' => $item->component->id,
                            'name' => $item->component->name,
                            'category' => $item->component->category->name,
                            'type' => $item->component->type->name,
                            'price' => $item->component->price,
                            'quantity' => $item->quantity,
                        ] : [],
                    ];
                });
            }else{
                $orderDetails = $order->items->map(function ($item) {
                    return [
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'price' => $item->product->price,
                            'quantity' => $item->quantity,
                        ] : [],
                    ];
                });
            }
        }

        return response()->json($orderDetails);

        $html = view('orders.partials.details', compact('order', 'orderDetails'))->render();

        return response()->json(['html' => $html]);
    }
}
