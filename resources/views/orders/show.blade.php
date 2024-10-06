@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Детали заказа</h2>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Номер продукт</th>
                                    <th>Номер сборки</th>
                                    <th>Номер сборка админа</th>
                                    <th>Количество</th>
                                    <th>Цена</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->items as $items)
                                    <tr>
                                        <td>{{ $items->id }}</td>
                                        <td>{{ $items->product_id }}</td>
                                        <td>{{ $items->assembly_id }}</td>
                                        <td>{{ $items->admin_assembly_id }}</td>
                                        <td>{{ $items->quantity }}</td>
                                        <td>{{ $items->price }}</td>
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
