@extends('errors.layout')

@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', "That's on us, not you. Try again in a moment.")
@section('actions')
<x-action-button variant="primary" href="javascript:location.reload()">Reload</x-action-button>
<x-action-button variant="secondary" href="{{ url('/dashboard') }}">Go to dashboard</x-action-button>
@endsection
