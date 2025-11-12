# Priority 3: Barcode Scanner System - Implementation Plan

**Date**: November 11, 2025  
**Status**: IN PROGRESS  
**Estimated Time**: 2-3 hours

---

## Overview

Implement a barcode scanner system that allows users to scan product barcodes using their device camera (mobile/desktop) for quick product lookup and selection in:
- Sales page (when adding products to invoice)
- Transfer page (when adding products to transfer)
- Inventory management (quick product search)

---

## Technical Stack

### Frontend Library
- **html5-qrcode**: Modern, lightweight barcode/QR scanner
  - Supports multiple formats (EAN-13, Code-128, QR, etc.)
  - Works on mobile and desktop
  - Camera permission handling
  - No native dependencies
  - Active maintenance

### Alternative Considered
- ~~quagga2~~: More complex, larger bundle size
- ~~@zxing/browser~~: Heavier, older API

---

## Features to Implement

### 1. BarcodeScanner Component

**Location**: `frontend/src/components/BarcodeScanner.tsx`

**Features**:
- Camera video preview
- Multiple barcode format support
- Success/error visual feedback
- Sound feedback (beep on success)
- Manual barcode input fallback
- Close/cancel button
- Loading states
- Permission error handling
- Arabic UI

**Props**:
```typescript
interface BarcodeScannerProps {
  onScan: (barcode: string) => void
  onClose: () => void
  isOpen: boolean
}
```

### 2. Product Lookup Integration

**Backend Endpoint**: Already exists
```php
GET /api/products?search={barcode}
```

**Frontend Service**: Already exists in `productsApi.ts`
```typescript
export const getProducts = async (params?: ProductParams): Promise<ProductResponse>
```

### 3. Sales Page Integration

**Location**: `frontend/src/pages/SalesPage.tsx`

**Changes**:
- Add "مسح الباركود" button next to product search
- Open scanner modal on click
- On successful scan:
  - Look up product by barcode
  - If found: Add to sale items automatically
  - If not found: Show error message
  - Close scanner

### 4. Transfer Page Integration

**Location**: `frontend/src/pages/TransfersPage.tsx`

**Changes**:
- Add "مسح الباركود" button in transfer form
- Same logic as sales page
- Add product to transfer items on successful scan

---

## Implementation Steps

### Step 1: Install Dependencies
```bash
npm install html5-qrcode
npm install --save-dev @types/html5-qrcode
```

### Step 2: Create BarcodeScanner Component
- Camera preview with Html5Qrcode
- Handle camera permissions
- Decode barcode and call onScan callback
- Visual feedback (green border on success, red on error)
- Manual input field as fallback
- Close button
- Loading spinner while initializing camera

### Step 3: Integrate into SalesPage
- Add state for scanner visibility
- Add "Scan" button in product selection area
- Handle scan result: lookup product and add to items
- Show success/error toast messages

### Step 4: Integrate into TransfersPage
- Same integration as SalesPage
- Adapt for transfer form context

### Step 5: Testing
- Test on desktop with webcam
- Test on mobile device
- Test with various barcode formats
- Test error cases (no camera, permission denied)
- Test manual input fallback

---

## Component Design

### BarcodeScanner.tsx Structure

```typescript
import { useEffect, useRef, useState } from 'react'
import { Html5Qrcode } from 'html5-qrcode'
import { Camera, X, Keyboard } from 'lucide-react'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface BarcodeScannerProps {
  isOpen: boolean
  onClose: () => void
  onScan: (barcode: string) => void
}

export function BarcodeScanner({ isOpen, onClose, onScan }: BarcodeScannerProps) {
  const [scanning, setScanning] = useState(false)
  const [manualInput, setManualInput] = useState('')
  const [error, setError] = useState<string | null>(null)
  const scannerRef = useRef<Html5Qrcode | null>(null)
  const readerRef = useRef<HTMLDivElement>(null)

  // Initialize scanner when opened
  useEffect(() => {
    if (isOpen) {
      startScanner()
    } else {
      stopScanner()
    }
    return () => stopScanner()
  }, [isOpen])

  const startScanner = async () => {
    // Initialize Html5Qrcode
    // Start camera with config
    // Handle success/error
  }

  const stopScanner = async () => {
    // Stop camera
    // Cleanup
  }

  const handleScanSuccess = (decodedText: string) => {
    // Play beep sound
    // Call onScan callback
    // Close scanner
  }

  const handleManualSubmit = () => {
    if (manualInput.trim()) {
      onScan(manualInput.trim())
      setManualInput('')
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>مسح الباركود</DialogTitle>
        </DialogHeader>
        
        {/* Camera Preview */}
        <div ref={readerRef} id="reader" className="w-full" />
        
        {/* Error Message */}
        {error && <div className="text-red-500">{error}</div>}
        
        {/* Manual Input Fallback */}
        <div className="space-y-2">
          <label>أو أدخل الباركود يدوياً:</label>
          <div className="flex gap-2">
            <Input
              value={manualInput}
              onChange={(e) => setManualInput(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleManualSubmit()}
              placeholder="اكتب رقم الباركود"
            />
            <Button onClick={handleManualSubmit}>بحث</Button>
          </div>
        </div>
        
        {/* Close Button */}
        <Button variant="outline" onClick={onClose}>إغلاق</Button>
      </DialogContent>
    </Dialog>
  )
}
```

### SalesPage Integration

```typescript
// Add state
const [scannerOpen, setScannerOpen] = useState(false)

// Add handler
const handleBarcodeScan = async (barcode: string) => {
  try {
    const response = await getProducts({ search: barcode })
    
    if (response.data.length > 0) {
      const product = response.data[0]
      
      // Check if product already in items
      const existingItem = saleItems.find(item => item.product_id === product.id)
      
      if (existingItem) {
        // Increment quantity
        setSaleItems(saleItems.map(item =>
          item.product_id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        ))
      } else {
        // Add new item
        setSaleItems([...saleItems, {
          product_id: product.id,
          product_name: product.name,
          quantity: 1,
          price: product.price,
          total: product.price
        }])
      }
      
      toast.success(`تم إضافة ${product.name}`)
    } else {
      toast.error('لم يتم العثور على المنتج')
    }
  } catch (error) {
    toast.error('خطأ في البحث عن المنتج')
  } finally {
    setScannerOpen(false)
  }
}

// Add button in JSX
<Button
  onClick={() => setScannerOpen(true)}
  className="flex items-center gap-2"
>
  <Camera className="h-4 w-4" />
  مسح الباركود
</Button>

// Add component
<BarcodeScanner
  isOpen={scannerOpen}
  onClose={() => setScannerOpen(false)}
  onScan={handleBarcodeScan}
/>
```

---

## Barcode Format Support

**Supported Formats**:
- EAN-13 (most common for retail products)
- EAN-8
- Code-128
- Code-39
- QR Code
- Data Matrix

**Configuration**:
```typescript
const config = {
  fps: 10,
  qrbox: { width: 250, height: 250 },
  aspectRatio: 1.0,
  formatsToSupport: [
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.QR_CODE
  ]
}
```

---

## Error Handling

### Camera Permission Denied
```
"يرجى السماح بالوصول إلى الكاميرا لمسح الباركود"
"يمكنك إدخال الباركود يدوياً بدلاً من ذلك"
```

### No Camera Available
```
"لا توجد كاميرا متاحة على هذا الجهاز"
"يمكنك إدخال الباركود يدوياً"
```

### Product Not Found
```
"لم يتم العثور على منتج بهذا الباركود"
"تأكد من إضافة المنتج إلى النظام أولاً"
```

### Invalid Barcode Format
```
"تنسيق الباركود غير صحيح"
"تأكد من مسح الباركود بشكل واضح"
```

---

## UI/UX Considerations

### Visual Feedback
- ✅ Green border flash on successful scan
- ❌ Red border flash on error
- 🔊 Beep sound on success
- 📹 Camera preview with target box
- ⏳ Loading spinner while initializing

### Mobile Optimization
- Full-screen camera preview on mobile
- Large touch targets for buttons
- Responsive dialog sizing
- Automatic camera selection (back camera preferred)

### Desktop Experience
- Webcam selector if multiple cameras
- Smaller dialog (max-w-2xl)
- Keyboard shortcuts (Enter to submit manual input, Esc to close)

### Accessibility
- ARIA labels for screen readers
- Keyboard navigation support
- High contrast mode support
- Error messages announced

---

## Testing Checklist

### Functional Tests
- [ ] Camera initializes correctly
- [ ] Barcode scanning works (EAN-13, Code-128)
- [ ] QR code scanning works
- [ ] Manual input fallback works
- [ ] Product lookup successful
- [ ] Product added to sale items
- [ ] Duplicate product increments quantity
- [ ] Error messages display correctly
- [ ] Close button works
- [ ] Dialog closes after successful scan

### Permission Tests
- [ ] Camera permission prompt appears
- [ ] Permission denied handled gracefully
- [ ] Permission granted starts camera
- [ ] Browser compatibility (Chrome, Firefox, Safari, Edge)

### Mobile Tests
- [ ] Works on Android Chrome
- [ ] Works on iOS Safari
- [ ] Back camera used by default
- [ ] Touch interface responsive
- [ ] Full-screen mode works

### Desktop Tests
- [ ] Webcam detection works
- [ ] Multiple camera selection (if applicable)
- [ ] Keyboard shortcuts work
- [ ] Dialog responsive on different screen sizes

### Error Tests
- [ ] No camera available error
- [ ] Permission denied error
- [ ] Invalid barcode error
- [ ] Product not found error
- [ ] Network error handling

---

## Performance Considerations

### Optimization
- Lazy load scanner component
- Stop camera when dialog closed
- Debounce scan results (prevent duplicate scans)
- Optimize camera resolution (balance quality vs performance)
- Memory cleanup on unmount

### Bundle Size
- html5-qrcode: ~50KB gzipped
- Tree-shake unused formats
- Consider code-splitting for scanner component

---

## Security & Privacy

### Camera Access
- Request permission only when needed
- Clear explanation of why camera needed
- No video recording/storage
- Camera stopped immediately after scan

### Data Handling
- Barcode data never sent to external servers
- Only used for local product lookup
- No analytics tracking of scanned items

---

## Future Enhancements (Phase 4)

1. **Batch Scanning**: Scan multiple products in quick succession
2. **Scan History**: Show recently scanned barcodes
3. **Custom Barcode Generation**: Generate and print barcodes for products
4. **Barcode Printing**: Print labels for inventory items
5. **Advanced Camera Controls**: Zoom, focus, torch (flashlight)
6. **Offline Support**: Cache product data for offline scanning
7. **Sound Preferences**: Enable/disable beep sound in settings
8. **Vibration Feedback**: Haptic feedback on mobile devices

---

## Implementation Timeline

**Total Estimated Time: 2-3 hours**

- **Step 1**: Install dependencies (5 mins)
- **Step 2**: Create BarcodeScanner component (1 hour)
- **Step 3**: Integrate into SalesPage (30 mins)
- **Step 4**: Integrate into TransfersPage (30 mins)
- **Step 5**: Testing and refinement (30-60 mins)

---

## Success Criteria

✅ Barcode scanner opens from Sales and Transfer pages  
✅ Camera initializes and shows preview  
✅ Successfully scans EAN-13 and Code-128 barcodes  
✅ Product lookup works correctly  
✅ Product added to sale/transfer items automatically  
✅ Manual input fallback works  
✅ Error handling for all edge cases  
✅ Works on mobile and desktop  
✅ Arabic UI and messages  
✅ Good performance (< 1s scan time)  
✅ No memory leaks  

---

**Let's implement this! 🚀**
