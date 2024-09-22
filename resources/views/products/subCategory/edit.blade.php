@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Редактировать под категорию</h2>
                        </div>
                        <form action="{{ route('product.sub-category.update', $productSubCategory->id ) }}" method="POST">
                            @method('PUT')
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="name" type="text" class="form-control" placeholder="Название"
                                                   value="{{ $productSubCategory->name }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <select class="form-select select2" style="width: 100%"
                                                    name="product_category_id">
                                                @foreach($productCategories as $productCategory)
                                                    <option
                                                        value="{{$productCategory->id}}" @selected($productCategory->id == $productSubCategory->product_category_id) >{{$productCategory->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning notika-btn-warning btn-sm waves-effect">
                                Редактировать
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
