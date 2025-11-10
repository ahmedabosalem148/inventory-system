/**
 * Customer Search Select Component
 * Autocomplete dropdown for searching and selecting customers
 */

import { useState, useEffect, useRef } from 'react'
import { Search, User } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import apiClient from '@/services/api/client'

interface Customer {
  id: number
  name: string
  code: string
  phone?: string
  balance?: number
}

interface CustomerSearchSelectProps {
  value: number | null
  onChange: (customerId: number | null, customer: Customer | null) => void
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  error?: string
}

export const CustomerSearchSelect = ({
  value,
  onChange,
  label = 'العميل',
  placeholder = 'ابحث بالاسم أو الكود...',
  required = false,
  disabled = false,
  error
}: CustomerSearchSelectProps) => {
  const [searchTerm, setSearchTerm] = useState('')
  const [customers, setCustomers] = useState<Customer[]>([])
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null)
  const [isOpen, setIsOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const dropdownRef = useRef<HTMLDivElement>(null)

  /**
   * Load customers based on search term
   */
  useEffect(() => {
    const searchCustomers = async () => {
      if (searchTerm.length < 2) {
        setCustomers([])
        return
      }

      setLoading(true)
      try {
        const response = await apiClient.get('/customers', {
          params: {
            search: searchTerm,
            per_page: 10,
            is_active: true
          }
        })
        setCustomers(response.data.data || [])
        setIsOpen(true)
      } catch (error) {
        console.error('Error searching customers:', error)
        setCustomers([])
      } finally {
        setLoading(false)
      }
    }

    const debounceTimer = setTimeout(searchCustomers, 300)
    return () => clearTimeout(debounceTimer)
  }, [searchTerm])

  /**
   * Load selected customer by ID
   */
  useEffect(() => {
    const loadCustomer = async () => {
      if (!value) {
        setSelectedCustomer(null)
        setSearchTerm('')
        return
      }

      try {
        const response = await apiClient.get(`/customers/${value}`)
        const customer = response.data.data
        setSelectedCustomer(customer)
        setSearchTerm(customer.name)
      } catch (error) {
        console.error('Error loading customer:', error)
      }
    }

    loadCustomer()
  }, [value])

  /**
   * Handle customer selection
   */
  const handleSelect = (customer: Customer) => {
    setSelectedCustomer(customer)
    setSearchTerm(customer.name)
    setIsOpen(false)
    onChange(customer.id, customer)
  }

  /**
   * Handle clear selection
   */
  const handleClear = () => {
    setSelectedCustomer(null)
    setSearchTerm('')
    onChange(null, null)
    setIsOpen(false)
  }

  /**
   * Close dropdown when clicking outside
   */
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="relative" ref={dropdownRef}>
      {label && (
        <Label>
          {label}
          {required && <span className="text-red-500 mr-1">*</span>}
        </Label>
      )}
      
      <div className="relative">
        {/* Search Input */}
        <div className="relative">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <Input
            type="text"
            value={searchTerm}
            onChange={(e) => {
              setSearchTerm(e.target.value)
              if (!e.target.value) {
                handleClear()
              }
            }}
            onFocus={() => {
              if (customers.length > 0) setIsOpen(true)
            }}
            placeholder={placeholder}
            disabled={disabled}
            className={`pr-10 ${error ? 'border-red-500' : ''}`}
          />
          {selectedCustomer && (
            <button
              type="button"
              onClick={handleClear}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              ✕
            </button>
          )}
        </div>

        {/* Selected Customer Info */}
        {selectedCustomer && !isOpen && (
          <div className="mt-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-gray-400" />
                <div>
                  <p className="text-sm font-medium">{selectedCustomer.name}</p>
                  <p className="text-xs text-gray-500">
                    {selectedCustomer.code}
                    {selectedCustomer.phone && ` • ${selectedCustomer.phone}`}
                  </p>
                </div>
              </div>
              {selectedCustomer.balance !== undefined && (
                <div className="text-left">
                  <p className="text-xs text-gray-500">الرصيد</p>
                  <p className={`text-sm font-medium ${
                    selectedCustomer.balance > 0 ? 'text-red-600' : 
                    selectedCustomer.balance < 0 ? 'text-green-600' : 
                    'text-gray-600'
                  }`}>
                    {Math.abs(selectedCustomer.balance)} ج.م
                    {selectedCustomer.balance > 0 && ' (عليه)'}
                    {selectedCustomer.balance < 0 && ' (له)'}
                  </p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Dropdown Results */}
        {isOpen && customers.length > 0 && (
          <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
            {loading && (
              <div className="p-3 text-center text-gray-500 text-sm">
                جاري البحث...
              </div>
            )}
            
            {!loading && customers.length === 0 && searchTerm.length >= 2 && (
              <div className="p-3 text-center text-gray-500 text-sm">
                لا توجد نتائج
              </div>
            )}

            {!loading && customers.map((customer) => (
              <button
                key={customer.id}
                type="button"
                onClick={() => handleSelect(customer)}
                className="w-full text-right p-3 hover:bg-gray-50 border-b border-gray-100 last:border-0 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-sm">{customer.name}</p>
                    <p className="text-xs text-gray-500">
                      {customer.code}
                      {customer.phone && ` • ${customer.phone}`}
                    </p>
                  </div>
                  {customer.balance !== undefined && (
                    <div className="text-left">
                      <p className={`text-xs font-medium ${
                        customer.balance > 0 ? 'text-red-600' : 
                        customer.balance < 0 ? 'text-green-600' : 
                        'text-gray-600'
                      }`}>
                        {Math.abs(customer.balance)} ج.م
                      </p>
                    </div>
                  )}
                </div>
              </button>
            ))}
          </div>
        )}

        {/* No Results Message */}
        {isOpen && !loading && customers.length === 0 && searchTerm.length >= 2 && (
          <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-center text-gray-500 text-sm">
            لم يتم العثور على عملاء بهذا الاسم
          </div>
        )}

        {/* Search Hint */}
        {!searchTerm && !selectedCustomer && (
          <p className="text-xs text-gray-500 mt-1">
            اكتب حرفين على الأقل للبحث
          </p>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <p className="text-xs text-red-500 mt-1">{error}</p>
      )}
    </div>
  )
}
