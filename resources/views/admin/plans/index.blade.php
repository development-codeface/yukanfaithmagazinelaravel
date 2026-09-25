@extends('layouts.admin')
@section('content')

<style>
    td .action-buttons {
        display: flex;
        gap: 5px;
    }
</style>

<div class="card">
    <div class="card-header">
        <p>
            <i class="fi fi-br-list mr_15_icc"></i>
           Subscription Plans
        </p>

        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.plans.create') }}">
                    <i class="fi fi-br-plus-small mr_5"></i>
                    Add Plan
                </a>
            </div>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fi fi-br-check mr-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>{{ $plan->id }}</td>
                            <td>{{ $plan->name }}</td>
                            <td>INR {{ number_format((float) $plan->price, 2) }}</td>
                            <td>
                                {{ $plan->duration }} {{ ucfirst($plan->duration_type) }}
                            </td>

                            {{-- Status Toggle --}}
                          <td>
                                <form action="{{ route('admin.plans.status', $plan->id) }}" method="GET">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            onchange="this.form.submit()"
                                            {{ $plan->status ? 'checked' : '' }}>
                                    </div>
                                </form>
                            </td>


                            {{-- Actions --}}
                            <td>
                                <div class="action-buttons">

                                    {{-- Edit --}}
                                    <a class="btn btn-xs btn-info"
                                       href="{{ route('admin.plans.edit', $plan->id) }}">
                                        <i class="fi fi-br-edit"></i>
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('admin.plans.destroy', $plan->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-xs btn-danger">
                                            <i class="fi fi-br-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                No Plans Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
</div>
@endsection
<style>
.form-check-input {
    width: 45px;
    height: 22px;
    cursor: pointer;
}
 </style>
