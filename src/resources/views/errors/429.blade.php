@extends('errors.layout')

@section('code', '429')
@section('heading', 'Slow down')
@section('message', "Too many requests in a short time. Wait a moment, then try again.")
@section('actions')
<a href="javascript:history.back()" class="{{ \App\Support\ButtonStyle::classes('secondary') }}">Go back</a>
@endsection
