# 🔑 كلمات مرور المخازن الإنتاجية

## المخازن الجديدة المضافة:

### 🏪 العتبة
- **كلمة المرور:** `ataba123`
- **الرابط:** `/warehouses/{warehouse_id}/login`

### 🏪 امبابة  
- **كلمة المرور:** `imbaba123`
- **الرابط:** `/warehouses/{warehouse_id}/login`

### 🏪 المصنع
- **كلمة المرور:** `factory123`
- **الرابط:** `/warehouses/{warehouse_id}/login`

## 📝 ملاحظات:
- جميع المخازن فارغة ولا تحتوي على أي بيانات تجريبية
- يمكن الوصول للمخازن من الصفحة الرئيسية `/warehouses`
- كل مخزن محمي بكلمة مرور منفصلة
- لتغيير كلمة المرور، استخدم:
  ```php
  use Illuminate\Support\Facades\Hash;
  $warehouse = App\Models\Warehouse::find($id);
  $warehouse->password = Hash::make('new_password');
  $warehouse->save();
  ```

## 🎯 خطوات البدء:
1. اذهب إلى `/warehouses` 
2. اختر المخزن المطلوب
3. أدخل كلمة المرور
4. ابدأ إضافة المنتجات والمخزون

## 👤 الدخول للإدارة:
- **رابط الإدارة:** `/` (الصفحة الرئيسية)
- **PIN:** `1234`
