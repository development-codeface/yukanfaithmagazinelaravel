@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-edit mr_15_icc"></i> Add Apparel Product</p>
    </div>

    <div class="card-body">
        <form class="apparel-product-form" method="POST" action="{{ route('admin.apparel-products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.apparel_products.partials.form')
        </form>
    </div>
</div>
@endsection
