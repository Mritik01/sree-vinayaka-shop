@extends('admin.layout')

@section('title', 'Edit Admin')
@section('page-title', 'Edit Admin')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
            @csrf
            @method('PUT')
            @include('admin.admins._form')
        </form>
    </div>
@endsection
