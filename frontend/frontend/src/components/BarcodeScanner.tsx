/**
 * BarcodeScanner Component
 * Camera-based barcode scanner using html5-qrcode
 * Supports multiple barcode formats with manual input fallback
 */

import { useEffect, useRef, useState } from 'react'
import { Html5Qrcode } from 'html5-qrcode'
import { Camera, X, Keyboard, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'

interface BarcodeScannerProps {
  isOpen: boolean
  onClose: () => void
  onScan: (barcode: string) => void
}

export function BarcodeScanner({ isOpen, onClose, onScan }: BarcodeScannerProps) {
  const [scanning, setScanning] = useState(false)
  const [manualInput, setManualInput] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [cameraStarted, setCameraStarted] = useState(false)
  const scannerRef = useRef<Html5Qrcode | null>(null)
  const scannerIdRef = useRef<string>('barcode-reader')

  // Initialize scanner when opened
  useEffect(() => {
    if (isOpen) {
      startScanner()
    }
    
    return () => {
      stopScanner()
    }
  }, [isOpen])

  const startScanner = async () => {
    try {
      setError(null)
      setScanning(true)

      // Create scanner instance
      if (!scannerRef.current) {
        scannerRef.current = new Html5Qrcode(scannerIdRef.current)
      }

      // Request camera permission and start
      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
      }

      await scannerRef.current.start(
        { facingMode: 'environment' }, // Prefer back camera on mobile
        config,
        handleScanSuccess,
        handleScanError
      )

      setCameraStarted(true)
      setScanning(false)
    } catch (err: any) {
      console.error('Failed to start scanner:', err)
      setScanning(false)
      
      if (err.name === 'NotAllowedError' || err.message?.includes('Permission')) {
        setError('يرجى السماح بالوصول إلى الكاميرا لمسح الباركود')
      } else if (err.name === 'NotFoundError' || err.message?.includes('Camera')) {
        setError('لا توجد كاميرا متاحة على هذا الجهاز')
      } else {
        setError('فشل بدء الكاميرا. يمكنك إدخال الباركود يدوياً')
      }
    }
  }

  const stopScanner = async () => {
    try {
      if (scannerRef.current && cameraStarted) {
        await scannerRef.current.stop()
        setCameraStarted(false)
      }
    } catch (err) {
      console.error('Error stopping scanner:', err)
    }
  }

  const handleScanSuccess = (decodedText: string) => {
    console.log('Barcode scanned:', decodedText)
    
    // Play beep sound (optional)
    try {
      const beep = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBDGH0fPTgjMGHm7A7+OZUQ0PVanf8rpnHgU0jdXu1YU6BxphtvDsmFAND1mk4u+zZSAFN4nW8tqCNggbb7zs5JlQDQ9Vqd/yumceBTSN1e7VhToHGmG28OyYUA0PWaTi77NlIAU3iNXy2oI2Bxtvuuzk')
      beep.play()
    } catch (e) {
      // Ignore audio errors
    }

    // Visual feedback
    const readerElement = document.getElementById(scannerIdRef.current)
    if (readerElement) {
      readerElement.style.border = '3px solid #10b981'
      setTimeout(() => {
        readerElement.style.border = '1px solid #e5e7eb'
      }, 500)
    }

    // Call callback and close
    onScan(decodedText)
    handleClose()
  }

  const handleScanError = () => {
    // Ignore common scanning errors (happens frequently while scanning)
    // Only log for debugging
    // console.warn('Scan error:', errorMessage)
  }

  const handleManualSubmit = () => {
    const barcode = manualInput.trim()
    if (barcode) {
      onScan(barcode)
      setManualInput('')
      handleClose()
    }
  }

  const handleClose = () => {
    stopScanner()
    setManualInput('')
    setError(null)
    onClose()
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <Card className="w-full max-w-2xl bg-white">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b">
          <div className="flex items-center gap-2">
            <Camera className="h-5 w-5 text-blue-600" />
            <h2 className="text-lg font-semibold">مسح الباركود</h2>
          </div>
          <Button
            variant="ghost"
            size="sm"
            onClick={handleClose}
            className="h-8 w-8 p-0"
          >
            <X className="h-4 w-4" />
          </Button>
        </div>

        {/* Content */}
        <div className="p-4 space-y-4">
          {/* Camera Preview */}
          <div className="relative">
            <div 
              id={scannerIdRef.current}
              className="w-full border border-gray-300 rounded-lg overflow-hidden bg-black"
              style={{ minHeight: '300px' }}
            />
            
            {scanning && (
              <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div className="text-white text-center">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-2" />
                  <p>جاري تشغيل الكاميرا...</p>
                </div>
              </div>
            )}
          </div>

          {/* Instructions */}
          {cameraStarted && !error && (
            <div className="flex items-start gap-2 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
              <Camera className="h-4 w-4 mt-0.5 flex-shrink-0" />
              <p>قم بتوجيه الكاميرا نحو الباركود. سيتم المسح تلقائياً عند التعرف عليه.</p>
            </div>
          )}

          {/* Error Message */}
          {error && (
            <div className="flex items-start gap-2 p-3 bg-red-50 rounded-lg text-sm text-red-800">
              <AlertCircle className="h-4 w-4 mt-0.5 flex-shrink-0" />
              <div>
                <p className="font-medium">{error}</p>
                <p className="mt-1 text-xs">يمكنك إدخال الباركود يدوياً أدناه</p>
              </div>
            </div>
          )}

          {/* Manual Input Fallback */}
          <div className="space-y-2">
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <Keyboard className="h-4 w-4" />
              <span>أو أدخل الباركود يدوياً:</span>
            </div>
            <div className="flex gap-2">
              <Input
                value={manualInput}
                onChange={(e) => setManualInput(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && handleManualSubmit()}
                placeholder="اكتب رقم الباركود..."
                className="flex-1"
                autoFocus={!!error}
              />
              <Button 
                onClick={handleManualSubmit}
                disabled={!manualInput.trim()}
              >
                بحث
              </Button>
            </div>
          </div>

          {/* Close Button */}
          <div className="flex justify-end pt-2">
            <Button variant="outline" onClick={handleClose}>
              إغلاق
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
