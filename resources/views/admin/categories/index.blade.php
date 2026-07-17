@extends('admin.layout')

@section('title', 'Categories')
@section('page-title', 'Categories')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="categories-results" placeholder="Search category name…" />
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn-gold">+ Add Category</a>
    </div>

    <div id="categories-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.categories._results')
    </div>
@endsection
