@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Заказы</h2>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User name</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Тип</th>
                                    <th>Детали</th>
                                    <th>Тип Оплаты</th>
                                    <th>Тип доставки</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td><a href="https://t.me/{{$order->user->uname}}"
                                               target="_blank">{{ $order->user->uname }}</a>
                                        </td>
                                        <td>{{ $order->total_price }}</td>
                                        <td>{{ $order->status }}</td>
                                        <td>{{ $order->type }}</td>
                                        <td>
                                            <button class="btn btn-success btn-sm waves-effect show-order-details"
                                                    data-order-id="{{ $order->id }}">Смотреть</button>
                                        </td>
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

    <!-- Модальное окно для деталей заказа -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Детали заказа</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Контент будет загружен через AJAX -->
                    <div id="order-details-content"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.show-order-details').on('click', function() {
                const orderId = $(this).data('order-id');

                $.ajax({
                    url: '/api/orders/show/' + orderId,
                    method: 'GET',
                    success: function(response) {
                        $('#order-details-content').html(response.html);
                        $('#orderDetailsModal').modal('show');
                    },
                    error: function() {
                        alert('Ошибка загрузки данных');
                    }
                });
            });
        });
    </script>
@endsection
