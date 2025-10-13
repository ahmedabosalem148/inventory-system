<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="نظام إدارة المخزون والعملاء">
    <title>@yield('title', 'نظام إدارة المخزون')</title>

    {{-- Bootstrap 5.3 RTL --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    {{-- Custom Styles --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/quick-search.css') }}">
    
    @stack('styles')

    <style>
        /* Layout Base */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .main-wrapper {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 260px;
            background: white;
            border-left: 1px solid #dee2e6;
            padding: 1rem;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            padding: 1.5rem;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #dee2e6;
        }

        /* Sidebar Navigation */
        .sidebar .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover {
            background: #f8f9fa;
            color: #0d6efd;
        }

        .sidebar .nav-link.active {
            background: #0d6efd;
            color: white;
        }

        .sidebar .nav-link i {
            margin-left: 0.5rem;
            width: 20px;
            text-align: center;
        }

        /* Footer */
        footer {
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            {{-- Brand --}}
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-box-seam"></i>
                نظام المخزون
            </a>

            {{-- Search (Desktop only) --}}
            <div class="d-none d-md-block mx-auto" style="max-width: 400px;">
                <div class="autocomplete-wrapper">
                    <input type="text" 
                           class="form-control" 
                           placeholder="بحث سريع..."
                           data-autocomplete="global"
                           data-autocomplete-url="/api/search/global">
                </div>
            </div>

            {{-- User Menu --}}
            <div class="d-flex align-items-center gap-3">
                {{-- Notifications --}}
                <div class="dropdown">
                    <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">الإشعارات</h6></li>
                        <li><a class="dropdown-item" href="#">
                            <small class="text-muted">منذ 5 دقائق</small><br>
                            منتج أقل من الحد الأدنى
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#">عرض الكل</a></li>
                    </ul>
                </div>

                {{-- User Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline">{{ auth()->user()->name ?? 'المستخدم' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> الملف الشخصي</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> الإعدادات</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">
        {{-- Sidebar --}}
        <aside class="sidebar">
            <nav class="nav flex-column">
                <a class="nav-link active" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    لوحة التحكم
                </a>
                
                <h6 class="sidebar-heading mt-3 px-3">المخزون</h6>
                <a class="nav-link" href="#">
                    <i class="bi bi-box"></i>
                    المنتجات
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-file-earmark-arrow-down"></i>
                    أذون الصرف
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    أذون المرتجعات
                </a>
                
                <h6 class="sidebar-heading mt-3 px-3">العملاء</h6>
                <a class="nav-link" href="#">
                    <i class="bi bi-people"></i>
                    العملاء
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-wallet2"></i>
                    المدفوعات
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-receipt"></i>
                    الشيكات
                </a>
                
                <h6 class="sidebar-heading mt-3 px-3">التقارير</h6>
                <a class="nav-link" href="#">
                    <i class="bi bi-graph-up"></i>
                    تقارير المخزون
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-file-text"></i>
                    تقارير العملاء
                </a>
                
                <h6 class="sidebar-heading mt-3 px-3">الإعدادات</h6>
                <a class="nav-link" href="#">
                    <i class="bi bi-building"></i>
                    الفروع
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-tags"></i>
                    التصنيفات
                </a>
            </nav>
        </aside>

        {{-- Main Content --}}
        <main class="main-content">
            {{-- Breadcrumb --}}
            @if(isset($breadcrumbs))
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($loop->last)
                            <li class="breadcrumb-item active">{{ $breadcrumb['title'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif

            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- Page Content --}}
            @yield('content')
        </main>
    </div>

    {{-- Footer --}}
    <footer>
        <div class="container-fluid">
            &copy; {{ date('Y') }} نظام إدارة المخزون. جميع الحقوق محفوظة.
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/responsive.js') }}"></script>
    <script src="{{ asset('js/quick-search.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
