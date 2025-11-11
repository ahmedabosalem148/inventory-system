import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import { Button } from '@/components/ui/button'
import { SearchInput } from '@/components/ui/search-input'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import {
  Menu,
  Bell,
  User,
  Settings,
  LogOut,
  Building2,
  Key,
} from 'lucide-react'
import { PasswordChangeDialog } from '@/components/PasswordChangeDialog'

interface NavbarProps {
  onMenuClick: () => void
}

export function Navbar({ onMenuClick }: NavbarProps) {
  const { user, logout } = useAuth()
  const [selectedBranch, setSelectedBranch] = useState('branch-1')
  const [showPasswordDialog, setShowPasswordDialog] = useState(false)

  const handleLogout = () => {
    if (confirm('هل تريد تسجيل الخروج؟')) {
      logout()
    }
  }

  // Mock notifications count
  const notificationsCount = 3

  return (
    <header className="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
      <div className="flex items-center justify-between h-16 px-4 md:px-6">
        {/* Right Side: Menu + Search */}
        <div className="flex items-center gap-3 flex-1">
          {/* Mobile Menu Button */}
          <Button
            variant="ghost"
            size="icon"
            onClick={onMenuClick}
            className="lg:hidden"
            aria-label="Open menu"
          >
            <Menu className="w-5 h-5" />
          </Button>

          {/* Search Input */}
          <div className="hidden md:block w-full max-w-md">
            <SearchInput
              placeholder="ابحث عن منتج، عميل، فاتورة..."
              onSearch={() => {
                // TODO: Implement search
              }}
            />
          </div>
        </div>

        {/* Left Side: Branch + Notifications + User Menu */}
        <div className="flex items-center gap-2 md:gap-4">
          {/* Branch Selector */}
          <div className="hidden md:flex items-center gap-2">
            <Building2 className="w-5 h-5 text-gray-500" />
            <Select value={selectedBranch} onValueChange={setSelectedBranch}>
              <SelectTrigger className="w-40">
                <SelectValue placeholder="اختر المخزن" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="branch-1">مخزن العتبة</SelectItem>
                <SelectItem value="branch-2">مخزن إمبابة</SelectItem>
                <SelectItem value="branch-3">مخزن المصنع</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Notifications */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="relative">
                <Bell className="w-5 h-5" />
                {notificationsCount > 0 && (
                  <Badge
                    variant="danger"
                    className="absolute -top-1 -left-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                  >
                    {notificationsCount}
                  </Badge>
                )}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
              <div className="p-4 border-b">
                <h3 className="font-semibold">الإشعارات</h3>
                <p className="text-xs text-gray-500 mt-1">
                  لديك {notificationsCount} إشعارات جديدة
                </p>
              </div>
              <div className="max-h-96 overflow-y-auto">
                {/* Notification Items */}
                <div className="p-3 hover:bg-gray-50 cursor-pointer border-b">
                  <p className="text-sm font-medium">منتج على وشك النفاد</p>
                  <p className="text-xs text-gray-500 mt-1">
                    لمبة LED 10W - المخزون: 5 قطع
                  </p>
                  <p className="text-xs text-gray-400 mt-1">منذ ساعتين</p>
                </div>
                <div className="p-3 hover:bg-gray-50 cursor-pointer border-b">
                  <p className="text-sm font-medium">فاتورة جديدة</p>
                  <p className="text-xs text-gray-500 mt-1">
                    فاتورة صرف #1523 - محمد أحمد
                  </p>
                  <p className="text-xs text-gray-400 mt-1">منذ 3 ساعات</p>
                </div>
                <div className="p-3 hover:bg-gray-50 cursor-pointer">
                  <p className="text-sm font-medium">دفعة مستحقة</p>
                  <p className="text-xs text-gray-500 mt-1">
                    أحمد علي - 5,000 جنيه
                  </p>
                  <p className="text-xs text-gray-400 mt-1">منذ 5 ساعات</p>
                </div>
              </div>
              <div className="p-2 border-t">
                <Button variant="ghost" size="sm" className="w-full">
                  عرض كل الإشعارات
                </Button>
              </div>
            </DropdownMenuContent>
          </DropdownMenu>

          {/* User Menu */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="flex items-center gap-2">
                <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <User className="w-4 h-4 text-blue-700" />
                </div>
                <span className="hidden md:inline text-sm font-medium">
                  {user?.name || 'المستخدم'}
                </span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <div className="p-3 border-b">
                <p className="text-sm font-medium">{user?.name}</p>
                <p className="text-xs text-gray-500">{user?.email}</p>
                {user?.roles && user.roles.length > 0 && (
                  <div className="mt-2">
                    <Badge variant="secondary" className="text-xs">
                      {typeof user.roles[0] === 'string' ? user.roles[0] : user.roles[0]?.name || 'مستخدم'}
                    </Badge>
                  </div>
                )}
              </div>
              <DropdownMenuItem 
                className="cursor-pointer"
                onClick={() => window.location.hash = '#profile'}
              >
                <User className="w-4 h-4 ml-2" />
                الملف الشخصي
              </DropdownMenuItem>
              <DropdownMenuItem 
                className="cursor-pointer"
                onClick={() => setShowPasswordDialog(true)}
              >
                <Key className="w-4 h-4 ml-2" />
                تغيير كلمة المرور
              </DropdownMenuItem>
              <DropdownMenuItem className="cursor-pointer">
                <Settings className="w-4 h-4 ml-2" />
                الإعدادات
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={handleLogout}
                className="cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50"
              >
                <LogOut className="w-4 h-4 ml-2" />
                تسجيل الخروج
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      {/* Mobile Search Bar */}
      <div className="md:hidden px-4 pb-3">
        <SearchInput
          placeholder="ابحث..."
          onSearch={() => {
            // TODO: Implement search
          }}
        />
      </div>

      {/* Password Change Dialog */}
      <PasswordChangeDialog
        isOpen={showPasswordDialog}
        onClose={() => setShowPasswordDialog(false)}
      />
    </header>
  )
}
