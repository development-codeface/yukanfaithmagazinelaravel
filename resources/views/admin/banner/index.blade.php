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

            <p> <i class="fi fi-br-list mr_15_icc"></i> {{ trans('cruds.banner.title') }} {{ trans('global.list') }}
                {{-- @can('user_create') --}}
            </p>
            <div style="margin-bottom: 10px;" class="row">
                <div class="col-lg-12">
                    <a class="btn btn-success " href="{{ route('admin.banners.create') }}">
                        <i class="fi fi-br-plus-small mr_5"></i>
                        {{ trans('global.add') }} {{ trans('cruds.banner.title_singular') }}
                    </a>
                </div>
            </div>
            {{-- @endcan --}}
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fi fi-br-check mr-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-User">
                    <thead>
                        <tr>

                            <th>
                                <i class="fi fi-br-hastag ictabl "></i>
                                ID
                            </th>
                            <th></i>
                                Image
                            </th>
                            <th>
                                Status
                            </th>

                            <th>
                                <i class="fi fi-br-apps-add ictabl"></i>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $key => $banner)
                            <tr data-entry-id="{{ $banner->id }}">

                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <img src="{{ asset($banner->image) }}"
                                        style="width:80px;height:50px;object-fit:cover;">
                                </td>
                                <td>
                                    <form action="{{ route('admin.banners.status.change', $banner->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Change banner status?')"
                                            class="btn btn-sm {{ $banner->status == 1 ? 'btn-success' : 'btn-danger' }}">
                                            {{ $banner->status == 1 ? '🟢 Active' : '🔴 Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">

                                        {{-- @can('user_edit') --}}
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.banners.edit',$banner->id) }}">
                                            <!-- {{ trans('global.edit') }}  -->
                                            <i class="fi fi-br-list"></i>
                                        </a>
                                        {{-- @endcan --}}

                                        {{-- @can('user_delete') --}}
                                        <form action="{{ route('admin.banners.destroy',$banner->id) }}" method="POST"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                            <button input type="submit" class="btn btn-xs btn-danger" value="">
                                                <i class="fi fi-br-trash"></i> </button>
                                        </form>
                                        {{-- @endcan --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @parent
    <script src="{{ asset('css/vendor/global/global.min.js') }}"></script>
    <script>
        $(function() {
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
            @can('user_delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.users.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var ids = $.map(dt.rows({
                            selected: true
                        }).nodes(), function(entry) {
                            return $(entry).data('entry-id')
                        });

                        if (ids.length === 0) {
                            alert('{{ trans('global.datatables.zero_selected') }}')

                            return
                        }

                        if (confirm('{{ trans('global.areYouSure') }}')) {
                            $.ajax({
                                    headers: {
                                        'x-csrf-token': _token
                                    },
                                    method: 'POST',
                                    url: config.url,
                                    data: {
                                        ids: ids,
                                        _method: 'DELETE'
                                    }
                                })
                                .done(function() {
                                    location.reload()
                                })
                        }
                    }
                }
                dtButtons.push(deleteButton)
            @endcan

            $.extend(true, $.fn.dataTable.defaults, {
                order: [
                    [1, 'desc']
                ],
                pageLength: 100,
            });
            $('.datatable-User:not(.ajaxTable)').DataTable({
                buttons: dtButtons
            })
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });
        });

        setTimeout(() => {
            let alert = document.querySelector('.alert-success');
            if (alert) {
                alert.classList.remove('show');
                alert.classList.add('fade');
            }
        }, 3000);
    </script>
@endsection
