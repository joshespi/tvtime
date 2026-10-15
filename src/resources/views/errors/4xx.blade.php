@extends('errors.layout')

@section('code', $exception->getStatusCode())
@section('heading', 'Request not allowed')
@section('message', "That request couldn't be completed.")
@section('actions')
<x-action-button variant="primary" href="{{ url('/dashboard') }}">Go to dashboard</x-action-button>
@endsection
