@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Редактировать продукт</h2>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-solid-danger" role="alert">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </div>
                        @endif
                        <form action="{{ route('product.items.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="name" type="text" class="form-control" placeholder="Название" value="{{ $product->name }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <select class="form-select select2" style="width: 100%"
                                                    name="product_category_id" id="product_category_select">
                                                @foreach($productCategories as $productCategory)
                                                    <option
                                                        value="{{$productCategory->id}}" @selected($product->product_category_id ==  $productCategory->id)>{{$productCategory->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <select class="form-select select2" style="width: 100%"
                                                    name="product_sub_category_id" id="product_sub_category_select">
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="brand" type="text" class="form-control" placeholder="Бренд" value="{{ $product->brand }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <textarea name="description" class="form-control" placeholder="Описание">{{ $product->description }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="quantity" type="number" class="form-control"
                                                   placeholder="Количество"  value="{{ $product->quantity }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="price" type="number" class="form-control" placeholder="Цена" value="{{ $product->price }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label for="imageInput" class="form-label">Выберите фото</label>
                                    <input type="file" name="photos[]" id="imageInput" class="form-control" multiple>
                                    <div id="imagePreview" class="mb-3 main__td">
                                        @if($product->photos)
                                            @foreach(json_decode($product->photos) as $photo)
                                                <div class="image-container td__img" data-photo-path="{{ $photo }}">
                                                    <img src="{{ asset('storage/' . $photo) }}" class="uploaded-image">
                                                    <button type="button" class="btn btn-danger btn-sm delete-image"
                                                            data-photo-path="{{ $photo }}">Удалить
                                                    </button>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                </div>
                            </div>
                            <button class="btn btn-warning notika-btn-warning btn-sm waves-effect mg-t-10">Редактировать
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function () {
            subCategories({{ $product->product_category_id }} )

            $('#product_category_select').on('change', function () {
                let categoryId = $(this).val();

                $('#product_sub_category_select').empty();
                subCategories(categoryId)
            });

            function subCategories(categoryId){
                $.ajax({
                    url: `/api/sub-categories/${categoryId}`,
                    type: 'GET',
                    success: function (response) {
                        console.log(response);
                        if (response.length > 0) {
                            response.forEach(function (subCategory) {
                                let selected = (subCategory.id == {{ $product->product_sub_category_id }} ? 'selected' : '')
                                $('#product_sub_category_select').append(`<option value="${subCategory.id}"  ${selected} >${subCategory.name}</option>`
                                );
                            });
                        } else {
                            $('#product_sub_category_select').append('<option value = "" > Нет подкатегорий </option> ');
                        }
                    },
                    error: function () {
                        alert('Ошибка при получении подкатегорий');
                    }
                });
            }

            $('#imageInput').on('change', function () {
                const files = Array.from($(this)[0].files);
                const imagePreview = $('#imagePreview');
                imagePreview.empty();

                files.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imgElement = $('<img>', {
                            src: e.target.result,
                            alt: file.name,
                            class: 'uploaded-image'
                        });

                        const imgContainer = $('<div>', {class: 'image-container td__img'});
                        imgContainer.append(imgElement);

                        const deleteBtn = $('<button>', {
                            class: 'btn btn-danger btn-sm delete-image',
                            text: 'Удалить',
                            click: function () {
                                imgContainer.remove();

                                const index = files.indexOf(file);
                                if (index !== -1) {
                                    files.splice(index, 1);
                                    updateFileInput(files);
                                }
                            }
                        });
                        imgContainer.append(deleteBtn);

                        imagePreview.append(imgContainer);
                    };
                    reader.readAsDataURL(file);
                });
            });

            function updateFileInput(files) {
                const input = $('#imageInput')[0];
                const fileList = new DataTransfer();
                files.forEach(file => {
                    fileList.items.add(file);
                });
                input.files = fileList.files;
            }
        });
    </script>
@endsection
