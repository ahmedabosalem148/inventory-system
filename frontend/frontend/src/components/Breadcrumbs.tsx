/**
 * Breadcrumbs Navigation Component
 * Shows current page hierarchy for better navigation
 */

import { ChevronLeft, Home } from 'lucide-react'
import { cn } from '@/lib/utils'

export interface BreadcrumbItem {
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
