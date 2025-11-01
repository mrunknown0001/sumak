@extends('errors.layout')

@section('title', __('Service unavailable'))
@section('code', '503')

@section('message')
    {{ __('We’re taking the system offline briefly for scheduled maintenance.') }}
@endsection

@section('extra')
    <p class="leading-relaxed">
        {{ __('Thanks for waiting with us. Refresh the page in a few minutes to see if everything is back online.') }}
    </p>
@endsection