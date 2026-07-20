@extends('admin.layout')

@section('title', 'Edit Rider')
@section('page-title', 'Edit Rider')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.riders.update', $rider) }}" enctype="multipart/form-data"
              x-data="riderPhotoCropper({{ $rider->photo_path ? Illuminate\Support\Js::from(asset($rider->photo_path)) : 'null' }})" @submit="beforeSubmit()">
            @csrf
            @method('PUT')
            @include('admin.riders._form')
        </form>
    </div>
@endsection
