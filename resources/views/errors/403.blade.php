@extends('errors.layout')

@section('badge', 'Access Denied')
@section('code', '403')
@section('title', 'You don\'t have permission')
@section('description', "You don't have the required permissions to view this page. If you believe this is a mistake, contact your administrator.")

@section('actions')
    <a href="{{ homeUrl() }}" class="btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Go Home
    </a>
    <a href="javascript:history.back()" class="btn-ghost">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Go Back
    </a>
@endsection
