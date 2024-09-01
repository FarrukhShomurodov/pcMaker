@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Сборки Админа</h2>
                                <a href="{{ route('admin-assembly.create') }}">
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
                                    <th>Заголовок</th>
                                    <th>Описаниек</th>
                                    <th>Цена</th>
                                    <th>Фото</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($adminAssemblies as $adminAssembly)
                                    <tr>
                                        <td>{{ $adminAssembly->id }}</td>
                                        <td>{{ $adminAssembly->title }}</td>
                                        <td>{{ $adminAssembly->description }}</td>
                                        <td>{{ $adminAssembly->price }}</td>
                                        <td>
                                            <div class="main__td">
                                                @if($adminAssembly->photos)
                                                    @foreach(json_decode($adminAssembly->photos) as $photo)
                                                        <div class="td__img">
                                                            <img src="storage/{{ $photo }}" class="popup-img"
                                                                 width="100px"/>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-inline-block text-nowrap">
                                                <button class="btn btn-warning notika-btn-warning btn-sm waves-effect"
                                                        onclick="location.href='{{ route('admin-assembly.edit', $adminAssembly->id) }}'">Редактировать</button>
                                                <form action="{{ route('admin-assembly.destroy', $adminAssembly->id) }}" method="POST"
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
