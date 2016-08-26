@extends('admin.layouts.default')

@section('pageTitle', $pageTitle)

@section('additionalStyles')
@stop

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>

                <div class="box-body">
                    <div id="data-table_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
                        @if(isset($createRoute))
                            <a href="{{ route($createRoute) }}"
                               class="btn btn-success pull-left">+ {{ trans('admin.add') }} {{ str_singular($pageTitle) }}</a>
                        @endif
                        <div class="row">
                            <div class="col-sm-12">
                                <table id="data-table" class="table table-bordered table-striped dataTable" role="grid"
                                       aria-describedby="data-table_info">
                                    <thead>
                                    <tr>
                                        @foreach($model->getAdminTableColumns() as $col)
                                            <th class="{{ array_get($col, 'type') }}">{{ $col['name'] }}</th>
                                        @endforeach
                                        <th class="no-sort actions-col">{{ trans('admin.actions') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="sortable" data-entityname="{{ strtolower($pageTitle) }}">
                                    @forelse ($records as $record)
                                        <tr data-itemId="{{ $record->id }}">
                                            @foreach($model->getAdminTableColumns() as $col)
                                                <td data-order="{{ $record->{$col['db_field']} }}">
                                                    {!! $model->renderTableRow($record, $col) !!}
                                                </td>
                                            @endforeach
                                            <td class="actions-cell">
                                                @if(isset($editRoute))
                                                    <a href="{{ route($editRoute, ['id' => $record->id]) }}"
                                                       class="btn btn-primary">
                                                        {{ trans('admin.edit') }}
                                                    </a>
                                                @endif

                                                @if(isset($deleteRoute))
                                                    <a href="#"
                                                       class="js-open-delete-modal btn btn-danger"
                                                       data-record="{{ json_encode($record->toArray()) }}"
                                                       data-delete-url="{{ route($deleteRoute, ['id' => $record->id]) }}">
                                                        {{ trans('admin.delete') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            @foreach($model->getAdminTableColumns() as $key => $col)
                                                @if($key == 0)
                                                    <td>{{ trans('admin.noData') }}</td>
                                                @else
                                                    <td>-</td>
                                                @endif
                                            @endforeach
                                            <td>-</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        @foreach($model->getAdminTableColumns() as $col)
                                            <th>{{ $col['name'] }}</th>
                                        @endforeach
                                        <th>{{ trans('admin.actions') }}</th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($deleteRoute))
        <div class="modal modal-danger fade" id="delete-modal" tabindex="-1" role="dialog"
             aria-labelledby="deleteModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-target="#delete-modal" data-dismiss="modal"
                                aria-label="{{ trans('admin.close') }}"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">{{ trans('admin.deleteTitle') }}</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ trans('admin.deleteConfirm') }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline pull-left" data-target="#delete-modal"
                                data-dismiss="modal">{{ trans('admin.close') }}</button>
                        <form method="POST" action="" class="delete-form">
                            <input type="hidden" name="_token" value="{!! csrf_token() !!}"/>
                            <input type="hidden" name="_method" value="DELETE"/>

                            <button type="submit" type="button" class="delete-btn btn btn-outline">
                                {{ trans('admin.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('additionalScripts')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-XSRF-Token': $('meta[name="_token"]').attr('content')
            }
        });

        // Sortable
        var changePosition = function (requestData) {
            $.ajax({
                url: '/sort',
                type: 'POST',
                data: requestData,
                success: function (data) {
                    if (data.success) {
                        console.log('Sort: success!');
                    } else {
                        console.log(data.errors);
                    }
                },
                error: function (e) {
                    console.log('Something went wrong! Error(' + e.status + '): ' + e.statusText);
                }
            });
        };

        var $sortableTable = $('.sortable');
        if ($sortableTable.length > 0) {
            $sortableTable.sortable({
                handle: '.sortable-handle',
                axis: 'y',
                update: function (a, b) {
                    var entityName = $(this).data('entityname');
                    var $sorted = b.item;

                    var $previous = $sorted.prev();
                    var $next = $sorted.next();

                    if ($previous.length > 0) {
                        changePosition({
                            parentId: $sorted.data('parentid'),
                            type: 'moveAfter',
                            entityName: entityName,
                            id: $sorted.data('itemid'),
                            positionEntityId: $previous.data('itemid')
                        });
                    } else if ($next.length > 0) {
                        changePosition({
                            parentId: $sorted.data('parentid'),
                            type: 'moveBefore',
                            entityName: entityName,
                            id: $sorted.data('itemid'),
                            positionEntityId: $next.data('itemid')
                        });
                    } else {
                        console.log(a);
                    }
                },
                cursor: "move"
            });
        }

        // Delete entity
        $('.js-open-delete-modal').on('click', function (e) {
            e.preventDefault();
            var that = $(this),
                    $deleteModal = $('#delete-modal'),
                    deleteURL = that.data('delete-url');

            $deleteModal.find('.delete-form').attr('action', deleteURL);

            $deleteModal.modal();
        });

        var isSortable = $('th.sort').length === 0;

        var table = $('#data-table').DataTable({
            paging: isSortable !== false,
            pageLength: {{ config('admin.bootstrap.paginateLimit') }},
            lengthChange: false,
            searching: true,
            ordering: isSortable !== false,
            info: false,
            autoWidth: true,
            columnDefs: [
                {
                    targets: "no-sort",
                    orderable: false,
                    searchable: false
                }
            ],
            aaSorting: [],
            oLanguage: {
                sSearch: "<span class='search-label uppercase'>Search</span>"
            }
        });
    </script>
@stop
