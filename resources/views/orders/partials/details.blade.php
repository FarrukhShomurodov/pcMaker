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
        <li><b>Номер название:</b> {{ $orderDetails['title'] }}</li>
        <p><b>Номер описание:</b> {{ $orderDetails['description'] }}</p>
        <p><b>Цена:</b> {{ $orderDetails['price'] }}</p>
    </ul>
@else
    @if ($order->items()->first()->component_id)
        <h3>Детали заказа:</h3>
        <ul>
            @foreach($orderDetails as $item)
                <li><b>Номер:</b> {{ $item['id'] }}</li>
                <li><b>Название:</b> {{ $item['component']['name'] }}</li>
                <li><b>Категория:</b> {{ $item['component']['category'] }}</li>
                <li><b>Тип:</b> {{ $item['component']['type'] }}</li>
                <li><b>Цена:</b> {{ $item['component']['price'] }}</li>
                <li><b>Кол-во:</b> {{ $item['component']['quantity'] }}</li>
            @endforeach
        </ul>
    @else
    <h3>Детали заказа:</h3>
        <ul>
            @foreach($orderDetails as $item)
                <li><b>Номер:</b> {{ $item['id'] }}</li>
                <li><b>Название:</b> {{ $item['product']['name'] }}</li>
                <li><b>Кол-во:</b> {{ $item['product']['quantity'] }}</li>
                <li><b>Цена:</b> {{ $item['product']['price'] }}</li>
            @endforeach
        </ul>
    @endif
@endif
