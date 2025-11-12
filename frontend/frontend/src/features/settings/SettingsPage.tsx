/**
 * Settings & Configuration Page
 * Manage company info, users, and system settings
 */

import { useState } from 'react'
import { 
  Building2, 
  Users, 
  Settings as SettingsIcon,
  Save,
  Upload,
  Database
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { UsersPage } from '@/features/users'

type SettingsTab = 'company' | 'users' | 'system'

export const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState<SettingsTab>('company')

  // Company Settings State
  const [companyData, setCompanyData] = useState({
    name: 'شركة المخزون النموذجية',
    commercial_register: '1234567890',
    tax_number: '300123456789003',
    phone: '+966501234567',
    email: 'info@company.com',
    address: 'الرياض، المملكة العربية السعودية',
    currency: 'SAR',
    fiscal_year_start: '01-01',
  })

  // System Settings State
  const [systemData, setSystemData] = useState({
    language: 'ar',
    timezone: 'Asia/Riyadh',
    date_format: 'Y-m-d',
    low_stock_threshold: 10,
    auto_backup: true,
    email_notifications: true,
    sms_notifications: false,
  })

  const tabs = [
    {
      id: 'company' as SettingsTab,
      label: 'معلومات الشركة',
      icon: Building2,
    },
    {
      id: 'users' as SettingsTab,
      label: 'المستخدمين',
      icon: Users,
    },
    {
      id: 'system' as SettingsTab,
      label: 'إعدادات النظام',
      icon: SettingsIcon,
    },
  ]

  const renderCompanySettings = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">معلومات الشركة</h3>
          <Button size="sm">
            <Save className="h-4 w-4 ml-2" />
            حفظ التغييرات
          </Button>
        </div>

        {/* Company Logo */}
        <div>
          <label className="block text-sm font-medium mb-2">شعار الشركة</label>
          <div className="flex items-center gap-4">
            <div className="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
              <Building2 className="h-12 w-12 text-gray-400" />
            </div>
            <Button variant="outline" size="sm">
              <Upload className="h-4 w-4 ml-2" />
              تحميل شعار
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Company Name */}
          <div>
            <label className="block text-sm font-medium mb-2">
              اسم الشركة <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={companyData.name}
              onChange={(e) => setCompanyData({ ...companyData, name: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>

          {/* Commercial Register */}
          <div>
            <label className="block text-sm font-medium mb-2">السجل التجاري</label>
            <input
              type="text"
              value={companyData.commercial_register}
              onChange={(e) => setCompanyData({ ...companyData, commercial_register: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>

          {/* Tax Number */}
          <div>
            <label className="block text-sm font-medium mb-2">الرقم الضريبي</label>
            <input
              type="text"
              value={companyData.tax_number}
              onChange={(e) => setCompanyData({ ...companyData, tax_number: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>

          {/* Phone */}
          <div>
            <label className="block text-sm font-medium mb-2">الهاتف</label>
            <input
              type="text"
              value={companyData.phone}
              onChange={(e) => setCompanyData({ ...companyData, phone: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>

          {/* Email */}
          <div>
            <label className="block text-sm font-medium mb-2">البريد الإلكتروني</label>
            <input
              type="email"
              value={companyData.email}
              onChange={(e) => setCompanyData({ ...companyData, email: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>

          {/* Currency */}
          <div>
            <label className="block text-sm font-medium mb-2">العملة</label>
            <select
              value={companyData.currency}
              onChange={(e) => setCompanyData({ ...companyData, currency: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            >
              <option value="SAR">ريال سعودي (SAR)</option>
              <option value="USD">دولار أمريكي (USD)</option>
              <option value="EUR">يورو (EUR)</option>
            </select>
          </div>
        </div>

        {/* Address */}
        <div>
          <label className="block text-sm font-medium mb-2">العنوان</label>
          <textarea
            value={companyData.address}
            onChange={(e) => setCompanyData({ ...companyData, address: e.target.value })}
            rows={3}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
          />
        </div>
      </div>
    </Card>
  )

  const renderUsersManagement = () => (
    <div className="space-y-6">
      <UsersPage />
    </div>
  )

  const renderSystemSettings = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">إعدادات النظام</h3>
          <Button size="sm">
            <Save className="h-4 w-4 ml-2" />
            حفظ التغييرات
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Language */}
          <div>
            <label className="block text-sm font-medium mb-2">اللغة</label>
            <select
              value={systemData.language}
              onChange={(e) => setSystemData({ ...systemData, language: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            >
              <option value="ar">العربية</option>
              <option value="en">English</option>
            </select>
          </div>

          {/* Timezone */}
          <div>
            <label className="block text-sm font-medium mb-2">المنطقة الزمنية</label>
            <select
              value={systemData.timezone}
              onChange={(e) => setSystemData({ ...systemData, timezone: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            >
              <option value="Asia/Riyadh">الرياض (GMT+3)</option>
              <option value="Asia/Dubai">دبي (GMT+4)</option>
              <option value="Africa/Cairo">القاهرة (GMT+2)</option>
            </select>
          </div>

          {/* Low Stock Threshold */}
          <div>
            <label className="block text-sm font-medium mb-2">حد تنبيه المخزون المنخفض</label>
            <input
              type="number"
              value={systemData.low_stock_threshold}
              onChange={(e) => setSystemData({ ...systemData, low_stock_threshold: parseInt(e.target.value) })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>
        </div>

        {/* Notifications */}
        <div className="space-y-4 pt-4 border-t">
          <h4 className="font-semibold">الإشعارات</h4>
          
          <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
              <p className="font-medium">إشعارات البريد الإلكتروني</p>
              <p className="text-sm text-gray-500">استلام التنبيهات عبر البريد</p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={systemData.email_notifications}
                onChange={(e) => setSystemData({ ...systemData, email_notifications: e.target.checked })}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
              <p className="font-medium">إشعارات الرسائل النصية</p>
              <p className="text-sm text-gray-500">استلام التنبيهات عبر SMS</p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={systemData.sms_notifications}
                onChange={(e) => setSystemData({ ...systemData, sms_notifications: e.target.checked })}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
              <p className="font-medium">النسخ الاحتياطي التلقائي</p>
              <p className="text-sm text-gray-500">نسخ احتياطي يومي للبيانات</p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={systemData.auto_backup}
                onChange={(e) => setSystemData({ ...systemData, auto_backup: e.target.checked })}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>
        </div>

        {/* Backup & Maintenance */}
        <div className="space-y-4 pt-4 border-t">
          <h4 className="font-semibold">النسخ الاحتياطي والصيانة</h4>
          <div className="flex gap-3">
            <Button variant="outline">
              <Database className="h-4 w-4 ml-2" />
              إنشاء نسخة احتياطية
            </Button>
            <Button variant="outline">
              <Upload className="h-4 w-4 ml-2" />
              استعادة نسخة احتياطية
            </Button>
          </div>
        </div>
      </div>
    </Card>
  )

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">الإعدادات</h1>
        <p className="text-gray-600 dark:text-gray-400">
          إدارة إعدادات الشركة والنظام والمستخدمين
        </p>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 border-b border-gray-200 dark:border-gray-700">
        {tabs.map((tab) => {
          const Icon = tab.icon
          return (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-3 border-b-2 transition-colors ${
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600 font-semibold'
                  : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
              }`}
            >
              <Icon className="h-5 w-5" />
              {tab.label}
            </button>
          )
        })}
      </div>

      {/* Tab Content */}
      {activeTab === 'company' && renderCompanySettings()}
      {activeTab === 'users' && renderUsersManagement()}
      {activeTab === 'system' && renderSystemSettings()}
    </div>
  )
}
