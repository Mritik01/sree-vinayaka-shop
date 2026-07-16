@extends('admin.layout')

@section('title', 'Add Admin')
@section('page-title', 'Add Admin')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.admins.store') }}">
            @csrf
            @include('admin.admins._form')
        </form>
    </div>
@endsection
