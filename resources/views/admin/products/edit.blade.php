@extends('admin.layout')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-7xl">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.products._form')
        </form>
    </div>
@endsection
