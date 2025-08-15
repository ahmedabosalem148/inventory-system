# Frontend Acceptance Checklist - قائمة قبول الواجهة الأمامية

## العناصر المشتركة (Shared Components)
- [ ] **RTL Support**: النصوص العربية تظهر من اليمين لليسار
- [ ] **CSRF Token**: وجود `<meta name="csrf-token">` في الـhead
- [ ] **CSS/JS Loading**: تحميل `app.css` و `app.js` و `selfcheck.js` بنجاح
- [ ] **Toast Notifications**: رسائل النجاح/الخطأ تظهر لـ3 ثوان
- [ ] **Mute State**: زر الكتم يحفظ الحالة في localStorage ويعمل بصريًا
- [ ] **Audio System**: تشغيل الصوت + fallback للـWebAudio API
- [ ] **Responsive Design**: الواجهة تتكيف مع الشاشات المختلفة
- [ ] **Loading States**: حالات التحميل واضحة أثناء العمليات
- [ ] **Error Handling**: رسائل الخطأ واضحة باللغة العربية

## لوحة الأدمن (Admin Dashboard)
### KPIs والمؤشرات
- [ ] **Key Metrics**: عرض إجمالي المنتجات، المخازن، العناصر تحت الحد الأدنى
- [ ] **Real-time Updates**: تحديث المؤشرات عند إضافة/تعديل البيانات

### أدوات الجدول
- [ ] **Search Functionality**: البحث في المنتجات والمخازن يعمل فوريًا
- [ ] **Filters**: فلاتر حسب المخزن والحالة
- [ ] **Add Product Button**: زر إضافة منتج جديد واضح ومتاح

### الجدول الرئيسي
- [ ] **Required Columns**: المنتج، المخزن، الكراتين، حجم الكرتونة، القطع المفردة، الإجمالي، الحد الأدنى، الحالة
- [ ] **Calculations**: `الإجمالي = الكراتين × حجم الكرتونة + القطع المفردة`
- [ ] **Row Coloring**: الصفوف تحت الحد الأدنى بلون تحذيري (أحمر/برتقالي)
- [ ] **Alert Bell**: أيقونة 🔔 للعناصر تحت الحد الأدنى
- [ ] **Audio Alert**: تشغيل الصوت مرة واحدة للتحذيرات الجديدة

### مودال إضافة منتج
- [ ] **Form Fields**: اسم المنتج، حجم الكرتونة، حالة النشاط
- [ ] **Instant Validation**: التحقق الفوري من صحة البيانات
- [ ] **422 Error Messages**: رسائل خطأ واضحة للتحقق من صحة البيانات
- [ ] **Success Feedback**: رسالة نجاح عند الإضافة
- [ ] **Modal Behavior**: فتح/إغلاق المودال بسلاسة

## واجهة المخازن (Warehouse Interface)
### قائمة المخازن
- [ ] **Warehouse Grid**: عرض جميع المخازن في شبكة منظمة
- [ ] **Warehouse Cards**: بطاقات تحتوي على معلومات أساسية
- [ ] **Navigation**: الانتقال السلس لصفحة كل مخزن

### صفحة المخزن المحدد
- [ ] **Search in Warehouse**: البحث في منتجات المخزن
- [ ] **Inventory Table**: جدول المخزون مع جميع الأعمدة المطلوبة
- [ ] **Row Highlighting**: تلوين الصفوف حسب حالة المخزون
- [ ] **Details Drawer**: فتح درج التفاصيل لكل منتج
- [ ] **Add Operation**: نموذج إضافة مخزون مع التحديث الفوري
- [ ] **Withdraw Operation**: نموذج سحب مخزون مع التحقق من الكمية المتاحة
- [ ] **Set Minimum**: تحديد الحد الأدنى مع التحديث الفوري
- [ ] **422 Validation**: رسائل خطأ واضحة لجميع العمليات

## إمكانية الوصول (Accessibility)
- [ ] **Tab Order**: ترتيب منطقي للانتقال بالـTab
- [ ] **ARIA Labels**: تسميات واضحة للعناصر التفاعلية
- [ ] **Focus Trap**: حصر التركيز داخل المودال/الدرج المفتوح
- [ ] **Keyboard Navigation**: إمكانية التنقل بالكيبورد
- [ ] **Screen Reader Support**: دعم قارئات الشاشة

## الأداء (Performance)
- [ ] **API Throttling**: التعامل مع خطأ 429 (Too Many Requests)
- [ ] **Pagination**: تقسيم البيانات للجداول الكبيرة (≥ 500 صف)
- [ ] **Loading Indicators**: مؤشرات التحميل للعمليات البطيئة
- [ ] **Debounced Search**: تأخير البحث لتقليل الطلبات
- [ ] **Optimistic Updates**: تحديث الواجهة قبل تأكيد الخادم

## عقود API (API Contracts)
### مثال: GET /api/warehouses/{id}/inventory
```json
[
  {
    "id": 1,
    "product_id": 2,
    "product_name": "منتج تجريبي",
    "closed_cartons": 5,
    "carton_size": 12,
    "loose_units": 3,
    "totalUnits": 63,
    "min_threshold": 50,
    "belowMin": false
  }
]
```

### مثال: POST /api/inventory/add
```json
// Request
{
  "warehouse_id": 1,
  "product_id": 2,
  "quantity": 10,
  "unit_type": "units"
}

// Response 200
{
  "success": true,
  "data": {
    "inventory_item": {
      "closed_cartons": 5,
      "loose_units": 13,
      "totalUnits": 73
    }
  }
}

// Response 422
{
  "message": "الكمية يجب أن تكون أكبر من صفر"
}
```

## Definition of Done
- [ ] **Lighthouse Score**: النتيجة ≥ 90 للأداء وإمكانية الوصول
- [ ] **Console Errors**: لا توجد أخطاء في console المتصفح
- [ ] **Acceptance Scenarios**: نجاح جميع سيناريوهات القبول المحددة
- [ ] **Cross-browser Testing**: اختبار على Chrome وFirefox وSafari
- [ ] **Mobile Responsive**: عمل صحيح على الهواتف والأجهزة اللوحية
- [ ] **Data Integrity**: تطابق البيانات بين الواجهة والخادم
- [ ] **User Experience**: تجربة مستخدم سلسة وبديهية

## ملاحظات الاختبار
- استخدم `?debug=1` لرؤية لوحة الفحص الذاتي
- تأكد من وجود بيانات تجريبية كافية للاختبار
- اختبر جميع العمليات في بيئات مختلفة
- تحقق من التعامل مع الحالات الاستثنائية
