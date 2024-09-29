@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Редактировать совместимость</h2>
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
                        <form
                            action="{{ route('component.category-compatibility.update', $categoryCompatibility->id) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="component_select">Выберите категорию</label>
                                <select class="form-control select2" name="component_category_id" id="component_select">
                                    @foreach($categories as $category)
                                        <option
                                            value="{{ $category->id }}" {{ $categoryCompatibility->component_category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="compatible_with_select">Выберите совместимые категории</label>
                                <select class="form-control select2" name="compatible_with_id[]"
                                        id="compatible_with_select" multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                @if($categoryCompatibility->compatible_category_id == $category->id) selected @endif>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-warning notika-btn-warning btn-sm waves-effect">Редактировать
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
