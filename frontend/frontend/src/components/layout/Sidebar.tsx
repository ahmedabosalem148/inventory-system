import { useAuth } from '@/features/auth/AuthContext'
import { cn } from '@/lib/utils'
import {
  LayoutDashboard,
  Package,
  Users,
  FileText,
  Repeat,
  DollarSign,
  BarChart3,
  Settings,
  Building2,
  ShoppingCart,
  ClipboardList,
  X,
} from 'lucide-react'

interface SidebarProps {
  isOpen: boolean
  onClose: () => void
}

interface NavItem {
  label: string
  icon: React.ElementType
  href: string
  roles?: string[] // Optional: restrict to specific roles
  permissions?: string[] // Optional: require specific permissions
}

const navigationItems: NavItem[] = [
  {
    label: 'لوحة التحكم',
    icon: LayoutDashboard,
    href: '#dashboard',
  },
  {
    label: 'المنتجات',
    icon: Package,
    href: '#products',
    permissions: ['view-products'],
  },
  {
    label: 'العملاء',
    icon: Users,
    href: '#customers',
    permissions: ['view-customers'],
  },
  {
    label: 'فواتير الصرف',
    icon: FileText,
    href: '#issue-vouchers',
    permissions: ['view-vouchers'],
  },
  {
    label: 'مرتجعات',
    icon: Repeat,
    href: '#return-vouchers',
    permissions: ['view-returns'],
  },
  {
    label: 'المدفوعات',
    icon: DollarSign,
    href: '#payments',
    permissions: ['view-payments'],
    roles: ['manager', 'accountant'],
  },
  {
    label: 'التقارير',
    icon: BarChart3,
    href: '#reports',
    permissions: ['view-reports'],
  },
  {
    label: 'المخازن',
    icon: Building2,
    href: '#branches',
    roles: ['manager'],
  },
  {
    label: 'المشتريات',
    icon: ShoppingCart,
    href: '#purchases',
    permissions: ['manage-inventory'],
  },
  {
    label: 'الجرد',
    icon: ClipboardList,
    href: '#inventory',
    permissions: ['manage-inventory'],
  },
  {
    label: 'الإعدادات',
    icon: Settings,
    href: '#settings',
    roles: ['manager'],
  },
]

export function Sidebar({ isOpen, onClose }: SidebarProps) {
  const { user } = useAuth()

  // Filter navigation items based on user roles/permissions
  const filteredNav = navigationItems.filter((item) => {
    // If no restrictions, show to everyone
    if (!item.roles && !item.permissions) return true

    // Check roles
    if (item.roles) {
      const userRoles = user?.roles?.map((r) => r?.name?.toLowerCase()).filter(Boolean) || []
      const hasRole = item.roles.some((role) => userRoles.includes(role))
      if (!hasRole) return false
    }

    // Check permissions (if user has permissions array)
    if (item.permissions && user?.permissions) {
      const userPermissions = user.permissions.map((p) => p?.name).filter(Boolean) || []
      const hasPermission = item.permissions.some((perm) =>
        userPermissions.includes(perm)
      )
      if (!hasPermission) return false
    }

    return true
  })

  return (
    <>
      {/* Sidebar Container */}
      <aside
        className={cn(
          'fixed top-0 right-0 z-30 h-screen w-64 bg-white border-l border-gray-200 transform transition-transform duration-300 ease-in-out',
          isOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'
        )}
      >
        {/* Sidebar Header */}
        <div className="flex items-center justify-between h-16 px-4 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <Package className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-gray-900">نظام المخزون</h1>
              <p className="text-xs text-gray-500">إدارة متكاملة</p>
            </div>
          </div>
          
          {/* Close button (mobile only) */}
          <button
            onClick={onClose}
            className="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
            aria-label="Close sidebar"
          >
            <X className="w-5 h-5 text-gray-600" />
          </button>
        </div>

        {/* Navigation Menu */}
        <nav className="flex-1 overflow-y-auto p-4 space-y-1">
          {filteredNav.map((item) => {
            const Icon = item.icon
            return (
              <a
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  'hover:bg-blue-50 hover:text-blue-700',
                  'text-gray-700'
                )}
                onClick={(e) => {
                  e.preventDefault()
                  onClose()
                  // TODO: Handle navigation with router
                }}
              >
                <Icon className="w-5 h-5" />
                <span>{item.label}</span>
              </a>
            )
          })}
        </nav>

        {/* Sidebar Footer */}
        <div className="border-t border-gray-200 p-4">
          <div className="flex items-center gap-3 px-3 py-2 bg-gray-50 rounded-lg">
            <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
              <span className="text-blue-700 font-bold text-sm">
                {user?.name?.charAt(0) || 'U'}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {user?.name || 'المستخدم'}
              </p>
              <p className="text-xs text-gray-500 truncate">
                {user?.email || ''}
              </p>
            </div>
          </div>
        </div>
      </aside>
    </>
  )
}
