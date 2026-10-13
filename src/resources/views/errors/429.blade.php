@extends('errors.layout')

@section('code', '429')
@section('heading', 'Slow down')
@section('message', "Too many requests in a short time. Wait a moment, then try again.")
@section('actions')
<x-action-button variant="secondary" href="javascript:history.back()">Go back</x-action-button>
@endsection
