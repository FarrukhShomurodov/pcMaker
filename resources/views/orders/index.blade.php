@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Продукты</h2>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User name</th>
                                    <th>Сумма</th>
                                    <th>Тип</th>
                                    <th>Тип Оплаты</th>
                                    <th>Тип доставки</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td><a href="https://t.me/{{$order->user->uname}}" target="_blank">{{ $order->user->uname }}</a>
                                        </td>
                                        <td>{{ $order->total_price}}</td>
                                        <td>{{ $order->status }}</td>
                                        <td>{{ $order->type }}</td>
                                        <td>{{ $order->payment_method_id ?? '-' }}</td>
                                        <td>{{ $order->delivery_method_id ?? '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>
    $('.popup-img').on('click', function () {
        let src = $(this).attr('src');
        let popup = `
                <div class="popup-overlay" onclick="$(this).remove()">
                    <img src="${src}" class="popup-img-expanded">
                </div>
            `;
        $('body').append(popup);
    });

</script>
@endsection
