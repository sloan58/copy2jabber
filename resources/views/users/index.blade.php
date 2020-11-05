@extends('layouts.app')

@section('main-content')

    <div class="container-fluid mt--7">
        <div class="row">
            <div class="col">
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="offset-8 col-4 text-right">
                                <a href="{{ route('user.create') }}"
                                   class="btn btn-sm btn-primary">{{ __('Add User') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive py-4">
                        <table class="table align-items-center table-flush" id="datatable">
                            <thead class="thead-light">
                            <tr>
                                <th scope="col">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Email') }}</th>
                                <th scope="col">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            $(function () {
                $('#datatable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    ajax: '/user',
                    columns: [
                        {data: 'name'},
                        {data: 'email'},
                        {data: 'actions'},
                    ],
                    language: {
                        paginate: {
                            previous: "<i class='fas fa-angle-left'>",
                            next: "<i class='fas fa-angle-right'>"
                        }
                    },
                });
            });
        </script>
    @endpush

@endsection
