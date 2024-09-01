@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Под Категории</h2>
                                <a href="{{ route('product.sub-category.create') }}">
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
                                    <th>Деиствия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($productSubCategories as $productSubCategory)
                                    <tr>
                                        <td>{{ $productSubCategory->id }}</td>
                                        <td>{{ $productSubCategory->name }}</td>
                                        <td>{{ $productSubCategory->category->name }}</td>
                                        <td>
                                            <div class="d-inline-block text-nowrap">
                                                <button class="btn btn-warning notika-btn-warning btn-sm waves-effect"
                                                        onclick="location.href='{{ route('product.sub-category.edit', $productSubCategory->id) }}'">Редактировать</button>
                                                <form action="{{ route('product.sub-category.destroy', $productSubCategory->id) }}" method="POST"
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
