/**
 * Breadcrumbs Navigation Component
 * Shows current page hierarchy for better navigation
 */

import { ChevronLeft, Home } from 'lucide-react'
import { cn } from '@/lib/utils'

interface BreadcrumbItem {
  label: string
  path?: string
}

interface BreadcrumbsProps {
  items: BreadcrumbItem[]
  className?: string
}

export function Breadcrumbs({ items, className }: BreadcrumbsProps) {
  const handleNavigate = (path?: string) => {
    if (path) {
      window.location.hash = path
    }
  }

  return (
    <nav 
      className={cn('flex items-center gap-2 text-sm text-gray-600', className)}
      aria-label="Breadcrumb"
    >
      {/* Home icon */}
      <button
        onClick={() => handleNavigate('#')}
        className="flex items-center hover:text-blue-600 transition-colors"
        aria-label="الصفحة الرئيسية"
      >
        <Home className="w-4 h-4" />
      </button>

      {/* Breadcrumb items */}
      {items.map((item, index) => {
        const isLast = index === items.length - 1

        return (
          <div key={index} className="flex items-center gap-2">
            <ChevronLeft className="w-4 h-4 text-gray-400" />
            
            {isLast ? (
              <span className="font-medium text-gray-900">
                {item.label}
              </span>
            ) : (
              <button
                onClick={() => handleNavigate(item.path)}
                className="hover:text-blue-600 transition-colors"
              >
                {item.label}
              </button>
            )}
          </div>
        )
      })}
    </nav>
  )
}

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
