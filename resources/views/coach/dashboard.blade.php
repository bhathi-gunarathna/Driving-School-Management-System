@extends('layouts.coach')

@section('title', 'Coach Dashboard')

@section('content')

    <h1 class="text-2xl font-bold">
        Welcome, {{ auth()->user()->name }}
    </h1>

    <p class="text-slate-500 mt-2">
        Your training and trial management dashboard.
    </p>

@endsection