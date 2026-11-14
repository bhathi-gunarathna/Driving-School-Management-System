@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-heading', 'Dashboard')

@section('content')

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Dashboard Overview
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Here's what's happening at your driving school.
            </p>
        </div>

        <div class="text-sm bg-white border border-slate-200 rounded-lg px-4 py-2">
            {{ now()->format('l, d F Y') }}
        </div>

    </div>


    {{-- Statistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

        @php
            $statistics = [
                [
                    'title' => 'Total Students',
                    'value' => '0',
                    'description' => 'All registered students',
                    'icon' => '♙',
                ],
                [
                    'title' => 'Active Students',
                    'value' => '0',
                    'description' => 'Currently enrolled',
                    'icon' => '◉',
                ],
                [
                    'title' => 'Total Income',
                    'value' => 'Rs. 0',
                    'description' => 'Actual received payments',
                    'icon' => '₨',
                ],
                [
                    'title' => 'Pending Payments',
                    'value' => 'Rs. 0',
                    'description' => 'Outstanding balances',
                    'icon' => '◷',
                ],
            ];
        @endphp


        @foreach ($statistics as $stat)

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <p class="text-sm text-slate-500 font-medium">
                        {{ $stat['title'] }}
                    </p>

                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        {{ $stat['icon'] }}
                    </div>

                </div>

                <h2 class="text-2xl font-bold text-slate-900 mt-5">
                    {{ $stat['value'] }}
                </h2>

                <p class="text-xs text-slate-400 mt-2">
                    {{ $stat['description'] }}
                </p>

            </div>

        @endforeach

    </div>


    {{-- Secondary Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-6">

            <p class="text-sm text-slate-500">Total Coaches</p>

            <h2 class="text-2xl font-bold mt-3">0</h2>

            <p class="text-xs text-slate-400 mt-2">
                Registered instructors
            </p>

        </div>


        <div class="bg-white border border-slate-200 rounded-2xl p-6">

            <p class="text-sm text-slate-500">Upcoming Trials</p>

            <h2 class="text-2xl font-bold mt-3">0</h2>

            <p class="text-xs text-slate-400 mt-2">
                Scheduled examinations
            </p>

        </div>


        <div class="bg-white border border-slate-200 rounded-2xl p-6">

            <p class="text-sm text-slate-500">Training Sessions</p>

            <h2 class="text-2xl font-bold mt-3">0</h2>

            <p class="text-xs text-slate-400 mt-2">
                Today's training sessions
            </p>

        </div>

    </div>


    {{-- Main Dashboard Sections --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-8">

        {{-- Income Chart Placeholder --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6">

            <div class="mb-6">

                <h3 class="font-semibold text-slate-900">
                    Income Overview
                </h3>

                <p class="text-xs text-slate-500 mt-1">
                    Monthly revenue performance
                </p>

            </div>

            <div class="h-64 flex items-center justify-center bg-slate-50 rounded-xl">

                <p class="text-sm text-slate-400">
                    Income chart will appear here.
                </p>

            </div>

        </div>


        {{-- Registration Chart Placeholder --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6">

            <div class="mb-6">

                <h3 class="font-semibold text-slate-900">
                    Student Registrations
                </h3>

                <p class="text-xs text-slate-500 mt-1">
                    Monthly registration statistics
                </p>

            </div>

            <div class="h-64 flex items-center justify-center bg-slate-50 rounded-xl">

                <p class="text-sm text-slate-400">
                    Registration chart will appear here.
                </p>

            </div>

        </div>

    </div>


    {{-- Recent Activity --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-6 mt-8">

        <h3 class="font-semibold text-slate-900">
            Recent Activities
        </h3>

        <p class="text-sm text-slate-500 mt-1">
            Recent activities will appear here.
        </p>

        <div class="py-12 text-center">

            <p class="text-sm text-slate-400">
                No recent activities available.
            </p>

        </div>

    </div>

@endsection