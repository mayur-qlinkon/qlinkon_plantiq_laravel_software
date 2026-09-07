@extends('layouts.admin')

@section('title', 'Profile Not Set Up')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Employee Portal</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Your profile is pending setup</p>
    </div>
@endsection

@section('content')
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center max-w-md px-6">
            <div
                class="w-20 h-20 bg-amber-50 border-2 border-amber-200 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i data-lucide="user-x" class="w-10 h-10 text-amber-400"></i>
            </div>
            <h2 class="text-xl font-black text-gray-800 mb-2">Employee Profile Not Set Up</h2>
            <p class="text-sm text-gray-500 leading-relaxed mb-6">
                Your user account hasn't been linked to an employee record yet.
                Please contact your HR administrator to complete your profile setup.
            </p>
            <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-left mb-6">
                <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider mb-1">What HR needs to do</p>
                <p class="text-[12px] text-amber-800">Go to <strong>HRM → Employees → Create Employee</strong> and link your
                    user account (<strong>{{ auth()->user()->name }}</strong>) to a new employee record.</p>
            </div>
            <a href="{{ route('admin.employee.dashboard') }}"
                class="inline-flex items-center gap-2 text-[13px] font-bold px-5 py-2.5 rounded-xl text-white hover:opacity-90 transition"
                style="background: var(--brand-600)">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Try Again
            </a>
        </div>
    </div>
@endsection
