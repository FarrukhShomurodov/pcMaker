@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Совместимость котегории</h2>
                                <a href="{{ route('component.category-compatibility.create') }}">
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
                                    <th>Категория</th>
                                    <th>Совместимые котегория</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($compatibilities as $compatibility)
                                    <tr>
                                        <td>{{ $compatibility->id }}</td>
                                        <td>{{ $compatibility->componentCategory->name }}</td>
                                        <td>{{ $compatibility->compatibleCategory->name }}</td>
                                        <td>
                                            <a href="{{ route('component.category-compatibility.edit', $compatibility->id) }}" class="btn btn-warning">Редактировать</a>
                                            <form action="{{ route('component.category-compatibility.destroy', $compatibility->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger">Удалить</button>
                                            </form>
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
