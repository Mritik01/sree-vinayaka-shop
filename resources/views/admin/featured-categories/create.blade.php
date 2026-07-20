@extends('admin.layout')

@section('title', 'Add Featured Category')
@section('page-title', 'Add Featured Category')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.featured-categories.store') }}" enctype="multipart/form-data"
              x-data="featuredCategoryImageCropper(null)" @submit="beforeSubmit()">
            @csrf
            @include('admin.featured-categories._form')
        </form>
    </div>
@endsection
