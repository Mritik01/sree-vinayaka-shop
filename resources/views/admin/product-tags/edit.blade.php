@extends('admin.layout')

@section('title', 'Edit Tag')
@section('page-title', 'Edit Tag')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-lg">
        <form method="POST" action="{{ route('admin.product-tags.update', $tag) }}">
            @csrf
            @method('PUT')
            @include('admin.product-tags._form')
        </form>
    </div>
@endsection
