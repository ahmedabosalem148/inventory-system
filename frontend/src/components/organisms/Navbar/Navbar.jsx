import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Menu, Bell, ChevronDown, Store, User, LogOut } from 'lucide-react';
import { useAuth } from '../../../contexts/AuthContext';

const Navbar = ({ onMenuClick }) => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [showBranchMenu, setShowBranchMenu] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [selectedBranch, setSelectedBranch] = useState('الفرع الرئيسي');

  const branches = [
    { id: 1, name: 'الفرع الرئيسي' },
    { id: 2, name: 'فرع الدقي' },
    { id: 3, name: 'فرع المهندسين' },
  ];

  const handleLogout = () => {
    logout();
    setShowUserMenu(false);
    navigate('/login');
  };

  return (
    <header className="h-16 bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 lg:right-64 z-30">
      <div className="h-full px-4 lg:px-6 flex items-center justify-between">
        {/* Right Side */}
        <div className="flex items-center gap-4">
          {/* Mobile Menu Button */}
          <button
            onClick={onMenuClick}
            className="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <Menu className="w-6 h-6 text-gray-700" />
          </button>

          {/* Branch Selector */}
          <div className="relative">
            <button
              onClick={() => setShowBranchMenu(!showBranchMenu)}
              className="flex items-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Store className="w-5 h-5 text-gray-600" />
              <span className="hidden sm:inline text-sm font-medium text-gray-700">
                {selectedBranch}
              </span>
              <ChevronDown className="w-4 h-4 text-gray-500" />
            </button>

            {/* Branch Dropdown */}
            {showBranchMenu && (
              <>
                <div 
                  className="fixed inset-0 z-10"
                  onClick={() => setShowBranchMenu(false)}
                />
                <div className="absolute top-full left-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-20">
                  {branches.map((branch) => (
                    <button
                      key={branch.id}
                      onClick={() => {
                        setSelectedBranch(branch.name);
                        setShowBranchMenu(false);
                      }}
                      className={`w-full px-4 py-2 text-right hover:bg-gray-50 transition-colors ${
                        selectedBranch === branch.name ? 'bg-blue-50 text-blue-600' : 'text-gray-700'
                      }`}
                    >
                      {branch.name}
                    </button>
                  ))}
                </div>
              </>
            )}
          </div>
        </div>

        {/* Left Side */}
        <div className="flex items-center gap-3">
          {/* Notifications */}
          <button className="relative p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <Bell className="w-6 h-6 text-gray-600" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
          </button>

          {/* User Menu */}
          <div className="relative">
            <button
              onClick={() => setShowUserMenu(!showUserMenu)}
              className="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <div className="hidden sm:block text-right">
                <p className="text-sm font-medium text-gray-700">
                  {user?.name || 'المستخدم'}
                </p>
                <p className="text-xs text-gray-500">{user?.role || 'مدير'}</p>
              </div>
              <div className="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <User className="w-5 h-5 text-white" />
              </div>
              <ChevronDown className="w-4 h-4 text-gray-500" />
            </button>

            {/* User Dropdown */}
            {showUserMenu && (
              <>
                <div 
                  className="fixed inset-0 z-10"
                  onClick={() => setShowUserMenu(false)}
                />
                <div className="absolute top-full left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-20">
                  <div className="px-4 py-3 border-b border-gray-200">
                    <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                    <p className="text-xs text-gray-500">{user?.email}</p>
                  </div>
                  <button className="w-full px-4 py-2 text-right text-sm text-gray-700 hover:bg-gray-50">
                    الملف الشخصي
                  </button>
                  <button className="w-full px-4 py-2 text-right text-sm text-gray-700 hover:bg-gray-50">
                    الإعدادات
                  </button>
                  <div className="border-t border-gray-200 mt-2 pt-2">
                    <button 
                      onClick={handleLogout}
                      className="w-full px-4 py-2 text-right text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                    >
                      <LogOut className="w-4 h-4" />
                      <span>تسجيل الخروج</span>
                    </button>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};

export default Navbar;
