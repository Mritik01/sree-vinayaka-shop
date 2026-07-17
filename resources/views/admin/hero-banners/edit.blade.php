@extends('admin.layout')

@section('title', 'Edit Hero Banner')
@section('page-title', 'Edit Hero Banner')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.hero-banners.update', $banner) }}" enctype="multipart/form-data"
              x-data="heroBannerCropper(@js(asset($banner->image_path)), @js(old('title', $banner->title)))" @submit="beforeSubmit()">
            @csrf
            @method('PUT')
            @include('admin.hero-banners._form')
        </form>
    </div>
@endsection
