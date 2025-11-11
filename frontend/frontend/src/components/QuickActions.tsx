/**
 * Quick Actions Component
 * Provides shortcuts to common tasks for faster workflow
 */

import { 
  Plus, 
  FileText, 
  Users, 
  Package, 
  DollarSign, 
  RefreshCw,
  TrendingUp,
  Clipboard
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

interface QuickAction {
  id: string
  label: string
  icon: React.ReactNode
  path: string
  color: string
  description: string
  roles?: string[]
}

interface QuickActionsProps {
  userRole?: string
}

export function QuickActions({ userRole = 'manager' }: QuickActionsProps) {
  const allActions: QuickAction[] = [
    {
      id: 'new-issue',
      label: 'إذن صرف جديد',
      icon: <FileText className="w-5 h-5" />,
      path: '#sales',
      color: 'bg-blue-500 hover:bg-blue-600',
      description: 'إنشاء إذن صرف للعميل',
      roles: ['manager', 'store_user'],
    },
    {
      id: 'new-return',
      label: 'إذن مرتجع جديد',
      icon: <RefreshCw className="w-5 h-5" />,
      path: '#return-vouchers',
      color: 'bg-orange-500 hover:bg-orange-600',
      description: 'إنشاء إذن مرتجع من عميل',
      roles: ['manager', 'store_user'],
    },
    {
      id: 'add-payment',
      label: 'تسجيل دفعة',
      icon: <DollarSign className="w-5 h-5" />,
      path: '#payments',
      color: 'bg-green-500 hover:bg-green-600',
      description: 'تسجيل دفعة من عميل',
      roles: ['manager', 'accounting'],
    },
    {
      id: 'add-customer',
      label: 'عميل جديد',
      icon: <Users className="w-5 h-5" />,
      path: '#customers',
      color: 'bg-purple-500 hover:bg-purple-600',
      description: 'إضافة عميل جديد',
      roles: ['manager', 'accounting'],
    },
    {
      id: 'add-product',
      label: 'منتج جديد',
      icon: <Package className="w-5 h-5" />,
      path: '#products',
      color: 'bg-indigo-500 hover:bg-indigo-600',
      description: 'إضافة منتج للمخزن',
      roles: ['manager'],
    },
    {
      id: 'inventory',
      label: 'جرد المخزن',
      icon: <Clipboard className="w-5 h-5" />,
      path: '#inventory',
      color: 'bg-teal-500 hover:bg-teal-600',
      description: 'إجراء جرد للمخزن',
      roles: ['manager', 'store_user'],
    },
    {
      id: 'sales-report',
      label: 'تقرير المبيعات',
      icon: <TrendingUp className="w-5 h-5" />,
      path: '#reports/sales-summary',
      color: 'bg-pink-500 hover:bg-pink-600',
      description: 'عرض تقرير المبيعات',
      roles: ['manager', 'accounting'],
    },
    {
      id: 'customer-balances',
      label: 'أرصدة العملاء',
      icon: <FileText className="w-5 h-5" />,
      path: '#reports/customer-balances',
      color: 'bg-cyan-500 hover:bg-cyan-600',
      description: 'عرض أرصدة العملاء',
      roles: ['manager', 'accounting'],
    },
  ]

  // Filter actions based on user role
  const actions = allActions.filter(action => 
    !action.roles || action.roles.includes(userRole)
  )

  const handleAction = (path: string) => {
    window.location.hash = path
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Plus className="w-5 h-5 text-blue-600" />
          إجراءات سريعة
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {actions.map((action) => (
            <Button
              key={action.id}
              variant="outline"
              className={`h-auto flex flex-col items-center gap-2 p-4 hover:scale-105 transition-transform ${action.color} text-white border-0`}
              onClick={() => handleAction(action.path)}
            >
              {action.icon}
              <span className="text-sm font-medium text-center">
                {action.label}
              </span>
            </Button>
          ))}
        </div>
        
        {/* Keyboard Shortcuts Hint */}
        <div className="mt-4 pt-4 border-t border-gray-200">
          <p className="text-xs text-gray-500 text-center">
            💡 نصيحة: استخدم <kbd className="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl + K</kbd> للبحث السريع
          </p>
        </div>
      </CardContent>
    </Card>
  )
}
