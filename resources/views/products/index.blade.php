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
                                <a href="{{ route('product.items.create') }}">
                                    <button class="btn btn-success notika-btn-success btn-sm waves-effect">Создать
                                    </button>
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Названия</th>
                                    <th>Категория</th>
                                    <th>Под категория</th>
                                    <th>Бренд</th>
                                    <th>Описание</th>
                                    <th>Количество</th>
                                    <th>Цена</th>
                                    <th>Фото</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>{{ $product->id }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->category->name }}</td>
                                        <td>{{ isset($product->subCategory) ? $product->subCategory->name: '-' }}</td>
                                        <td>{{ $product->brand }}</td>
                                        <td>{{ $product->description ?? '-' }}</td>
                                        <td>{{ $product->quantity }}</td>
                                        <td>{{ $product->price }}</td>
                                        <td>
                                            <div class="main__td">
                                                @if($product->photos)
                                                    @foreach(json_decode($product->photos) as $photo)
                                                        <div class="td__img">
                                                            <img src="/storage/{{ $photo }}" class="popup-img"
                                                                 width="100px"/>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-inline-block text-nowrap">
                                                <button class="btn btn-warning notika-btn-warning btn-sm waves-effect"
                                                        onclick="location.href='{{ route('product.items.edit', $product->id) }}'">Редактировать</button>
                                                <form action="{{ route('product.items.destroy', $product->id) }}" method="POST"
                                                      style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger notika-btn-danger btn-sm waves-effect">
                                                        Удалить
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
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
