@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            <p><i class="fi fi-br-edit mr_15_icc"></i>
                Edit Plan
            </p>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $plan->name) }}"
                        required>
                </div>

                <div class="form-group">
                    <label class="required" for="price">Price</label>
                    <input class="form-control"
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        id="price"
                        value="{{ old('price', $plan->price) }}">
                </div>

                <div class="form-group">
                    <label class="required">Duration</label>

                    <div class="d-flex gap-2">
                        <input class="form-control"
                            type="number"
                            name="duration"
                            min="1"
                            value="{{ old('duration', $plan->duration) }}">

                        <select class="form-control" name="duration_type">
                            <option value="days" {{ $plan->duration_type == 'days' ? 'selected' : '' }}>Days</option>
                            <option value="months" {{ $plan->duration_type == 'months' ? 'selected' : '' }}>Months</option>
                            <option value="years" {{ $plan->duration_type == 'years' ? 'selected' : '' }}>Years</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control"
                        name="description"
                        rows="3">{{ old('description', $plan->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Features</label>
                    <textarea class="form-control"
                        name="features"
                        rows="4"
                        placeholder="Enter one feature per line">{{ old('features', $plan->features) }}</textarea>
                    <span class="help-block">Example: Unlimited paid articles, Magazine reader access, Premium archive</span>
                </div>

                <div class="form-group">
                    <button class="btn btn-success min-w-200" type="submit">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
