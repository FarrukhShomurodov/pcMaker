@extends('layouts.app')

@section('content')
    <div class="data-table-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="data-table-list">
                        <div class="basic-tb-hd">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h2 style="margin: 0;">Пользователи бота</h2>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>id</th>
                                    <th>chat_id</th>
                                    <th>phone_number</th>
                                    <th>full_name</th>
                                    <th>step</th>
                                    <th>lang</th>
                                    <th>created at</th>
                                </tr>
                                </thead>
                                <tbody>

                                @php
                                    $userCount = 1
                                @endphp

                                @foreach($botUsers as $user)
                                    <tr>
                                        <td>{{ $userCount++ }}</td>
                                        <td>{{ $user->chat_id }}</td>
                                        <td>{{ $user->phone_number }}</td>
                                        <td>{{ $user->full_name}}</td>
                                        <td>{{ $user->step}}</td>
                                        <td>{{ $user->lang}}</td>
                                        <td>{{ $user->created_at}}</td>
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
