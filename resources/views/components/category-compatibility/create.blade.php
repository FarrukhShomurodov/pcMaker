@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Создать совместимость категории</h2>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ route('component.category-compatibility.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="component_select">Выберите категорию</label>
                                <select class="form-control select2" name="component_category_id" id="component_select">
                                    <option value="" disabled selected>Выберите категорию</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="compatible_with_select">Выберите совместимые категории</label>
                                <select class="form-control select2" name="compatible_with_id[]"
                                        id="compatible_with_select" multiple>
                                    <option value="" disabled>Выберите совместимые категории</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success notika-btn-success btn-sm waves-effect"
                                    style="margin-top: 10px">Создать
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
            $('#component_select, #compatible_with_select').select2();
        });
    </script>
@endsection
