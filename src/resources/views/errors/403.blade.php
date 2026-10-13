@extends('errors.layout')

@section('code', '403')
@section('heading', 'Not allowed')
@section('message', "You don't have access to that.")
@section('actions')
<x-action-button variant="primary" href="{{ url('/dashboard') }}">Go to dashboard</x-action-button>
@endsection
