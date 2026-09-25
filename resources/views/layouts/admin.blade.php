<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible"
          content="ie=edge">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>{{ trans('panel.site_title') }}</title>

    {{-- ================= JQUERY ================= --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    {{-- ================= BOOTSTRAP 4 ================= --}}
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          rel="stylesheet" />

    {{-- ================= FONT AWESOME ================= --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css"
          rel="stylesheet" />

    <link href="https://use.fontawesome.com/releases/v5.2.0/css/all.css"
          rel="stylesheet" />

    {{-- ================= DATATABLES ================= --}}
    <link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"
          rel="stylesheet" />

    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css"
          rel="stylesheet" />

    <link href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css"
          rel="stylesheet" />

    <link href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css"
          rel="stylesheet" />

    {{-- ================= SELECT2 ================= --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css"
          rel="stylesheet" />

    {{-- ================= DATETIME PICKER ================= --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css"
          rel="stylesheet" />

    {{-- ================= CORE UI ================= --}}
    <link href="https://unpkg.com/@coreui/coreui@2.1.16/dist/css/coreui.min.css"
          rel="stylesheet" />

    {{-- ================= DROPZONE ================= --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css"
          rel="stylesheet" />

    {{-- ================= CUSTOM CSS ================= --}}
    <link href="{{ asset('css/custom.css') }}"
          rel="stylesheet" />

    <link href="{{ asset('css/vendor/chartist/css/chartist.min.css') }}"
          rel="stylesheet" />

    {{-- FIXED PATH --}}
    <link href="{{ asset('css/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}"
          rel="stylesheet" />

    <link href="{{ asset('css/style.css') }}"
          rel="stylesheet" />

    @yield('styles')

</head>

<body class="app header-fixed sidebar-fixed aside-menu-fixed pace-done sidebar-lg-show">

    {{-- ================= HEADER ================= --}}
    <header class="app-header navbar">

        <button class="navbar-toggler sidebar-toggler d-lg-none mr-auto"
                type="button"
                data-toggle="sidebar-show">

            <span class="navbar-toggler-icon"></span>

        </button>

        <a class="navbar-brand" href="#">

            <span class="navbar-brand-full">

                <img class="w_104"
                     src="{{ asset('css/img/lg.png') }}"
                     alt="">

            </span>

            <span class="navbar-brand-minimized">
                {{ trans('panel.site_title') }}
            </span>

        </a>

        <ul class="navbar-nav ml-auto">

            @if (count(config('panel.available_languages', [])) > 1)

                <li class="nav-item dropdown d-md-down-none">

                    <a class="nav-link"
                       data-toggle="dropdown"
                       href="#"
                       role="button">

                        {{ strtoupper(app()->getLocale()) }}

                    </a>

                    <div class="dropdown-menu dropdown-menu-right">

                        @foreach (config('panel.available_languages') as $langLocale => $langName)

                            <a class="dropdown-item"
                               href="{{ url()->current() }}?change_language={{ $langLocale }}">

                                {{ strtoupper($langLocale) }}
                                ({{ $langName }})

                            </a>

                        @endforeach

                    </div>

                </li>

            @endif

        </ul>

        {{-- ================= PROFILE ================= --}}
        <ul class="navbar-nav header-right">

            <li class="nav-item dropdown header-profile">

                <a class="nav-link"
                   href="javascript:void(0)"
                   role="button"
                   data-toggle="dropdown">

                    <img src="{{ asset('css/img/17.jpg') }}"
                         width="20"
                         alt="" />

                </a>

                <div class="dropdown-menu dropdown-menu-right">

                    @if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php')))

                        @can('profile_password_edit')

                            <a class="dropdown-item ai-icon {{ request()->routeIs('profile.password.edit') ? 'active' : '' }}"
                               href="{{ route('profile.password.edit') }}">

                                <i class="fa fa-lock nav-icon"></i>

                                {{ trans('global.change_password') }}

                            </a>

                        @endcan

                    @endif

                    <a href="#"
                       onclick="event.preventDefault(); document.getElementById('logoutform').submit();"
                       class="dropdown-item ai-icon">

                        <i class="fa fa-sign-out-alt text-danger"></i>

                        <span class="ml-2">Logout</span>

                    </a>

                </div>

            </li>

        </ul>

    </header>

    {{-- ================= BODY ================= --}}
    <div class="app-body">

        @include('partials.menu')

        <main class="main">

            <div style="padding-top:20px"
                 class="container-fluid">

                {{-- SUCCESS MESSAGE --}}
                @if (session('message'))

                    <div class="row mb-2">

                        <div class="col-lg-12">

                            <div class="alert alert-success">

                                {{ session('message') }}

                            </div>

                        </div>

                    </div>

                @endif

                {{-- VALIDATION ERRORS --}}
                @if ($errors->count() > 0)

                    <div class="alert alert-danger">

                        <ul class="list-unstyled">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                @yield('content')

            </div>

        </main>

        {{-- LOGOUT FORM --}}
        <form id="logoutform"
              action="{{ route('logout') }}"
              method="POST"
              style="display:none;">

            @csrf

        </form>

    </div>

    {{-- ================= JS ================= --}}

    {{-- POPPER --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>

    {{-- BOOTSTRAP 4 --}}
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>

    {{-- CORE UI --}}
    <script src="https://unpkg.com/@coreui/coreui@2.1.16/dist/js/coreui.min.js"></script>

    {{-- DATATABLES --}}
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>

    <script src="//cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>

    <script src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.colVis.min.js"></script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>

    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>

    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>

    <script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>

    {{-- MOMENT --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>

    {{-- DATETIME PICKER --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>

    {{-- SELECT2 --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>

    {{-- DROPZONE --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>

    {{-- CUSTOM --}}
    <script src="{{ asset('js/main.js') }}"></script>

    <script src="{{ asset('css/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>

    <script src="{{ asset('css/vendor/chart.js/Chart.bundle.min.js') }}"></script>

    <script src="{{ asset('js/custom.min.js') }}"></script>

    <script src="{{ asset('js/deznav-init.js') }}"></script>

    <script src="{{ asset('css/vendor/owl-carousel/owl.carousel.js') }}"></script>

    <script src="{{ asset('css/vendor/peity/jquery.peity.min.js') }}"></script>

    <script src="{{ asset('css/vendor/apexchart/apexchart.js') }}"></script>

    {{-- OPTIONAL DASHBOARD --}}
    {{-- <script src="{{ asset('js/dashboard/dashboard-1.js') }}"></script> --}}

    {{-- ================= DATATABLE CONFIG ================= --}}
    <script>

        $(function () {

            $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {
                className: 'btn'
            });

            $.extend(true, $.fn.dataTable.defaults, {

                order: [],
                pageLength: 100,
                scrollX: true

            });

        });

    </script>

    {{-- PAGE SCRIPTS --}}
    @yield('scripts')

</body>

</html>