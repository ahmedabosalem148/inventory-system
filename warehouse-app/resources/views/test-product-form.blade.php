<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>اختبار سريع إضافة منتج</title>
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, button { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 300px; }
        button { background: #007bff; color: white; cursor: pointer; width: auto; padding: 15px 30px; }
        button:hover { background: #0056b3; }
        .result { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>🧪 اختبار سريع إضافة منتج</h1>
    
    <form id="productForm" action="/warehouses/1/products" method="POST">
        @csrf
        <div class="form-group">
            <label>اسم المنتج:</label>
            <input type="text" name="name" value="منتج اختبار {{ time() }}" required>
        </div>
        
        <div class="form-group">
            <label>عدد الوحدات في كل كرتونة:</label>
            <input type="number" name="units_per_carton" value="24" required>
        </div>
        
        <div class="form-group">
            <label>الحد الأدنى:</label>
            <input type="number" name="min_threshold" value="10">
        </div>
        
        <div class="form-group">
            <label>عدد الكراتين:</label>
            <input type="number" name="cartons" value="5" required>
        </div>
        
        <button type="submit" id="submitBtn">💾 حفظ المنتج</button>
    </form>
    
    <div id="result"></div>
    
    <script src="/js/app.js"></script>
    <script>
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const result = document.getElementById('result');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ جاري الحفظ...';
            
            result.innerHTML = '<div class="result">جاري إرسال البيانات...</div>';
            
            console.log('Form submitted with data:', new FormData(this));
        });
        
        // Check if we came back with session messages
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                const msg = decodeURIComponent(urlParams.get('success'));
                document.getElementById('result').innerHTML = `<div class="result success">✅ ${msg}</div>`;
                if (typeof App !== 'undefined' && App.showSuccessAlert) {
                    App.showSuccessAlert(msg);
                }
            }
            if (urlParams.has('error')) {
                const msg = decodeURIComponent(urlParams.get('error'));
                document.getElementById('result').innerHTML = `<div class="result error">❌ ${msg}</div>`;
                if (typeof App !== 'undefined' && App.showErrorAlert) {
                    App.showErrorAlert('خطأ', msg);
                }
            }
        });
    </script>
</body>
</html>
