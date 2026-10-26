@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-edit mr_15_icc"></i> Add Apparel Category</p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.apparel-categories.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.apparel_categories.partials.form', ['category' => null])
        </form>
    </div>
</div>
@endsection
