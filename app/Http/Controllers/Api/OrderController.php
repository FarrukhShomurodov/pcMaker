<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

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
            if ($order->items()->first()->component_id){
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

        $html = view('orders.partials.details', compact('order', 'orderDetails'))->render();

        return response()->json(['html' => $html]);
    }

    public function changeStatus(Request $request, Order $order){
        $order->update([
            'status' => $request->input('status')
        ]); 

        if($order->status !== 'waiting'){
            $text = $order->status == 'done'
            ? 'Отличные новости! Ваш заказ готов к выдаче. Вы можете забрать его в любое удобное время.'
            : 'К сожалению, ваш заказ был отменен. Если у вас возникли вопросы, пожалуйста, свяжитесь с нашей службой поддержки.';


            $telegram = new Api(config('telegram.bot_token'));
            $telegram->sendMessage([
                'chat_id' => $order->user->chat_id,
                'text' => $text,
            ]);
            
            $telegram->sendLocation(['chat_id' => $order->user->chat_id,
                'latitude' => 41.227364,
                'longitude' => 69.204015
            ]);
        }

        return response()->json([], 200);
    }
}
