@extends('admin.layout')

@section('title', 'Announcement Banner')
@section('page-title', 'Announcement Banner')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-7xl">
        <form method="POST" action="{{ route('admin.announcement.update') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.announcement._form')
        </form>
    </div>
@endsection
