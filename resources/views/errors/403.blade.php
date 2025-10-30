@extends('errors.layout')

@section('title', __('Forbidden'))
@section('code', '403')

@section('message')
    {{ __('You’re signed in, but your account doesn’t have permission to open this page.') }}
@endsection

@section('extra')
    <p class="leading-relaxed">
        {{ __('If you think you should be able to view it, please contact your administrator or support so they can review your access level.') }}
    </p>
@endsection