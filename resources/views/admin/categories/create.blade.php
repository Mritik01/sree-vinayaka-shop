@extends('admin.layout')

@section('title', 'Add Category')
@section('page-title', 'Add Category')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" x-data="categoryImageCropper(null)" @submit="beforeSubmit()">
            @csrf
            @include('admin.categories._form')
        </form>
    </div>
@endsection
