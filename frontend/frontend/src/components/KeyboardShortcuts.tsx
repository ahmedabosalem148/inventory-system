/**
 * Keyboard Shortcuts Handler
 * Global keyboard shortcuts for faster navigation and actions
 */

import { useEffect, useState } from 'react'
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle,
  DialogDescription
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Keyboard } from 'lucide-react'

interface Shortcut {
  keys: string[]
  description: string
  action: string
  category: string
}

const shortcuts: Shortcut[] = [
  // Navigation
  { keys: ['Ctrl', 'H'], description: 'الذهاب للصفحة الرئيسية', action: '#', category: 'التنقل' },
  { keys: ['Ctrl', 'K'], description: 'البحث السريع', action: 'search', category: 'التنقل' },
  { keys: ['Ctrl', 'B'], description: 'فتح/إغلاق القائمة الجانبية', action: 'toggle-sidebar', category: 'التنقل' },
  
  // Quick Actions
  { keys: ['Ctrl', 'N'], description: 'إنشاء جديد', action: 'new', category: 'إجراءات' },
  { keys: ['Ctrl', 'S'], description: 'حفظ', action: 'save', category: 'إجراءات' },
  { keys: ['Ctrl', 'E'], description: 'تعديل', action: 'edit', category: 'إجراءات' },
  { keys: ['Ctrl', 'P'], description: 'طباعة', action: 'print', category: 'إجراءات' },
  
  // Dialogs
  { keys: ['Esc'], description: 'إغلاق النوافذ المنبثقة', action: 'close-dialog', category: 'نوافذ' },
  { keys: ['Enter'], description: 'تأكيد (في النوافذ)', action: 'confirm', category: 'نوافذ' },
  
  // Reports
  { keys: ['Ctrl', 'R'], description: 'التقارير', action: '#reports', category: 'تقارير' },
  { keys: ['Ctrl', 'X'], description: 'تصدير Excel', action: 'export', category: 'تقارير' },
]

export function KeyboardShortcuts() {
  const [showHelp, setShowHelp] = useState(false)

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Show shortcuts help: Ctrl+?
      if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault()
        setShowHelp(true)
        return
      }

      // Don't trigger shortcuts when typing in input fields
      const target = e.target as HTMLElement
      if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
        return
      }

      // Global shortcuts
      if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
          case 'h':
            e.preventDefault()
            window.location.hash = '#'
            break
          case 'k':
            e.preventDefault()
            // TODO: Implement global search
            console.log('Global search triggered')
            break
          case 'b':
            e.preventDefault()
            // TODO: Toggle sidebar
            console.log('Toggle sidebar')
            break
          case 'r':
            e.preventDefault()
            window.location.hash = '#reports'
            break
        }
      }

      // Escape to close dialogs (handled by dialog components)
      if (e.key === 'Escape') {
        setShowHelp(false)
      }
    }

    window.addEventListener('keydown', handleKeyDown)
    return () => window.removeEventListener('keydown', handleKeyDown)
  }, [])

  const categories = Array.from(new Set(shortcuts.map(s => s.category)))

  return (
    <>
      {/* Help Dialog */}
      <Dialog open={showHelp} onOpenChange={setShowHelp}>
        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Keyboard className="w-5 h-5 text-blue-600" />
              اختصارات لوحة المفاتيح
            </DialogTitle>
            <DialogDescription>
              استخدم هذه الاختصارات لتسريع عملك في النظام
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-6 mt-4">
            {categories.map(category => (
              <div key={category}>
                <h3 className="font-semibold text-lg mb-3 text-gray-700 border-b pb-2">
                  {category}
                </h3>
                <div className="space-y-2">
                  {shortcuts
                    .filter(s => s.category === category)
                    .map((shortcut, index) => (
                      <div 
                        key={index}
                        className="flex items-center justify-between p-2 rounded hover:bg-gray-50"
                      >
                        <span className="text-gray-700">{shortcut.description}</span>
                        <div className="flex gap-1">
                          {shortcut.keys.map((key, i) => (
                            <Badge 
                              key={i} 
                              variant="outline"
                              className="bg-gray-100 font-mono text-xs"
                            >
                              {key}
                            </Badge>
                          ))}
                        </div>
                      </div>
                    ))}
                </div>
              </div>
            ))}
          </div>

          <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p className="text-sm text-blue-800 text-center">
              💡 اضغط <Badge variant="outline" className="mx-1 bg-white font-mono">Ctrl</Badge> + 
              <Badge variant="outline" className="mx-1 bg-white font-mono">/</Badge> 
              لعرض هذه القائمة في أي وقت
            </p>
          </div>
        </DialogContent>
      </Dialog>

      {/* Floating hint (shown on first load) */}
      <div className="fixed bottom-4 left-4 z-50 hidden md:block">
        <button
          onClick={() => setShowHelp(true)}
          className="bg-gray-800 text-white px-3 py-2 rounded-lg shadow-lg hover:bg-gray-700 transition-colors text-sm flex items-center gap-2"
        >
          <Keyboard className="w-4 h-4" />
          <span>اختصارات</span>
          <Badge variant="outline" className="bg-gray-700 border-gray-600 font-mono text-xs">
            Ctrl+/
          </Badge>
        </button>
      </div>
    </>
  )
}
