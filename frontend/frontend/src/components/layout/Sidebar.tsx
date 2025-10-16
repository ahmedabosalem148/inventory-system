import { useState, useEffect } from 'react'
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
  ShoppingBag,
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
    // Available to all roles
  },
  {
    label: 'المنتجات',
    icon: Package,
    href: '#products',
    roles: ['manager', 'store_user'],
  },
  {
    label: 'المبيعات',
    icon: ShoppingCart,
    href: '#sales',
    roles: ['manager', 'accounting', 'store_user'],
  },
  {
    label: 'المشتريات',
    icon: ShoppingBag,
    href: '#purchases',
    roles: ['manager', 'store_user'],
  },
  {
    label: 'العملاء',
    icon: Users,
    href: '#customers',
    roles: ['manager', 'accounting'],
  },
  {
    label: 'الموردين',
    icon: Users,
    href: '#suppliers',
    roles: ['manager', 'accounting'],
  },
  {
    label: 'فواتير الصرف',
    icon: FileText,
    href: '#issue-vouchers',
    roles: ['manager', 'store_user'],
  },
  {
    label: 'مرتجعات',
    icon: Repeat,
    href: '#return-vouchers',
    roles: ['manager', 'store_user'],
  },
  {
    label: 'المدفوعات',
    icon: DollarSign,
    href: '#payments',
    roles: ['manager', 'accounting'],
  },
  {
    label: 'الشيكات',
    icon: DollarSign,
    href: '#cheques',
    roles: ['manager', 'accounting'],
  },
  {
    label: 'التقارير',
    icon: BarChart3,
    href: '#reports',
    roles: ['manager', 'accounting'],
  },
  {
    label: 'المخازن',
    icon: Building2,
    href: '#branches',
    roles: ['manager'],
  },
  {
    label: 'الجرد',
    icon: ClipboardList,
    href: '#inventory',
    roles: ['manager', 'store_user'],
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
  const [currentHash, setCurrentHash] = useState(window.location.hash)

  // Listen to hash changes
  useEffect(() => {
    const handleHashChange = () => {
      setCurrentHash(window.location.hash)
    }

    window.addEventListener('hashchange', handleHashChange)
    return () => window.removeEventListener('hashchange', handleHashChange)
  }, [])

  // Filter navigation items based on user roles
  const filteredNav = navigationItems.filter((item) => {
    // If no restrictions, show to everyone (like dashboard)
    if (!item.roles && !item.permissions) return true

    // Check roles (if specified)
    if (item.roles) {
      // Handle both string array and object array formats
      const userRoles = user?.roles?.map((r) => 
        typeof r === 'string' ? r.toLowerCase() : r?.name?.toLowerCase()
      ).filter(Boolean) || []
      
      console.log('🔍 Checking item:', item.label, '| Required roles:', item.roles, '| User roles:', userRoles)
      
      const hasRole = item.roles.some((role) => userRoles.includes(role.toLowerCase()))
      
      console.log('✅ Has role?', hasRole)
      
      return hasRole
    }

    // Check permissions (if specified) - fallback
    if (item.permissions) {
      // If user doesn't have any permissions, deny access
      if (!user?.permissions && !user?.all_permissions) {
        return false
      }
      
      // Handle both string array and object array formats
      const userPermissions = [
        ...(user.permissions?.map((p) => typeof p === 'string' ? p : p?.name).filter(Boolean) || []),
        ...(user.all_permissions?.map((p) => typeof p === 'string' ? p : p?.name).filter(Boolean) || [])
      ]
      
      const hasPermission = item.permissions.some((perm) =>
        userPermissions.includes(perm)
      )
      return hasPermission
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
            const isActive = currentHash === item.href
            return (
              <a
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-blue-100 text-blue-700'
                    : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700'
                )}
                onClick={() => {
                  onClose()
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
