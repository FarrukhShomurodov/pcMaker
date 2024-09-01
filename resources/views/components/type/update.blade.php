@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Редактировать тип</h2>
                        </div>
                        <form action="{{ route('component.type.update', $componentType->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input type="text" name="name" class="form-control" placeholder="Название"
                                                   value="{{$componentType->name}}">
                                        </div>
                                    </div>
                                </div>
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
