<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>نظام إدارة المخزون</title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: #ffffff;
            transform: translateX(-5px);
        }
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: #ffffff;
            font-weight: 600;
        }
        .sidebar .nav-link i {
            margin-left: 0.5rem;
            font-size: 1.1rem;
        }
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .main-content {
            padding: 2rem;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 1rem 2rem;
        }
        .user-info {
            color: rgba(255,255,255,0.9);
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="position-sticky d-flex flex-column" style="height: 100vh;">
                    <div class="sidebar-header text-center">
                        <h4 class="text-white mb-0">
                            <i class="bi bi-box-seam"></i>
                            نظام المخزون
                        </h4>
                    </div>

                    <div class="flex-grow-1 overflow-auto">
                        <ul class="nav flex-column py-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    لوحة المتابعة
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    المنتجات
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('issue-vouchers.*') ? 'active' : '' }}" href="{{ route('issue-vouchers.index') }}">
                                    <i class="bi bi-arrow-up-circle"></i>
                                    أذون الصرف
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('return-vouchers.*') ? 'active' : '' }}" href="{{ route('return-vouchers.index') }}">
                                    <i class="bi bi-arrow-down-circle"></i>
                                    أذون الارتجاع
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                                    <i class="bi bi-people"></i>
                                    العملاء
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                    <i class="bi bi-cash-stack"></i>
                                    المدفوعات
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('cheques.*') ? 'active' : '' }}" href="{{ route('cheques.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    الشيكات
                                </a>
                            </li>

                            <hr style="border-color: rgba(255,255,255,0.2); margin: 1rem 0.5rem;">

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.low-stock') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    التقارير
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                                    <i class="bi bi-building"></i>
                                    الفروع
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                    <i class="bi bi-tags"></i>
                                    التصنيفات
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="user-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle fs-3 me-2"></i>
                            <div>
                                <div class="fw-bold">مستخدم النظام</div>
                                <small class="opacity-75">مدير</small>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
