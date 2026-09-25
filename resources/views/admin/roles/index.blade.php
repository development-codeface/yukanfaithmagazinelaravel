@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            {{ trans('cruds.role.title_singular') }} {{ trans('global.list') }}
            @can('role_create')
                <div class="row">
                    <div class="col-lg-12">
                        <a class="btn btn-success" href="{{ route('admin.roles.create') }}">
                            <i class="fi fi-br-plus-small mr_5"></i> {{ trans('global.add') }}
                            {{ trans('cruds.role.title_singular') }}
                        </a>
                    </div>
                </div>
            @endcan
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Role">
                    <thead>
                        <tr>
                            <th width="10">

                            </th>
                            <th>
                                {{ trans('cruds.role.fields.id') }}
                            </th>
                            <th>
                                {{ trans('cruds.role.fields.title') }}
                            </th>
                            <th>
                                {{ trans('cruds.role.fields.permissions') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $key => $role)
                            <tr data-entry-id="{{ $role->id }}">
                                <td>

                                </td>
                                <td>
                                    {{ $role->id ?? '' }}
                                </td>
                                <td>
                                    {{ $role->title ?? '' }}
                                </td>
                                <td>
                                    @foreach ($role->permissions as $key => $item)
                                        <span class="badge badge-info">{{ $item->title }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @can('role_show')
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.roles.show', $role->id) }}">
                                            <i class="fi fi-br-eye"></i>
                                        </a>
                                    @endcan

                                    @can('role_edit')
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.roles.edit', $role->id) }}">
                                            <i class="fi fi-br-list"></i>
                                        </a>
                                    @endcan

                                    @can('role_delete')
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-block;">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fi fi-br-trash"></i>
                                            </button>
                                        </form>
                                    @endcan

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
    <script>
    $(function () {

        // Clone default buttons
        let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

        // ❌ Remove built-in Select / Deselect buttons
        dtButtons = dtButtons.filter(btn => {
            return btn.extend !== 'selectAll' && btn.extend !== 'selectNone'
        })

        // ✅ Single Toggle Button
        let toggleSelectButton = {
            text: 'Select all',
            className: 'btn buttons-select-all btn-primary',
            action: function (e, dt, node) {

                let total = dt.rows({ search: 'applied' }).count()
                let selected = dt.rows({ selected: true }).count()

                if (selected === total) {
                    dt.rows().deselect()
                    node.text('Select all')
                } else {
                    dt.rows({ search: 'applied' }).select()
                    node.text('Deselect all')
                }
            }
        }

        // Add button as FIRST button
        dtButtons.unshift(toggleSelectButton)

        @can('role_delete')
        let deleteButton = {
            text: '{{ trans('global.datatables.delete') }}',
            url: "{{ route('admin.roles.massDestroy') }}",
            className: 'btn btn-danger',
            action: function (e, dt) {

                let ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
                    return $(entry).data('entry-id')
                })

                if (ids.length === 0) {
                    alert('{{ trans('global.datatables.zero_selected') }}')
                    return
                }

                if (confirm('{{ trans('global.areYouSure') }}')) {
                    $.ajax({
                        headers: { 'x-csrf-token': _token },
                        method: 'POST',
                        url: "{{ route('admin.roles.massDestroy') }}",
                        data: { ids: ids, _method: 'DELETE' }
                    }).done(() => location.reload())
                }
            }
        }
        dtButtons.push(deleteButton)
        @endcan

        // DataTable init
      let table;

        if (!$.fn.DataTable.isDataTable('.datatable-Role')) {
            table = $('.datatable-Role:not(.ajaxTable)').DataTable({
                buttons: dtButtons,
                order: [[1, 'desc']],
                pageLength: 100,
                select: {
                    style: 'multi'
                }
            });
        } else {
            table = $('.datatable-Role').DataTable();
        }


        // 🔄 Sync button text on manual select
        table.on('select deselect', function () {
            let total = table.rows({ search: 'applied' }).count()
            let selected = table.rows({ selected: true }).count()

            table.button(0).text(
                selected === total ? 'Deselect all' : 'Select all'
            )
        })

    })
</script>

@endsection
