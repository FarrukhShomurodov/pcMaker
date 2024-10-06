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
                                    <th>Номер</th>
                                </tr>
                                <tr>
                                    <th>Название</th>
                                    <th>Категория</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->items as $items)
                                    @if($items->assembly_id)
                                        <tr>
                                            <td>{{ $items->assembly_id }}</td>
                                        </tr>

                                        @foreach($items->assembly->components as $components)
                                            <tr>
                                                <td>{{ $components->component->name }}</td>
                                                <td>{{ $components->component->category->name }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
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
