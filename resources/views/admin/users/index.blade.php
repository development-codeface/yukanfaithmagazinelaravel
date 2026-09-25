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

            <p> <i class="fi fi-br-list mr_15_icc"></i> {{ trans('cruds.user.title') }} {{ trans('global.list') }}
                @can('user_create')
                </p>
                <div style="margin-bottom: 10px;" class="row">
                    <div class="col-lg-12">
                        <a class="btn btn-success " href="{{ route('admin.users.create') }}">
                            <i class="fi fi-br-plus-small mr_5"></i>
                            {{ trans('global.add') }} {{ trans('cruds.user.title_singular') }}
                        </a>
                    </div>
                </div>
            @endcan
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-User">
                    <thead>
                        <tr>
                        
                            <th>
                                <i class="fi fi-br-hastag ictabl "></i>
                                {{ trans('cruds.user.fields.id') }}
                            </th>
                            <th><i class="fi fi-br-users-alt ictabl"></i>
                                {{ trans('cruds.user.fields.name') }}
                            </th>
                            <th>
                                <i class="fi fi-br-envelope ictabl"></i>
                                {{ trans('cruds.user.fields.email') }}
                            </th>
                            <!--<th>
                                    {{ trans('cruds.user.fields.email_verified_at') }}
                                </th> -->
                            <th><i class="fi fi-sr-apps-sort ictabl"></i>
                                {{ trans('cruds.user.fields.roles') }}
                            </th>
                            <th>
                                <i class="fi fi-br-apps-add ictabl"></i>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $key => $user)
                            <tr data-entry-id="{{ $user->id }}">
                                
                                <td>
                                    {{ $user->id ?? '' }}
                                </td>
                                <td>
                                    {{ $user->name ?? '' }}
                                </td>
                                <td>
                                    {{ $user->email ?? '' }}
                                </td>
                                <!--<td>
                                        {{ $user->email_verified_at ?? '' }}
                                    </td>-->
                                <td>
                                    @foreach ($user->roles as $key => $item)
                                        <span class="badge badge-info">{{ $item->title }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        @can('user_show')
                                            <a class="btn btn-xs btn-primary"
                                                href="{{ route('admin.users.show', $user->id) }}">
                                                <!--{{ trans('global.view') }} -->
                                                <i class="fi fi-br-eye"></i>
                                            </a>
                                        @endcan

                                        @can('user_edit')
                                            <a class="btn btn-xs btn-info" href="{{ route('admin.users.edit', $user->id) }}">
                                                <!-- {{ trans('global.edit') }}  -->
                                                <i class="fi fi-br-list"></i>
                                            </a>
                                        @endcan

                                       
                                        @if ($user->is_blocked)
                                            <!-- Unblock Button -->
                                            <form action="{{ route('admin.users.unblock', $user->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-xs btn-warning">
                                                    <i class="fi fi-br-ban de-active"></i> Unblock
                                                </button>
                                            </form>
                                        @else
                                            <!-- Block Button -->
                                            <form action="{{ route('admin.users.block', $user->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-xs btn-secondary">
                                                    <i class="fi fi-br-ban de-active"></i> Block
                                                </button>
                                            </form>
                                        @endif
                                  
                                        @can('user_delete')
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                                onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                                style="display: inline-block;">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                                <button input type="submit" class="btn btn-xs btn-danger" value="">
                                                    <i class="fi fi-br-trash"></i> </button>

                                                <!-- need to add below icon insted od delete text -->
                                                <!-- <i class="fa-regular fa-trash-can"></i> -->
                                            </form>
                                        @endcan
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
        })
    </script>
@endsection
