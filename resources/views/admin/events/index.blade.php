@extends('layouts.admin')

@section('content')

<style>
td .action-buttons {
    display:flex;
    gap:5px;
}
.form-check-input{
    width:45px;
    height:22px;
    cursor:pointer;
}
</style>

<div class="card">

    <div class="card-header">

        <p>
            <i class="fi fi-br-list mr_15_icc"></i>
            Events
        </p>

        <div class="row mb-2">
            <div class="col-lg-12">
                <a class="btn btn-success"
                   href="{{ route('admin.events.create') }}">
                    <i class="fi fi-br-plus-small mr_5"></i>
                    Add Event
                </a>
            </div>
        </div>

    </div>


    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3">
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
                    <th>Image</th>
                    <th>Title</th>
                    <th>Subtitle</th>
                    <th width="150">Actions</th>
                </tr>
                </thead>

                <tbody>

                @forelse($events as $event)

                    <tr>

                        <td>{{ $event->id }}</td>

                        {{-- IMAGE --}}
                        <td>
                            @if($event->image)
                                <img src="{{ asset($event->image) }}"
                                     width="80">
                            @endif
                        </td>

                        <td>{{ $event->title }}</td>

                        <td>{{ $event->subtitle }}</td>

                        {{-- ACTIONS --}}
                        <td>

                            <div class="action-buttons">

                                {{-- EDIT --}}
                                <a class="btn btn-xs btn-info"
                                   href="{{ route('admin.events.edit',$event->id) }}">
                                    <i class="fi fi-br-edit"></i>
                                </a>


                                {{-- DELETE --}}
                                <form action="{{ route('admin.events.destroy',$event->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this event?')">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-xs btn-danger">
                                        <i class="fi fi-br-trash"></i>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="text-center">
                            No Events Found
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
