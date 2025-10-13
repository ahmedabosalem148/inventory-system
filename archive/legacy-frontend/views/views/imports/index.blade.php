@extends('layouts.app')

@section('title', 'استيراد البيانات')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    استيراد البيانات
                </h2>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="importTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                        <i class="bi bi-box-seam"></i>
                        المنتجات والأرصدة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button" role="tab">
                        <i class="bi bi-people"></i>
                        العملاء والأرصدة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cheques-tab" data-bs-toggle="tab" data-bs-target="#cheques" type="button" role="tab">
                        <i class="bi bi-credit-card-2-front"></i>
                        الشيكات
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="importTabsContent">
                <!-- Products Tab -->
                <div class="tab-pane fade show active" id="products" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-box-seam"></i>
                                استيراد المنتجات والأرصدة الافتتاحية
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Instructions -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> تعليمات الاستيراد:</h6>
                                <ol class="mb-0">
                                    <li>قم بتحميل قالب CSV أولاً</li>
                                    <li>املأ البيانات: <strong>كود الفرع، كود المنتج (SKU)، الكمية</strong></li>
                                    <li>احفظ الملف بصيغة CSV</li>
                                    <li>ارفع الملف هنا</li>
                                </ol>
                            </div>

                            <!-- Download Template Button -->
                            <div class="mb-4">
                                <a href="{{ route('imports.template') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i>
                                    تحميل قالب CSV
                                </a>
                            </div>

                            <hr>

                            <!-- Upload Form -->
                            <form action="{{ route('imports.execute') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="file-products" class="form-label fw-bold">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                        اختر ملف CSV للاستيراد
                                    </label>
                                    <input 
                                        type="file" 
                                        class="form-control @error('file') is-invalid @enderror" 
                                        id="file-products" 
                                        name="file" 
                                        accept=".csv,.txt"
                                        required
                                    >
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        الصيغ المدعومة: CSV (.csv) أو TXT - الحد الأقصى 5 ميجا
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-upload"></i>
                                        استيراد أرصدة المنتجات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Customers Tab -->
                <div class="tab-pane fade" id="customers" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-people"></i>
                                استيراد العملاء والأرصدة الافتتاحية
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Instructions -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> تعليمات الاستيراد:</h6>
                                <ol class="mb-0">
                                    <li>قم بتحميل قالب CSV للعملاء</li>
                                    <li>املأ البيانات: <strong>كود العميل، الاسم، الهاتف، العنوان، الرصيد الافتتاحي</strong></li>
                                    <li>الرصيد الموجب = علية (دين للعميل)، الرصيد السالب = له (دائن)</li>
                                    <li>احفظ الملف بصيغة CSV</li>
                                    <li>ارفع الملف هنا</li>
                                </ol>
                            </div>

                            <!-- Download Template Button -->
                            <div class="mb-4">
                                <a href="{{ route('imports.customers.template') }}" class="btn btn-outline-success">
                                    <i class="bi bi-download"></i>
                                    تحميل قالب CSV للعملاء
                                </a>
                            </div>

                            <hr>

                            <!-- Upload Form -->
                            <form action="{{ route('imports.customers.execute') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="file-customers" class="form-label fw-bold">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                        اختر ملف CSV للعملاء
                                    </label>
                                    <input 
                                        type="file" 
                                        class="form-control @error('file') is-invalid @enderror" 
                                        id="file-customers" 
                                        name="file" 
                                        accept=".csv,.txt"
                                        required
                                    >
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        الصيغ المدعومة: CSV (.csv) أو TXT - الحد الأقصى 5 ميجا
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-upload"></i>
                                        استيراد بيانات العملاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Cheques Tab -->
                <div class="tab-pane fade" id="cheques" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="bi bi-credit-card-2-front"></i>
                                استيراد الشيكات غير المصروفة
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Instructions -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> تعليمات الاستيراد:</h6>
                                <ol class="mb-0">
                                    <li>قم بتحميل قالب CSV للشيكات</li>
                                    <li>املأ البيانات: <strong>كود العميل، رقم الشيك، البنك، تاريخ الاستحقاق، المبلغ، رقم الفاتورة (اختياري)</strong></li>
                                    <li>تاريخ الاستحقاق بصيغة: <code>YYYY-MM-DD</code> مثل: 2025-12-31</li>
                                    <li>جميع الشيكات ستكون بحالة "غير مصروفة" (PENDING)</li>
                                    <li>احفظ الملف بصيغة CSV</li>
                                    <li>ارفع الملف هنا</li>
                                </ol>
                            </div>

                            <!-- Download Template Button -->
                            <div class="mb-4">
                                <a href="{{ route('imports.cheques.template') }}" class="btn btn-outline-warning">
                                    <i class="bi bi-download"></i>
                                    تحميل قالب CSV للشيكات
                                </a>
                            </div>

                            <hr>

                            <!-- Upload Form -->
                            <form action="{{ route('imports.cheques.execute') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="file-cheques" class="form-label fw-bold">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                        اختر ملف CSV للشيكات
                                    </label>
                                    <input 
                                        type="file" 
                                        class="form-control @error('file') is-invalid @enderror" 
                                        id="file-cheques" 
                                        name="file" 
                                        accept=".csv,.txt"
                                        required
                                    >
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        الصيغ المدعومة: CSV (.csv) أو TXT - الحد الأقصى 5 ميجا
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="bi bi-upload"></i>
                                        استيراد بيانات الشيكات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
