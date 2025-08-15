{{-- Self-Check Panel - لوحة الفحص الذاتي --}}
<div class="card border-info mb-4" style="border-left: 4px solid #17a2b8;">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            🔧 لوحة الفحص الذاتي للواجهة
            <small class="float-end">وضع التصحيح</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-3" role="alert">
            <strong>⚠️ تحذير:</strong> هذه الأداة مخصصة للاختبار والتطوير فقط، لا تُنشر في بيئة الإنتاج.
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6>الفحوصات المتاحة:</h6>
                <ul class="list-unstyled">
                    <li>✓ وجود CSRF Token</li>
                    <li>✓ نظام الصوت والتنبيهات</li>
                    <li>✓ زر الكتم و localStorage</li>
                    <li>✓ هيكل جداول البيانات</li>
                    <li>✓ صحة الحسابات الرياضية</li>
                    <li>✓ تلوين الصفوف التحذيرية</li>
                    <li>✓ وجود العناصر الحرجة</li>
                </ul>
            </div>
            <div class="col-md-6">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" onclick="window.SelfCheck.run()">
                        🚀 تشغيل فحص الواجهة
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="console.clear()">
                        🧹 مسح Console
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Results Container --}}
        <div id="selfcheck-results" class="mt-4">
            <div class="text-muted text-center py-3">
                اضغط "تشغيل فحص الواجهة" لبدء الاختبار...
            </div>
        </div>
    </div>
    <div class="card-footer text-muted">
        <small>
            لإخفاء هذه اللوحة، قم بإزالة <code>?debug=1</code> من رابط الصفحة.
            <br>
            راجع <code>public/js/selfcheck.js</code> لتفاصيل الفحوصات.
        </small>
    </div>
</div>
