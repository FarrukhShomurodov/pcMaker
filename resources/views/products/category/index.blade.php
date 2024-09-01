@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                @if ($errors->any())
                    <div class="alert alert-solid-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </div>
                @endif
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Категории</h2>
                                <a href="{{ route('product.category.create') }}">
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
                                @foreach($productCategories as $productCategory)
                                    <tr>
                                        <td>{{ $productCategory->id }}</td>
                                        <td>{{ $productCategory->name }}</td>
                                        <td>
                                            <div class="d-inline-block text-nowrap">
                                                <button class="btn btn-warning notika-btn-warning btn-sm waves-effect"
                                                        onclick="location.href='{{ route('product.category.edit', $productCategory->id) }}'">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form
                                                    action="{{ route('product.category.destroy', $productCategory->id) }}"
                                                    method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-danger notika-btn-danger btn-sm waves-effect">
                                                        <i class="fa-solid fa-trash"></i>
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
