@extends('admin.layout')

@section('title', 'Edit Coupon')
@section('page-title', 'Edit Coupon')

@section('content')
    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
            @csrf
            @method('PUT')
            @include('admin.coupons._form')
        </form>
    </div>
@endsection
