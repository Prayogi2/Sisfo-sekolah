{{-- Favicon logo sekolah; pakai favicon.ico bawaan kalau logo belum diunggah. --}}
@if (file_exists(public_path('images/logo.png')))
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif
