@extends('admin.layout')

@section('title', 'Edit Featured Category')
@section('page-title', 'Edit Featured Category')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.featured-categories.update', $category) }}" enctype="multipart/form-data"
              x-data="featuredCategoryImageCropper({{ $category->image_path ? Illuminate\Support\Js::from(asset($category->image_path)) : 'null' }})" @submit="beforeSubmit()">
            @csrf
            @method('PUT')
            @include('admin.featured-categories._form')
        </form>
    </div>
@endsection
