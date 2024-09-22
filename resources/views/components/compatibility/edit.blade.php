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
                        <form action="{{ route('component.compatibility.update', $typeCompatibility->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="component_select">Выберите тип</label>
                                <select class="form-control select2" name="component_type_id" id="component_select">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ $typeCompatibility->component_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="compatible_with_select">Выберите совместимые типы</label>
                                <select class="form-control select2" name="compatible_with_id[]" id="compatible_with_select" multiple>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}"
                                                @if($typeCompatibility->compatible_type_id == $type->id) selected @endif>
                                            {{ $type->name }}
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
        $(document).ready(function() {
            $('#component_select, #compatible_with_select').select2();
        });
    </script>
@endsection
