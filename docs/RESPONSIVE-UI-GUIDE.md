# دليل التصميم المتجاوب (Responsive UI Guide)

## نظرة عامة

تم تطبيق تصميم متجاوب كامل يدعم جميع أحجام الشاشات:
- **Mobile**: 320px - 767px
- **Tablet**: 768px - 1023px
- **Desktop**: 1024px+

الميزات:
- ✅ Bootstrap 5.3 RTL
- ✅ Mobile-first approach
- ✅ Touch-friendly UI (44px minimum touch targets)
- ✅ Responsive tables (card-based on mobile)
- ✅ Mobile navigation with sidebar
- ✅ Swipe gestures
- ✅ Print-friendly styles

---

## Breakpoints

```css
/* Mobile */
@media (max-width: 767px) { ... }

/* Tablet */
@media (min-width: 768px) and (max-width: 1023px) { ... }

/* Desktop */
@media (min-width: 1024px) { ... }
```

---

## المكونات الرئيسية

### 1. **Responsive CSS** (`responsive.css`)

يحتوي على:
- Media queries لجميع الأحجام
- Responsive tables
- Mobile-optimized forms
- Touch-friendly buttons
- Utility classes

#### الاستخدام:

```html
<link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
```

---

### 2. **Responsive JavaScript** (`responsive.js`)

يحتوي على 4 classes رئيسية:

#### **MobileNavigation**
```javascript
// تلقائي
new MobileNavigation();

// Events
document.addEventListener('sidebar:open', () => {
    console.log('Sidebar opened');
});

document.addEventListener('sidebar:close', () => {
    console.log('Sidebar closed');
});
```

#### **ResponsiveTable**
```javascript
// تحويل الجداول لـ cards على Mobile
const table = document.querySelector('.my-table');
new ResponsiveTable(table);
```

#### **ResponsiveForm**
```javascript
// تحسين Forms للـ Mobile
const form = document.querySelector('#myForm');
new ResponsiveForm(form);
```

#### **TouchDropdown**
```javascript
// Dropdowns محسّنة للمس
new TouchDropdown();
```

---

## Responsive Tables

### الطريقة التلقائية:

```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>الكود</th>
                <th>الاسم</th>
                <th>السعر</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>P001</td>
                <td>منتج 1</td>
                <td>100.00</td>
                <td>
                    <button class="btn btn-sm btn-primary">تعديل</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

على **Mobile**، سيتحول تلقائياً إلى card-based layout.

---

### Card-Based على Mobile:

سيظهر كالتالي:

```
┌─────────────────────────────┐
│ الكود: P001                 │
├─────────────────────────────┤
│ الاسم: منتج 1               │
├─────────────────────────────┤
│ السعر: 100.00               │
├─────────────────────────────┤
│ الإجراءات: [تعديل]         │
└─────────────────────────────┘
```

---

## Mobile Navigation

### HTML Structure:

```html
{{-- في Layout --}}
<nav class="navbar">
    {{-- Toggle button يُضاف تلقائياً بواسطة JS --}}
    <a class="navbar-brand" href="/">نظام المخزون</a>
</nav>

<aside class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link active" href="/">
            <i class="bi bi-speedometer2"></i>
            لوحة التحكم
        </a>
        {{-- المزيد من الروابط --}}
    </nav>
</aside>
```

### Behavior:

- **Desktop**: Sidebar ظاهر دائماً
- **Mobile**: Sidebar drawer من الجانب
- **Swipe**: من اليمين لليسار (RTL) لفتح/إغلاق
- **Overlay**: يظهر خلف Sidebar على Mobile

---

## Responsive Forms

### مثال فورم متجاوب:

```blade
<form class="row g-3">
    {{-- Desktop: 3 أعمدة، Mobile: عمود واحد --}}
    <div class="col-md-4">
        <label class="form-label">الاسم</label>
        <input type="text" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">البريد</label>
        <input type="email" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">الهاتف</label>
        <input type="tel" class="form-control">
    </div>

    {{-- Buttons --}}
    <div class="col-12">
        <div class="d-flex gap-2 stack-mobile">
            <button type="submit" class="btn btn-primary">حفظ</button>
            <button type="reset" class="btn btn-secondary">إعادة تعيين</button>
        </div>
    </div>
</form>
```

على **Mobile**:
- كل حقل في سطر منفصل
- Buttons بعرض 100%
- Font size 16px (منع zoom في iOS)

---

## Utility Classes

### إخفاء/إظهار حسب الحجم:

```html
{{-- إخفاء على Mobile --}}
<div class="hide-mobile">
    محتوى يظهر على Desktop فقط
</div>

{{-- إظهار على Mobile فقط --}}
<div class="show-mobile">
    محتوى يظهر على Mobile فقط
</div>

{{-- إخفاء على Tablet --}}
<div class="hide-tablet">
    يخفى على Tablet
</div>

{{-- إخفاء على Desktop --}}
<div class="hide-desktop">
    يخفى على Desktop
</div>
```

---

### Stack على Mobile:

```html
{{-- Desktop: جنب بعض، Mobile: فوق بعض --}}
<div class="d-flex gap-2 stack-mobile">
    <button class="btn btn-primary">زر 1</button>
    <button class="btn btn-secondary">زر 2</button>
    <button class="btn btn-success">زر 3</button>
</div>
```

---

## Touch Targets

**القاعدة**: الحد الأدنى 44×44px للعناصر القابلة للمس.

```css
/* تطبيق تلقائي على Mobile */
@media (max-width: 767px) {
    .btn {
        min-height: 44px;
    }
    
    .form-control {
        min-height: 44px;
    }
    
    .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
    }
}
```

---

## Responsive Images

```html
{{-- صورة متجاوبة --}}
<img src="image.jpg" class="img-fluid" alt="...">

{{-- صور مختلفة حسب الحجم --}}
<picture>
    <source media="(max-width: 767px)" srcset="image-mobile.jpg">
    <source media="(min-width: 768px)" srcset="image-desktop.jpg">
    <img src="image-desktop.jpg" alt="...">
</picture>
```

---

## Modal على Mobile

```html
{{-- Modal ملء الشاشة على Mobile --}}
<div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">عنوان</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                محتوى Modal
            </div>
        </div>
    </div>
</div>
```

---

## Print Styles

```css
/* عند الطباعة */
@media print {
    .no-print {
        display: none !important;
    }
}
```

```html
{{-- عناصر لا تُطبع --}}
<button class="btn btn-primary no-print">تعديل</button>
<nav class="navbar no-print">...</nav>
```

---

## Testing

### Chrome DevTools:

1. افتح DevTools (F12)
2. اضغط على أيقونة Device Toolbar (Ctrl+Shift+M)
3. اختر جهاز:
   - iPhone SE (375×667)
   - iPad (768×1024)
   - Desktop (1920×1080)

### Viewport Sizes للاختبار:

```
Mobile Portrait:  320×568  (iPhone SE)
Mobile Landscape: 568×320
Tablet Portrait:  768×1024 (iPad)
Tablet Landscape: 1024×768
Desktop:          1920×1080
```

---

## Performance Tips

### 1. **تحميل CSS حسب الحاجة**:

```html
{{-- فقط على Desktop --}}
<link rel="stylesheet" 
      href="desktop-only.css" 
      media="(min-width: 1024px)">

{{-- فقط على Mobile --}}
<link rel="stylesheet" 
      href="mobile-only.css" 
      media="(max-width: 767px)">
```

---

### 2. **Lazy Loading للصور**:

```html
<img src="image.jpg" loading="lazy" alt="...">
```

---

### 3. **Viewport Height Fix** (Mobile):

```javascript
// في responsive.js
const setViewportHeight = () => {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
};

window.addEventListener('resize', setViewportHeight);
```

الاستخدام:

```css
.fullscreen {
    height: calc(var(--vh, 1vh) * 100);
}
```

---

## Accessibility

### 1. **Focus States**:

```css
@media (max-width: 767px) {
    a:focus, button:focus, input:focus {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }
}
```

---

### 2. **ARIA للـ Sidebar**:

```html
<aside class="sidebar" aria-hidden="true">
    {{-- محتوى Sidebar --}}
</aside>
```

يتحول إلى `aria-hidden="false"` عند الفتح.

---

### 3. **Skip Links**:

```html
<a href="#main-content" class="skip-link">
    تخطي إلى المحتوى الرئيسي
</a>
```

---

## Best Practices

### ✅ **افعل:**

1. **استخدم Mobile-first**:
   ```css
   /* Base styles للـ Mobile */
   .btn { padding: 0.75rem; }
   
   /* Desktop enhancement */
   @media (min-width: 1024px) {
       .btn { padding: 0.5rem 1rem; }
   }
   ```

2. **اختبر على أجهزة حقيقية**:
   - iPhone/Android
   - iPad/Tablet
   - Desktop

3. **استخدم rem بدلاً من px**:
   ```css
   font-size: 1rem; /* ✅ */
   font-size: 16px; /* ❌ */
   ```

4. **Touch targets ≥ 44px**

5. **تجنب hover على Mobile**:
   ```css
   @media (min-width: 1024px) {
       .btn:hover { /* فقط على Desktop */ }
   }
   ```

---

### ❌ **لا تفعل:**

1. **لا تستخدم fixed width على Mobile**:
   ```css
   /* ❌ Bad */
   .card { width: 800px; }
   
   /* ✅ Good */
   .card { max-width: 800px; width: 100%; }
   ```

2. **لا تنسى viewport meta tag**:
   ```html
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   ```

3. **لا تعتمد على hover للوظائف الأساسية**

4. **لا تجعل النصوص صغيرة جداً** (minimum 14px على Mobile)

---

## Checklist للتأكد من التجاوب

- [ ] Viewport meta tag موجود
- [ ] جميع الجداول responsive
- [ ] Forms تعمل على Mobile
- [ ] Buttons حجمها مناسب للمس (≥44px)
- [ ] Navigation يعمل على Mobile
- [ ] Images متجاوبة
- [ ] Modals ملء الشاشة على Mobile
- [ ] لا توجد horizontal scroll
- [ ] Font size مناسب (≥14px)
- [ ] Tested على 3 أحجام على الأقل

---

## ملفات المشروع

### Files Created:
- `public/css/responsive.css` - Responsive styles
- `public/js/responsive.js` - Mobile navigation & helpers
- `resources/views/layouts/app-responsive.blade.php` - Responsive layout

### Usage:

```blade
@extends('layouts.app-responsive')

@section('title', 'صفحتي')

@section('content')
    {{-- محتوى responsive تلقائياً --}}
@endsection
```

---

## المراجع

- TASK-030 في BACKLOG.md
- Bootstrap 5.3 Responsive: https://getbootstrap.com/docs/5.3/layout/breakpoints/
- CSS Media Queries: https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries
- Mobile UX Best Practices: https://developers.google.com/web/fundamentals/design-and-ux/responsive

