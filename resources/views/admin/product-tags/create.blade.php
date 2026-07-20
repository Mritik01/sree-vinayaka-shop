@extends('admin.layout')

@section('title', 'Add Tag')
@section('page-title', 'Add Tag')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-lg">
        <form method="POST" action="{{ route('admin.product-tags.store') }}">
            @csrf
            @include('admin.product-tags._form')
        </form>
    </div>
@endsection
