@if($order->type === 'assembly')
    <h3>Детали сборки:</h3>
    <ul>
        @foreach($orderDetails as $item)
            <li><b>Номер сборки:</b> {{ $item['number'] }}</li>
            @foreach($item['component'] as $component)
                <li><b>{{ $component['category'] }}:</b> {{ $component['name'] }} (Цена: {{ $component['price'] }})</li>
            @endforeach
        @endforeach
    </ul>
@elseif($order->type === 'admin_assembly')
    <h3>Детали админ-сборки:</h3>
    <ul>
        <li><b>Номер сборки:</b> {{ $orderDetails['id'] }}</li>
        <li><b>Номер название:</b> {{ $orderDetails['title'] }} (Цена: {{ $orderDetails['price'] }})</li>
        <p><b>Номер описание:</b> {{ $orderDetails['description'] }}</p>
    </ul>
@else
    <h3>Детали заказа</h3>
    <ul>
        @foreach($orderDetails as $item)
            <li>{{ $item['product']['name'] }} (Кол-во: {{ $item['product']['quantity'] }}) (Цена: {{ $item['product']['price'] }})</li>
        @endforeach
    </ul>
@endif
