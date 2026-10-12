@extends('errors.layout')

@section('code', '503')
@section('heading', 'Back in a moment')
@section('message', "TV Tracker is being updated. This page will work again shortly.")
@section('actions')
<a href="javascript:location.reload()" class="{{ \App\Support\ButtonStyle::classes('primary') }}">Reload</a>
@endsection
