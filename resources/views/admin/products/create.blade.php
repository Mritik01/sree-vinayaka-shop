@extends('admin.layout')

@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-7xl">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.products._form')
        </form>
    </div>
@endsection
