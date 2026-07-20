@extends('admin.layout')

@section('title', 'Add Rider')
@section('page-title', 'Add Rider')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.riders.store') }}" enctype="multipart/form-data"
              x-data="riderPhotoCropper(null)" @submit="beforeSubmit()">
            @csrf
            @include('admin.riders._form')
        </form>
    </div>
@endsection
