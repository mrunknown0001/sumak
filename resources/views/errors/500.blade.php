@extends('errors.layout')

@section('title', __('Server error'))
@section('code', '500')

@section('message')
    {{ __('Something on our side broke before we could finish your request.') }}
@endsection

@section('extra')
    <p class="leading-relaxed">
        {{ __('Our engineers are already looking into it. Give it a moment, then refresh the page or come back shortly.') }}
    </p>
@endsection