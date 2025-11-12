import { useState, type ReactNode } from 'react'
import { Sidebar } from './Sidebar'
import { Navbar } from './Navbar'
import { Breadcrumbs } from '@/components/Breadcrumbs'
import { useBreadcrumbs } from '@/components/BreadcrumbsUtils'

interface AppLayoutProps {
  children: ReactNode
}

export function AppLayout({ children }: AppLayoutProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  
  // Get current page from URL hash
  const currentPage = window.location.hash.slice(1) || 'dashboard'
  const breadcrumbs = useBreadcrumbs(currentPage)

  return (
    <div className="min-h-screen bg-gray-50" dir="rtl">
      {/* Sidebar */}
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      {/* Main Content Area */}
      <div className="lg:pr-64 transition-all duration-300">
        {/* Top Navbar */}
        <Navbar onMenuClick={() => setSidebarOpen(true)} />

        {/* Breadcrumbs - Only show if not on dashboard */}
        {breadcrumbs.length > 0 && (
          <div className="px-4 md:px-6 lg:px-8 pt-4 pb-2">
            <Breadcrumbs items={breadcrumbs} />
          </div>
        )}

        {/* Page Content */}
        <main className="p-4 md:p-6 lg:p-8">
          {children}
        </main>
      </div>

      {/* Mobile Overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}
    </div>
  )
}
