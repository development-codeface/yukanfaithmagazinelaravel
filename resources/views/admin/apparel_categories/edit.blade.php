@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-edit mr_15_icc"></i> Edit Apparel Category</p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.apparel-categories.update', $apparelCategory) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.apparel_categories.partials.form', ['category' => $apparelCategory])
        </form>
    </div>
</div>
@endsection
