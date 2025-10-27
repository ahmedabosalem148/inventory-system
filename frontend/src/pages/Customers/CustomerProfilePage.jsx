import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Sidebar, Navbar } from '../../components/organisms';
import { Card, Button, Badge } from '../../components/atoms';
import { 
  ArrowRight, 
  User, 
  Phone, 
  MapPin, 
  CreditCard, 
  History, 
  Receipt, 
  DollarSign,
  TrendingUp,
  TrendingDown
} from 'lucide-react';
import apiClient from '../../utils/axios';

const CustomerProfilePage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [customer, setCustomer] = useState(null);
  const [ledgerEntries, setLedgerEntries] = useState([]);
  const [vouchers, setVouchers] = useState([]);
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [filterLoading, setFilterLoading] = useState(false);

  useEffect(() => {
    fetchCustomerProfile();
  }, [id]);

  const fetchCustomerProfile = async () => {
    setLoading(true);
    try {
      // Get customer details with ledger entries
      const customerResponse = await apiClient.get(`/customers/${id}`);
      const customerData = customerResponse.data.data;
      
      setCustomer(customerData);
      setLedgerEntries(customerData.ledger_entries || []);

      // Get customer vouchers
      const vouchersResponse = await apiClient.get('/issue-vouchers', {
        params: { customer_id: id }
      });
      setVouchers(vouchersResponse.data.data || []);

      // Get customer payments
      const paymentsResponse = await apiClient.get('/payments', {
        params: { customer_id: id }
      });
      setPayments(paymentsResponse.data.data || []);

    } catch (error) {
      console.error('Failed to fetch customer profile:', error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch ledger entries with date filter
  const fetchLedgerEntries = async (filters = {}) => {
    setFilterLoading(true);
    try {
      const response = await apiClient.get(`/customers/${id}`, {
        params: {
          from_date: filters.from_date,
          to_date: filters.to_date
        }
      });
      
      setLedgerEntries(response.data.data.ledger_entries || []);
    } catch (error) {
      console.error('Failed to fetch ledger entries:', error);
      alert('فشل في تحميل الحركات المالية');
    } finally {
      setFilterLoading(false);
    }
  };

  // Handle filter button click
  const handleFilter = () => {
    if (!fromDate && !toDate) {
      alert('الرجاء اختيار تاريخ من أو إلى');
      return;
    }
    fetchLedgerEntries({ from_date: fromDate, to_date: toDate });
  };

  // Handle reset button click
  const handleReset = () => {
    setFromDate('');
    setToDate('');
    fetchLedgerEntries(); // Reload all entries
  };

  // Handle PDF export
  const handleExportPDF = async () => {
    try {
      const response = await apiClient.get(`/customers/${id}/statement/pdf`, {
        params: {
          from_date: fromDate,
          to_date: toDate
        },
        responseType: 'blob'
      });
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `customer-${id}-statement.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Failed to export PDF:', error);
      alert('فشل في تصدير PDF. تأكد من وجود الـ API في Backend');
    }
  };

  // Handle Excel export
  const handleExportExcel = async () => {
    try {
      const response = await apiClient.get(`/customers/${id}/statement/excel`, {
        params: {
          from_date: fromDate,
          to_date: toDate
        },
        responseType: 'blob'
      });
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `customer-${id}-statement.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Failed to export Excel:', error);
      alert('فشل في تصدير Excel. تأكد من وجود الـ API في Backend');
    }
  };

  const getBalanceStatus = (balance) => {
    if (balance > 0) return { text: 'له', color: 'bg-green-100 text-green-800', icon: TrendingUp };
    if (balance < 0) return { text: 'عليه', color: 'bg-red-100 text-red-800', icon: TrendingDown };
    return { text: 'متساوي', color: 'bg-gray-100 text-gray-800', icon: DollarSign };
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  if (loading) {
    return (
      <div className="flex h-screen">
        <Sidebar />
        <div className="flex-1 flex flex-col">
          <Navbar />
          <main className="flex-1 p-6">
            <div className="flex items-center justify-center h-64">
              <div className="text-gray-500">جاري التحميل...</div>
            </div>
          </main>
        </div>
      </div>
    );
  }

  if (!customer) {
    return (
      <div className="flex h-screen">
        <Sidebar />
        <div className="flex-1 flex flex-col">
          <Navbar />
          <main className="flex-1 p-6">
            <div className="flex items-center justify-center h-64">
              <div className="text-red-500">العميل غير موجود</div>
            </div>
          </main>
        </div>
      </div>
    );
  }

  const balanceStatus = getBalanceStatus(customer.balance);
  const BalanceIcon = balanceStatus.icon;

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar />
      <Navbar />
      <main className="pt-16 lg:mr-64 p-4 md:p-6 min-h-screen">
          {/* Header */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4 md:mb-6 gap-4">
            <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/customers')}
                className="flex items-center space-x-2 space-x-reverse w-fit"
              >
                <ArrowRight className="w-4 h-4" />
                <span className="hidden sm:inline">العودة للعملاء</span>
                <span className="sm:hidden">العودة</span>
              </Button>
              <div>
                <h1 className="text-xl md:text-2xl font-bold text-gray-900">
                  ملف العميل
                </h1>
                <p className="text-sm md:text-base text-gray-600">{customer.code}</p>
              </div>
            </div>
          </div>

          {/* Customer Info Card */}
          <Card className="mb-4 md:mb-6">
            <div className="p-4 md:p-6">
              <div className="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div className="flex items-start space-x-3 space-x-reverse">
                  <div className="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <User className="w-5 h-5 md:w-6 md:h-6 text-blue-600" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <h2 className="text-lg md:text-xl font-semibold text-gray-900 truncate">{customer.name}</h2>
                    <div className="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-4 sm:space-x-reverse mt-2 text-sm text-gray-600">
                      {customer.phone && (
                        <div className="flex items-center space-x-1 space-x-reverse">
                          <Phone className="w-4 h-4 flex-shrink-0" />
                          <span className="truncate">{customer.phone}</span>
                        </div>
                      )}
                      {customer.address && (
                        <div className="flex items-center space-x-1 space-x-reverse">
                          <MapPin className="w-4 h-4 flex-shrink-0" />
                          <span className="truncate" title={customer.address}>{customer.address}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
                
                <div className="flex flex-col items-end space-y-3">
                  <div className="flex flex-wrap items-center gap-2 justify-end">
                    <Badge 
                      variant={customer.is_active ? 'success' : 'secondary'}
                      className="px-2 py-1 text-xs"
                    >
                      {customer.is_active ? 'نشط' : 'غير نشط'}
                    </Badge>
                    <Badge 
                      variant={customer.type === 'wholesale' ? 'info' : 'outline'}
                      className="px-2 py-1 text-xs"
                    >
                      {customer.type === 'wholesale' ? 'جملة' : 'قطاعي'}
                    </Badge>
                  </div>
                  
                  {/* Current Balance */}
                  <div className={`inline-flex items-center space-x-2 space-x-reverse px-3 py-2 rounded-lg ${balanceStatus.color} text-sm`}>
                    <BalanceIcon className="w-4 h-4" />
                    <span className="font-semibold whitespace-nowrap">
                      {formatCurrency(Math.abs(customer.balance))} {balanceStatus.text}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          {/* Tabs */}
          <div className="bg-white border-b border-gray-200 mb-4 md:mb-6 rounded-lg shadow-sm">
            <nav className="flex overflow-x-auto px-4 md:px-6">
              {[
                { id: 'overview', label: 'نظرة عامة', icon: User },
                { id: 'transactions', label: 'الحركات المالية', icon: History },
                { id: 'vouchers', label: 'الفواتير', icon: Receipt },
                { id: 'payments', label: 'المدفوعات', icon: CreditCard },
              ].map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex items-center space-x-2 space-x-reverse py-3 md:py-4 px-2 md:px-1 border-b-2 font-medium text-xs md:text-sm whitespace-nowrap ${
                      activeTab === tab.id
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <Icon className="w-4 h-4 flex-shrink-0" />
                    <span className="hidden sm:inline">{tab.label}</span>
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Tab Content */}
          <div className="space-y-6">
            {activeTab === 'overview' && (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Stats Cards */}
                <Card>
                  <div className="p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <Receipt className="h-8 w-8 text-blue-600" />
                      </div>
                      <div className="mr-4">
                        <div className="text-sm font-medium text-gray-500">إجمالي الفواتير</div>
                        <div className="text-2xl font-semibold text-gray-900">{vouchers.length}</div>
                      </div>
                    </div>
                  </div>
                </Card>

                <Card>
                  <div className="p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <CreditCard className="h-8 w-8 text-green-600" />
                      </div>
                      <div className="mr-4">
                        <div className="text-sm font-medium text-gray-500">إجمالي المدفوعات</div>
                        <div className="text-2xl font-semibold text-gray-900">{payments.length}</div>
                      </div>
                    </div>
                  </div>
                </Card>

                <Card>
                  <div className="p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <History className="h-8 w-8 text-purple-600" />
                      </div>
                      <div className="mr-4">
                        <div className="text-sm font-medium text-gray-500">الحركات المالية</div>
                        <div className="text-2xl font-semibold text-gray-900">{ledgerEntries.length}</div>
                      </div>
                    </div>
                  </div>
                </Card>

                <Card>
                  <div className="p-6">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <DollarSign className="h-8 w-8 text-yellow-600" />
                      </div>
                      <div className="mr-4">
                        <div className="text-sm font-medium text-gray-500">حد الائتمان</div>
                        <div className="text-2xl font-semibold text-gray-900">
                          {formatCurrency(customer.credit_limit)}
                        </div>
                      </div>
                    </div>
                  </div>
                </Card>
              </div>
            )}

            {activeTab === 'transactions' && (
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4">الحركات المالية</h3>
                  {/* Date Filters & Export Buttons */}
                  <div className="flex flex-col md:flex-row md:items-center gap-2 mb-4">
                    <div className="flex gap-2">
                      <input
                        type="date"
                        className="border rounded px-2 py-1 text-sm"
                        value={fromDate || ''}
                        onChange={e => setFromDate(e.target.value)}
                        placeholder="من تاريخ"
                        aria-label="من تاريخ"
                        disabled={filterLoading}
                      />
                      <input
                        type="date"
                        className="border rounded px-2 py-1 text-sm"
                        value={toDate || ''}
                        onChange={e => setToDate(e.target.value)}
                        placeholder="إلى تاريخ"
                        aria-label="إلى تاريخ"
                        disabled={filterLoading}
                      />
                      <Button 
                        size="sm" 
                        onClick={handleFilter}
                        disabled={filterLoading}
                      >
                        {filterLoading ? 'جاري...' : 'فلترة'}
                      </Button>
                      <Button 
                        size="sm" 
                        variant="outline" 
                        onClick={handleReset}
                        disabled={filterLoading}
                      >
                        إعادة
                      </Button>
                    </div>
                    <div className="flex gap-2 md:ml-auto">
                      <Button size="sm" variant="outline" onClick={handleExportPDF}>
                        تصدير PDF
                      </Button>
                      <Button size="sm" variant="outline" onClick={handleExportExcel}>
                        تصدير Excel
                      </Button>
                    </div>
                  </div>
                  {ledgerEntries.length > 0 ? (
                    <div className="overflow-x-auto -mx-4 sm:mx-0">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                              التاريخ
                            </th>
                            <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                              الوصف
                            </th>
                            <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                              مدين
                            </th>
                            <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                              دائن
                            </th>
                            <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                              الرصيد
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {ledgerEntries.map((entry, index) => (
                            <tr key={index} className="hover:bg-gray-50">
                              <td className="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-900">
                                <div className="whitespace-nowrap">
                                  {formatDate(entry.created_at)}
                                </div>
                              </td>
                              <td className="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-900">
                                <div className="max-w-xs truncate" title={entry.description}>
                                  {entry.description}
                                </div>
                              </td>
                              <td className="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                {entry.debit_amount ? formatCurrency(entry.debit_amount) : '-'}
                              </td>
                              <td className="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                {entry.credit_amount ? formatCurrency(entry.credit_amount) : '-'}
                              </td>
                              <td className="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">
                                {formatCurrency(entry.running_balance)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500">
                      لا توجد حركات مالية
                    </div>
                  )}
                </div>
              </Card>
            )}

            {activeTab === 'vouchers' && (
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4">فواتير العميل</h3>
                  {vouchers.length > 0 ? (
                    <div className="space-y-4">
                      {vouchers.map((voucher) => (
                        <div key={voucher.id} className="border rounded-lg p-4 hover:bg-gray-50">
                          <div className="flex justify-between items-start">
                            <div>
                              <h4 className="font-medium text-gray-900">فاتورة رقم: {voucher.voucher_number}</h4>
                              <p className="text-sm text-gray-600 mt-1">
                                تاريخ الإصدار: {formatDate(voucher.issue_date)}
                              </p>
                            </div>
                            <div className="text-left">
                              <div className="text-lg font-semibold text-gray-900">
                                {formatCurrency(voucher.total_amount)}
                              </div>
                              <Badge 
                                variant={voucher.status === 'completed' ? 'success' : 'warning'}
                                className="mt-1"
                              >
                                {voucher.status === 'completed' ? 'مكتملة' : 'معلقة'}
                              </Badge>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500">
                      لا توجد فواتير
                    </div>
                  )}
                </div>
              </Card>
            )}

            {activeTab === 'payments' && (
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4">مدفوعات العميل</h3>
                  {payments.length > 0 ? (
                    <div className="space-y-4">
                      {payments.map((payment) => (
                        <div key={payment.id} className="border rounded-lg p-4 hover:bg-gray-50">
                          <div className="flex justify-between items-start">
                            <div>
                              <h4 className="font-medium text-gray-900">دفعة رقم: {payment.id}</h4>
                              <p className="text-sm text-gray-600 mt-1">
                                التاريخ: {formatDate(payment.payment_date)}
                              </p>
                              <p className="text-sm text-gray-600">
                                طريقة الدفع: {payment.payment_method === 'cash' ? 'نقدي' : 'تحويل'}
                              </p>
                            </div>
                            <div className="text-left">
                              <div className="text-lg font-semibold text-green-600">
                                {formatCurrency(payment.amount)}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500">
                      لا توجد مدفوعات
                    </div>
                  )}
                </div>
              </Card>
            )}
          </div>
        </main>
    </div>
  );
};

export default CustomerProfilePage;