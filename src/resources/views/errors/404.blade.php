@extends('errors.layout')

@section('code', '404')
@section('heading', 'Page not found')
@section('message', "That page doesn't exist, or the show or movie was removed from your list.")
@section('actions')
<a href="{{ url('/dashboard') }}" class="{{ \App\Support\ButtonStyle::classes('primary') }}">Go to dashboard</a>
@endsection
