@extends('errors.layout')

@section('title', __('Page not found'))
@section('code', '404')

@section('message')
    {{ __('We couldn’t find the page you were trying to open. It may have been moved or no longer exists.') }}
@endsection

@section('extra')
    <ul class="list-disc space-y-2 pl-5 text-sm">
        <li>{{ __('Check the link for any typos or missing characters.') }}</li>
        <li>{{ __('Use the navigation menu to jump back into the portal.') }}</li>
        <li>{{ __('Reach out to support if you need help locating something specific.') }}</li>
    </ul>
@endsection