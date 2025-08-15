<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام إدارة المخزون')</title>
    
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    <!-- Additional head content -->
    @stack('head')
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1>@yield('page-title', 'نظام إدارة المخزون')</h1>
                    @hasSection('breadcrumb')
                        <div class="breadcrumb">
                            @yield('breadcrumb')
                        </div>
                    @endif
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Audio Control -->
                    <button class="audio-control" type="button" title="تحكم في الصوت" data-testid="btn-toggle-mute">
                        🔔
                    </button>
                    
                    @auth('admin')
                        <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary btn-sm">
                            لوحة التحكم
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        @yield('content')
    </main>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Audio Element -->
    <audio id="alert-audio" preload="none">
        <!-- WebAudio fallback will be used since no actual audio files are provided -->
    </audio>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/selfcheck.js') }}" defer></script>
    
    <!-- Additional scripts -->
    @stack('scripts')
</body>
</html>
