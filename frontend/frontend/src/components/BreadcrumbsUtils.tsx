import type { BreadcrumbItem } from './Breadcrumbs'

// Helper to generate breadcrumbs from current page
export function useBreadcrumbs(currentPage: string): BreadcrumbItem[] {
  const pageMap: Record<string, BreadcrumbItem[]> = {
    // Main pages
    'dashboard': [],
    'products': [{ label: 'المنتجات' }],
    'customers': [{ label: 'العملاء' }],
    'suppliers': [{ label: 'الموردين' }],
    'sales': [{ label: 'إذونات الصرف' }],
    'purchases': [{ label: 'إذونات الشراء' }],
    'return-vouchers': [{ label: 'إذونات المرتجعات' }],
    'payments': [{ label: 'المدفوعات' }],
    'cheques': [{ label: 'الشيكات' }],
    'inventory': [{ label: 'الجرد' }],
    'branches': [{ label: 'الفروع' }],

    // Reports
    'reports': [{ label: 'التقارير' }],
    'reports/stock-summary': [
      { label: 'التقارير', path: '#reports' },
      { label: 'ملخص المخزون' }
    ],
    'reports/low-stock': [
      { label: 'التقارير', path: '#reports' },
      { label: 'المنتجات الناقصة' }
    ],
    'reports/product-movements': [
      { label: 'التقارير', path: '#reports' },
      { label: 'حركات المنتجات' }
    ],
    'reports/customer-balances': [
      { label: 'التقارير', path: '#reports' },
      { label: 'أرصدة العملاء' }
    ],
    'reports/customer-aging': [
      { label: 'التقارير', path: '#reports' },
      { label: 'أعمار الذمم' }
    ],
    'reports/sales-summary': [
      { label: 'التقارير', path: '#reports' },
      { label: 'ملخص المبيعات' }
    ],
    'reports/stock-valuation': [
      { label: 'التقارير', path: '#reports' },
      { label: 'تقييم المخزون' }
    ],

    // Admin pages
    'users': [{ label: 'إدارة المستخدمين' }],
    'activity-logs': [{ label: 'سجل النشاطات' }],
    'profile': [{ label: 'الملف الشخصي' }],
    'settings': [{ label: 'الإعدادات' }],
  }

  // Check if current page has details view (e.g., customers/123)
  if (currentPage.includes('/')) {
    const [mainPage, id] = currentPage.split('/')

    if (mainPage === 'customers' && id) {
      return [
        { label: 'العملاء', path: '#customers' },
        { label: `عميل ${id}` }
      ]
    }

    if (mainPage === 'sales' && id) {
      return [
        { label: 'إذونات الصرف', path: '#sales' },
        { label: `إذن صرف ${id}` }
      ]
    }

    if (mainPage === 'return-vouchers' && id) {
      return [
        { label: 'إذونات المرتجعات', path: '#return-vouchers' },
        { label: `إذن مرتجع ${id}` }
      ]
    }

    if (mainPage === 'reports') {
      const reportPath = currentPage
      if (reportPath in pageMap) {
        return pageMap[reportPath]
      }
    }
  }

  return pageMap[currentPage] || []
}
