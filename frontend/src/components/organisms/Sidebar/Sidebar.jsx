import { Home, Package, FileText, RotateCcw, Users, BarChart3, Settings, LogOut, Building2, ClipboardCheck } from 'lucide-react';
import { NavLink } from 'react-router-dom';
import { clsx } from 'clsx';

const Sidebar = ({ isOpen, onClose }) => {
  const navigation = [
    { name: 'لوحة التحكم', href: '/dashboard', icon: Home },
    { name: 'المنتجات', href: '/products', icon: Package },
    { name: 'المخازن/الفروع', href: '/branches', icon: Building2 },
    { name: 'جرد المخزون', href: '/inventory-counts', icon: ClipboardCheck },
    { name: 'أذونات الصرف', href: '/issue-vouchers', icon: FileText },
    { name: 'أذونات الإرجاع', href: '/return-vouchers', icon: RotateCcw },
    { name: 'العملاء', href: '/customers', icon: Users },
    { name: 'التقارير', href: '/reports', icon: BarChart3 },
    { name: 'الإعدادات', href: '/settings', icon: Settings },
  ];

  return (
    <>
      {/* Mobile Overlay */}
      {isOpen && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar */}
      <aside
        className={clsx(
          'fixed top-0 right-0 z-50 h-full bg-white shadow-xl transition-transform duration-300 ease-in-out',
          'w-64 lg:translate-x-0',
          isOpen ? 'translate-x-0' : 'translate-x-full'
        )}
      >
        {/* Logo */}
        <div className="h-16 flex items-center justify-between px-6 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <Package className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-gray-900">المخزون</h1>
              <p className="text-xs text-gray-500">نظام الإدارة</p>
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="p-4 space-y-1">
          {navigation.map((item) => (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200',
                  'hover:bg-blue-50 group',
                  isActive
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'text-gray-700 hover:text-blue-600'
                )
              }
            >
              {({ isActive }) => (
                <>
                  <item.icon
                    className={clsx(
                      'w-5 h-5 transition-colors',
                      isActive ? 'text-white' : 'text-gray-400 group-hover:text-blue-600'
                    )}
                  />
                  <span className="font-medium">{item.name}</span>
                </>
              )}
            </NavLink>
          ))}
        </nav>

        {/* Logout Button */}
        <div className="absolute bottom-0 right-0 left-0 p-4 border-t border-gray-200">
          <button
            className="flex items-center gap-3 px-4 py-3 w-full rounded-lg text-red-600 hover:bg-red-50 transition-colors"
          >
            <LogOut className="w-5 h-5" />
            <span className="font-medium">تسجيل الخروج</span>
          </button>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
