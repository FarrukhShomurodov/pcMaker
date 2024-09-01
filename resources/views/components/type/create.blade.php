@extends('layouts.app')

@section('content')
    <div class="form-element-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="form-element-list">
                        <div class="basic-tb-hd">
                            <h2>Создать тип</h2>
                        </div>
                        <form action="{{ route('component.type.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <div class="nk-int-st">
                                            <input name="name" type="text" class="form-control" placeholder="Название">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success notika-btn-success btn-sm waves-effect">Создать
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
