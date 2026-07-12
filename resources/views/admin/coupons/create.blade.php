@extends('admin.layout')

@section('title', 'Add Coupon')
@section('page-title', 'Add Coupon')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            @include('admin.coupons._form')
        </form>
    </div>
@endsection
