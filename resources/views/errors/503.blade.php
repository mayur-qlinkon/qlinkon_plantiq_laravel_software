@extends('errors.layout')

@section('badge', 'Under Maintenance')
@section('code', '503')
@section('title', 'We\'ll be right back')
@section('description', 'The system is currently undergoing scheduled maintenance. We apologize for the inconvenience — please check back shortly.')

@section('actions')
    <a href="javascript:location.reload()" class="btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
        Check Again
    </a>
@endsection
