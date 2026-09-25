@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            <p><i class="fi fi-br-edit mr_15_icc"></i>
                {{ trans('global.create') }} {{ trans('cruds.plan.title_singular') }} </p>
        </div>

        <div class="card-body">
           <form method="POST" action="{{ route('admin.plans.store') }}" enctype="multipart/form-data">

                @csrf
                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" placeholder="Enter plan name"
                        id="name" value="{{ old('name', '') }}" required>
                    @if ($errors->has('name'))
                        <div class="invalid-feedback">
                            {{ $errors->first('name') }}
                        </div>
                    @endif

                </div>
                <div class="form-group">
                    <label class="required" for="email">Price</label>
                    <input class="form-control {{ $errors->has('price') ? 'is-invalid' : '' }}" type="number" step="0.01" min="0"
                        name="price" id="price" value="{{ old('price') }}" placeholder="Enter plan price">
                </div>
                <div class="form-group">
                    <label class="required" for="duration">
                        Duration
                    </label>

                    <div class="d-flex gap-2">
                        <input class="form-control {{ $errors->has('duration') ? 'is-invalid' : '' }}" type="number"
                            name="duration" id="duration" min="1" value="{{ old('duration') }}" placeholder="Enter plan duration">

                        <select class="form-control {{ $errors->has('duration_type') ? 'is-invalid' : '' }}"
                            name="duration_type" required>
                            <option value="days">Days</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>

                    @if ($errors->has('duration'))
                        <div class="invalid-feedback d-block">
                            {{ $errors->first('duration') }}
                        </div>
                    @endif
                    <span class="help-block">Example: 30 Days, 6 Months, 1 Year</span>
                </div>
                <div class="form-group">
                    <label for="description">
                       Description
                    </label>

                    <textarea class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" name="description"
                        id="description" rows="3" placeholder="Enter plan description">{{ old('description') }}</textarea>

                    @if ($errors->has('description'))
                        <div class="invalid-feedback">
                            {{ $errors->first('description') }}
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="features">Features</label>
                    <textarea class="form-control {{ $errors->has('features') ? 'is-invalid' : '' }}"
                        name="features"
                        id="features"
                        rows="4"
                        placeholder="Enter one feature per line">{{ old('features') }}</textarea>

                    @if ($errors->has('features'))
                        <div class="invalid-feedback">
                            {{ $errors->first('features') }}
                        </div>
                    @endif
                    <span class="help-block">Example: Unlimited paid articles, Magazine reader access, Premium archive</span>
                </div>


                <div class="form-group">
                    <button class=" btn btn-success min-w-200 " type="submit">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
