@extends('errors.layout')

@section('title', __('Page expired'))
@section('code', '419')

@section('message')
    {{ __('The page didn’t finish in time, so your session token expired before we could complete the request.') }}
@endsection

@section('extra')
    <p class="leading-relaxed">
        {{ __('Start a fresh session to continue where you left off. If this happens repeatedly, clearing your browser cache can help.') }}
    </p>
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-slate-950">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
            <path d="M10 3l6 4.5V17a1 1 0 01-1 1h-3.5v-4h-3v4H5a1 1 0 01-1-1V7.5L10 3z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        {{ __('Sign in again') }}
    </a>
@endsection