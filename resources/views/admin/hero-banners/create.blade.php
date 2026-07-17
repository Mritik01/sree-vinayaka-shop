@extends('admin.layout')

@section('title', 'Add Hero Banner')
@section('page-title', 'Add Hero Banner')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.hero-banners.store') }}" enctype="multipart/form-data"
              x-data="heroBannerCropper(null, @js(old('title', '')))" @submit="beforeSubmit()">
            @csrf
            @include('admin.hero-banners._form')
        </form>
    </div>
@endsection
