@extends('errors.layout')

@section('code', '503')
@section('heading', 'Back in a moment')
@section('message', "TV Tracker is being updated. This page will work again shortly.")
@section('actions')
<x-action-button variant="primary" href="javascript:location.reload()">Reload</x-action-button>
@endsection
