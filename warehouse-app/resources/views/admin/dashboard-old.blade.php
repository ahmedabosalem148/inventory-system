@extends('layouts.app')

@section('title', 'لوحة التحكم الإدارية - نظام إدارة المخزون')
@section('page-title', 'لوحة التحكم الإدارية')
@section('breadcrumb')
    نظرة عامة على حالة المخزون
@endsection

@section('content')
    <!-- Logout Button -->
    <form action="/admin/logout" method="POST" style="position: absolute; top: 20px; left: 20px;">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm">تسجيل الخروج</button>
    </form>
            transition: background-color 0.3s;
        }
        
        .sound-toggle:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .stat-products { border-right: 4px solid #10b981; }
        .stat-below { border-right: 4px solid #ef4444; }
        .stat-cartons { border-right: 4px solid #3b82f6; }
        .stat-open { border-right: 4px solid #f59e0b; }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 16px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        td {
            color: #6b7280;
        }
        
        .numeric {
            text-align: left;
            font-variant-numeric: tabular-nums;
            font-family: 'Courier New', monospace;
        }
        
        .row-below-min {
            background-color: #fef2f2;
        }
        
        .alert-icon {
            color: #ef4444;
            font-size: 16px;
            margin-left: 8px;
        }
        
        .status-ok {
            color: #10b981;
            font-weight: 500;
        }
        
        .status-low {
            color: #ef4444;
            font-weight: 500;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: #f3f4f6;
        }
        
        .empty-state {
            text-align: center;
            padding: 48px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>نظام إدارة المخزون</h1>
        <div class="header-actions">
            <button class="sound-toggle" id="soundToggle">🔔</button>
            <form method="POST" action="/admin/logout" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">خروج</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-products">
                <div class="stat-number">{{ $stats['products_total'] }}</div>
                <div class="stat-label">إجمالي المنتجات</div>
            </div>
            <div class="stat-card stat-below">
                <div class="stat-number">{{ $stats['below_min'] }}</div>
                <div class="stat-label">تحت الحد الأدنى</div>
            </div>
            <div class="stat-card stat-cartons">
                <div class="stat-number">{{ $stats['closed_cartons_total'] }}</div>
                <div class="stat-label">إجمالي الكراتين</div>
            </div>
            <div class="stat-card stat-open">
                <div class="stat-number">{{ $stats['open_estimated'] }}</div>
                <div class="stat-label">كراتين مفتوحة</div>
            </div>
        </div>
        
        <!-- Inventory Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">ملخص المخزون</div>
            </div>
            
            @if(count($rows) > 0)
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>المخزن</th>
                            <th>كراتين مقفولة</th>
                            <th>القطع/كرتونة</th>
                            <th>فرط</th>
                            <th>الإجمالي</th>
                            <th>الحد الأدنى</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr class="{{ $row->belowMin ? 'row-below-min' : '' }}" data-below-min="{{ $row->belowMin ? 'true' : 'false' }}">
                            <td>{{ $row->product_name }}</td>
                            <td>{{ $row->warehouse_name }}</td>
                            <td class="numeric">{{ number_format($row->cc) }}</td>
                            <td class="numeric">{{ number_format($row->carton_size) }}</td>
                            <td class="numeric">{{ number_format($row->lu) }}</td>
                            <td class="numeric">{{ number_format($row->total) }}</td>
                            <td class="numeric">{{ number_format($row->min) }}</td>
                            <td>
                                @if($row->belowMin)
                                    <span class="status-low">تحت الحد <span class="alert-icon">🔔</span></span>
                                @else
                                    <span class="status-ok">طبيعي</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-action">إضافة</button>
                                    <button class="btn-action">سحب</button>
                                    <button class="btn-action">تعديل</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state">
                <p>لا توجد بيانات مخزون متاحة</p>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Alert Audio (placeholder - add actual file to public/sounds/alert.mp3) -->
    <audio id="alert-audio" src="/sounds/alert.mp3" preload="auto"></audio>
    
    <script>
        // Sound management
        const soundToggle = document.getElementById('soundToggle');
        const alertAudio = document.getElementById('alert-audio');
        const beeped = new Set(); // Track which rows already beeped
        
        // Load sound preference
        let soundEnabled = localStorage.getItem('soundEnabled') !== 'false';
        updateSoundIcon();
        
        // Sound toggle handler
        soundToggle.addEventListener('click', function() {
            soundEnabled = !soundEnabled;
            localStorage.setItem('soundEnabled', soundEnabled);
            updateSoundIcon();
        });
        
        function updateSoundIcon() {
            soundToggle.textContent = soundEnabled ? '🔔' : '🔇';
        }
        
        // Play alerts for below-min items on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (!soundEnabled) return;
            
            const belowMinRows = document.querySelectorAll('[data-below-min="true"]');
            
            belowMinRows.forEach((row, index) => {
                const rowId = `${row.children[0].textContent}-${row.children[1].textContent}`;
                
                if (!beeped.has(rowId)) {
                    setTimeout(() => {
                        alertAudio.currentTime = 0;
                        alertAudio.play().catch(e => {
                            // Ignore audio play errors (browser restrictions)
                            console.log('Audio play blocked by browser');
                        });
                        beeped.add(rowId);
                    }, index * 500); // Stagger alerts by 500ms
                }
            });
        });
    </script>
</body>
</html>
