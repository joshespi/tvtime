@extends('errors.layout')

@section('code', '403')
@section('heading', 'Not allowed')
@section('message', "You don't have access to that.")
@section('actions')
<a href="{{ url('/dashboard') }}" class="{{ \App\Support\ButtonStyle::classes('primary') }}">Go to dashboard</a>
@endsection
