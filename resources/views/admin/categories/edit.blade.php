@extends('admin.layout')

@section('title', 'Edit Category')
@section('page-title', 'Edit Category')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data"
              x-data="categoryImageCropper({{ $category->image_path ? Illuminate\Support\Js::from(asset($category->image_path)) : 'null' }})" @submit="beforeSubmit()">
            @csrf
            @method('PUT')
            @include('admin.categories._form')
        </form>
    </div>
@endsection
