@extends('errors.layout')

@section('code', '404')
@section('heading', 'Page not found')
@section('message', "That page doesn't exist, or the show or movie was removed from your list.")
@section('actions')
<x-action-button variant="primary" href="{{ url('/dashboard') }}">Go to dashboard</x-action-button>
@endsection
