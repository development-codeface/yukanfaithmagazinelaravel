@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-edit mr_15_icc"></i> Edit Apparel Product</p>
    </div>

    <div class="card-body">
        <form class="apparel-product-form" method="POST" action="{{ route('admin.apparel-products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.apparel_products.partials.form')
        </form>
    </div>
</div>
@endsection
