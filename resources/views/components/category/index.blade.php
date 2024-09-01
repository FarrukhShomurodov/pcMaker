@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Категории</h2>
                                <a href="{{ route('component.category.create') }}">
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
                                    <th>Деиствия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($componentCategories as $componentCategory)
                                    <tr>
                                        <td>{{ $componentCategory->id }}</td>
                                        <td>{{ $componentCategory->name }}</td>
                                        <td>
                                            <div class="d-inline-block text-nowrap">
                                                <button class="btn btn-warning notika-btn-warning btn-sm waves-effect"
                                                        onclick="location.href='{{ route('component.category.edit', $componentCategory->id) }}'">Редактировать</button>
                                                <form action="{{ route('component.category.destroy', $componentCategory->id) }}" method="POST"
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
