@extends('errors.layout')

@section('code', '419')
@section('heading', 'Session expired')
@section('message', "You were signed out while this page sat open, so that last action wasn't saved. Sign in again and give it another go.")
@section('actions')
<a href="{{ url('/login') }}" class="{{ \App\Support\ButtonStyle::classes('primary') }}">Sign in</a>
<a href="{{ url('/dashboard') }}" class="{{ \App\Support\ButtonStyle::classes('secondary') }}">Try the dashboard</a>
@endsection
