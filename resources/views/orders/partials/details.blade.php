@if($order->type === 'assembly')
    <h3>Детали сборки:</h3>
    <ul>
        @foreach($orderDetails as $item)
            @foreach($item['component'] as $component)
                <li><b>{{ $component['category'] }}:</b> {{ $component['name'] }} (Цена: {{ $component['price'] }})</li>
            @endforeach
        @endforeach
    </ul>
@elseif($order->type === 'admin_assembly')
    <h3>Детали админ-сборки:</h3>
    <ul>
        @foreach($orderDetails as $item)
            <li>{{ $item['admin_assembly']['title'] }} (Цена: {{ $item['admin_assembly']['price'] }})</li>
            <p>{{ $item['admin_assembly']['description'] }}</p>
        @endforeach
    </ul>
@else
    <h3>Детали заказа:</h3>
    <ul>
        @foreach($orderDetails as $item)
            <li>{{ $item['product']['name'] }} (Кол-во: {{ $item['product']['quantity'] }}) (Цена: {{ $item['product']['price'] }})</li>
        @endforeach
    </ul>
@endif
