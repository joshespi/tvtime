@extends('errors.layout')

@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', "That's on us, not you. Try again in a moment.")
@section('actions')
<a href="javascript:location.reload()" class="{{ \App\Support\ButtonStyle::classes('primary') }}">Reload</a>
<a href="{{ url('/dashboard') }}" class="{{ \App\Support\ButtonStyle::classes('secondary') }}">Go to dashboard</a>
@endsection
