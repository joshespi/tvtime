@extends('errors.layout')

@section('code', '419')
@section('heading', 'Session expired')
@section('message', "You were signed out while this page sat open, so that last action wasn't saved. Sign in again and give it another go.")
@section('actions')
<x-action-button variant="primary" href="javascript:location.reload()">Reload</x-action-button>
<x-action-button variant="secondary" href="{{ url('/login') }}">Sign in</x-action-button>
@endsection
