<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Pc Maker Admin By ST40</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/" href="{{asset('img/logo/logo.png')}}">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/owl.carousel.css')}}">
    <link rel="stylesheet" href="{{asset('css/owl.theme.css')}}">
    <link rel="stylesheet" href="{{asset('css/owl.transitions.css')}}">
    <link rel="stylesheet" href="{{asset('css/meanmenu/meanmenu.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/animate.css')}}">
    <link rel="stylesheet" href="{{asset('css/normalize.css')}}">
    <link rel="stylesheet" href="{{asset('css/wave/waves.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/wave/button.css')}}">
    <link rel="stylesheet" href="{{asset('css/scrollbar/jquery.mCustomScrollbar.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/notika-custom-icon.css')}}">
    <link rel="stylesheet" href="{{asset('css/jquery.dataTables.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="{{asset('style.css')}}">
    <link rel="stylesheet" href="{{asset('css/responsive.css')}}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="{{asset('js/vendor/modernizr-2.8.3.min.js')}}"></script>
</head>

<body>

@include('layouts.header')

@include('layouts.menu')

@yield('content')

@include('layouts.footer')

<script src="{{asset('js/vendor/jquery-1.12.4.min.js')}}"></script>
<script src="{{asset('js/bootstrap.min.js')}}"></script>
<script src="{{asset('js/wow.min.js')}}"></script>
<script src="{{asset('js/jquery-price-slider.js')}}"></script>
<script src="{{asset('js/owl.carousel.min.js')}}"></script>
<script src="{{asset('js/jquery.scrollUp.min.js')}}"></script>
<script src="{{asset('js/meanmenu/jquery.meanmenu.js')}}"></script>
<script src="{{asset('js/counterup/jquery.counterup.min.js')}}"></script>
<script src="{{asset('js/counterup/waypoints.min.js')}}"></script>
<script src="{{asset('js/counterup/counterup-active.js')}}"></script>
<script src="{{asset('js/scrollbar/jquery.mCustomScrollbar.concat.min.js')}}"></script>
<script src="{{asset('js/sparkline/jquery.sparkline.min.js')}}"></script>
<script src="{{asset('js/sparkline/sparkline-active.js')}}"></script>
<script src="{{asset('js/flot/jquery.flot.js')}}"></script>
<script src="{{asset('js/flot/jquery.flot.resize.js')}}"></script>
<script src="{{asset('js/flot/flot-active.js')}}"></script>
<script src="{{asset('js/knob/jquery.knob.js')}}"></script>
<script src="{{asset('js/knob/jquery.appear.js')}}"></script>
<script src="{{asset('js/knob/knob-active.js')}}"></script>
<script src="{{asset('js/chat/jquery.chat.js')}}"></script>
<script src="{{asset('js/todo/jquery.todo.js')}}"></script>
<script src="{{asset('js/wave/waves.min.js')}}"></script>
<script src="{{asset('js/wave/wave-active.js')}}"></script>
<script src="{{asset('js/plugins.js')}}"></script>
<script src="{{asset('js/data-table/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('js/data-table/data-table-act.js')}}"></script>
<script src="{{asset('js/main.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2();
    })
</script>
@yield('scripts')
</body>
</html>
