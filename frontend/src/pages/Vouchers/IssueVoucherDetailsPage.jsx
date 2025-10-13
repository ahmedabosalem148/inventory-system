import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Sidebar, Navbar } from '../../components/organisms';
import { Card, Button, Badge } from '../../components/atoms';
import { 
  ArrowRight, 
  FileText, 
  User, 
  Calendar, 
  Package, 
  DollarSign,
  Printer,
  CreditCard,
  History,
  ExternalLink
} from 'lucide-react';
import apiClient from '../../utils/axios';

const IssueVoucherDetailsPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [voucher, setVoucher] = useState(null);
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchVoucherDetails();
  }, [id]);

  const fetchVoucherDetails = async () => {
    setLoading(true);
    try {
      // Get voucher details
      const voucherResponse = await apiClient.get(`/issue-vouchers/${id}`);
      const voucherData = voucherResponse.data.data || voucherResponse.data;
      setVoucher(voucherData);

      // Get related payments
      if (voucherData.customer) {
        const paymentsResponse = await apiClient.get('/payments', {
          params: { 
            customer_id: voucherData.customer.id,
            voucher_type: 'issue',
            voucher_id: id
          }
        });
        setPayments(paymentsResponse.data.data || []);
      }

    } catch (error) {
      console.error('Failed to fetch voucher details:', error);
    } finally {
      setLoading(false);
    }
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

  const getStatusBadge = (status) => {
    const statusMap = {
      draft: { text: 'مسودة', variant: 'secondary' },
      pending: { text: 'معلق', variant: 'warning' },
      completed: { text: 'مكتمل', variant: 'success' },
      cancelled: { text: 'ملغي', variant: 'danger' }
    };
    
    const config = statusMap[status] || { text: status, variant: 'secondary' };
    return <Badge variant={config.variant}>{config.text}</Badge>;
  };

  const calculateTotalPaid = () => {
    return payments.reduce((sum, payment) => sum + parseFloat(payment.amount || 0), 0);
  };

  const calculateRemaining = () => {
    if (!voucher) return 0;
    return parseFloat(voucher.total_amount || 0) - calculateTotalPaid();
  };

  const handlePrint = async () => {
    try {
      const response = await apiClient.post(`/issue-vouchers/${id}/print`);
      // Handle print response - could open PDF or trigger print dialog
      console.log('Print response:', response.data);
    } catch (error) {
      console.error('Print failed:', error);
    }
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

  if (!voucher) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Sidebar />
        <Navbar />
        <main className="pt-16 lg:mr-64 p-6 min-h-screen">
          <div className="flex items-center justify-center h-64">
            <div className="text-red-500">الفاتورة غير موجودة</div>
          </div>
        </main>
      </div>
    );
  }

  const totalPaid = calculateTotalPaid();
  const remaining = calculateRemaining();

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar />
      <Navbar />
      <main className="pt-16 lg:mr-64 p-4 md:p-6 min-h-screen">
          {/* Header */}
          <div className="flex flex-col lg:flex-row lg:items-center justify-between mb-4 md:mb-6 gap-4">
            <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/issue-vouchers')}
                className="flex items-center space-x-2 space-x-reverse w-fit"
              >
                <ArrowRight className="w-4 h-4" />
                <span className="hidden sm:inline">العودة للفواتير</span>
                <span className="sm:hidden">العودة</span>
              </Button>
              <div>
                <h1 className="text-lg md:text-2xl font-bold text-gray-900">
                  تفاصيل فاتورة الصرف
                </h1>
                <p className="text-sm md:text-base text-gray-600">{voucher.voucher_number}</p>
              </div>
            </div>
            
            <div className="flex flex-wrap gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={handlePrint}
                className="flex items-center space-x-2 space-x-reverse"
              >
                <Printer className="w-4 h-4" />
                <span className="hidden sm:inline">طباعة</span>
              </Button>
              {voucher.customer && (
                <Button
                  variant="primary"
                  size="sm"
                  onClick={() => navigate(`/customers/${voucher.customer.id}/profile`)}
                  className="flex items-center space-x-2 space-x-reverse"
                >
                  <ExternalLink className="w-4 h-4" />
                  <span className="hidden sm:inline">ملف العميل</span>
                  <span className="sm:hidden">العميل</span>
                </Button>
              )}
            </div>
          </div>

          <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6">
            {/* Main Content */}
            <div className="xl:col-span-2 space-y-4 md:space-y-6 order-2 xl:order-1">
              {/* Voucher Header */}
              <Card>
                <div className="p-6">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3 space-x-reverse">
                      <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <FileText className="w-5 h-5 text-blue-600" />
                      </div>
                      <div>
                        <h2 className="text-xl font-semibold">{voucher.voucher_number}</h2>
                        <p className="text-gray-600">فاتورة صرف</p>
                      </div>
                    </div>
                    {getStatusBadge(voucher.status)}
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div className="space-y-2">
                      <div className="flex items-center space-x-2 space-x-reverse">
                        <Calendar className="w-4 h-4 text-gray-400" />
                        <span className="text-gray-600">تاريخ الإصدار:</span>
                        <span className="font-medium">{formatDate(voucher.issue_date)}</span>
                      </div>
                      
                      {voucher.customer && (
                        <div className="flex items-center space-x-2 space-x-reverse">
                          <User className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-600">العميل:</span>
                          <span className="font-medium">{voucher.customer.name}</span>
                        </div>
                      )}
                    </div>

                    <div className="space-y-2">
                      {voucher.branch && (
                        <div className="flex items-center space-x-2 space-x-reverse">
                          <Package className="w-4 h-4 text-gray-400" />
                          <span className="text-gray-600">المخزن:</span>
                          <span className="font-medium">{voucher.branch.name}</span>
                        </div>
                      )}

                      {voucher.notes && (
                        <div className="flex items-start space-x-2 space-x-reverse">
                          <FileText className="w-4 h-4 text-gray-400 mt-0.5" />
                          <span className="text-gray-600">ملاحظات:</span>
                          <span className="font-medium">{voucher.notes}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </Card>

              {/* Items Table */}
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4 flex items-center space-x-2 space-x-reverse">
                    <Package className="w-5 h-5" />
                    <span>الأصناف</span>
                  </h3>
                  
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            الصنف
                          </th>
                          <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            الكمية
                          </th>
                          <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            السعر
                          </th>
                          <th className="px-3 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            الإجمالي
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {voucher.items && voucher.items.map((item, index) => (
                          <tr key={index} className="hover:bg-gray-50">
                            <td className="px-3 md:px-6 py-4">
                              <div>
                                <div className="text-xs md:text-sm font-medium text-gray-900">
                                  <div className="max-w-xs truncate" title={item.product?.name}>
                                    {item.product?.name || 'منتج غير معرف'}
                                  </div>
                                </div>
                                <div className="text-xs text-gray-500">
                                  {item.product?.code || ''}
                                </div>
                              </div>
                            </td>
                            <td className="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-900 whitespace-nowrap">
                              {item.quantity} {item.product?.unit || ''}
                            </td>
                            <td className="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-900 whitespace-nowrap">
                              {formatCurrency(item.unit_price)}
                            </td>
                            <td className="px-3 md:px-6 py-4 text-xs md:text-sm font-medium text-gray-900 whitespace-nowrap">
                              {formatCurrency(item.total_price)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              </Card>
            </div>

            {/* Sidebar */}
            <div className="space-y-4 md:space-y-6 order-1 xl:order-2">
              {/* Financial Summary */}
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4 flex items-center space-x-2 space-x-reverse">
                    <DollarSign className="w-5 h-5" />
                    <span>الملخص المالي</span>
                  </h3>
                  
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-sm md:text-base text-gray-600">المجموع الفرعي:</span>
                      <span className="font-medium text-sm md:text-base">{formatCurrency(voucher.subtotal || 0)}</span>
                    </div>
                    
                    {voucher.discount_amount > 0 && (
                      <div className="flex justify-between items-center">
                        <span className="text-sm md:text-base text-gray-600">الخصم:</span>
                        <span className="font-medium text-red-600 text-sm md:text-base">
                          -{formatCurrency(voucher.discount_amount)}
                        </span>
                      </div>
                    )}
                    
                    {voucher.tax_amount > 0 && (
                      <div className="flex justify-between items-center">
                        <span className="text-sm md:text-base text-gray-600">الضريبة:</span>
                        <span className="font-medium text-sm md:text-base">{formatCurrency(voucher.tax_amount)}</span>
                      </div>
                    )}
                    
                    <div className="border-t pt-3">
                      <div className="flex justify-between items-center text-base md:text-lg font-semibold">
                        <span>الإجمالي:</span>
                        <span>{formatCurrency(voucher.total_amount)}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>

              {/* Payment Summary */}
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-semibold mb-4 flex items-center space-x-2 space-x-reverse">
                    <CreditCard className="w-5 h-5" />
                    <span>حالة السداد</span>
                  </h3>
                  
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-sm md:text-base text-gray-600">المبلغ الإجمالي:</span>
                      <span className="font-medium text-sm md:text-base">{formatCurrency(voucher.total_amount)}</span>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm md:text-base text-gray-600">المدفوع:</span>
                      <span className="font-medium text-green-600 text-sm md:text-base">{formatCurrency(totalPaid)}</span>
                    </div>
                    
                    <div className="border-t pt-3">
                      <div className="flex justify-between items-center text-base md:text-lg font-semibold">
                        <span>المتبقي:</span>
                        <span className={remaining > 0 ? 'text-red-600' : 'text-green-600'}>
                          {formatCurrency(remaining)}
                        </span>
                      </div>
                    </div>

                    {remaining === 0 && (
                      <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div className="flex items-center space-x-2 space-x-reverse text-green-700">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                          </svg>
                          <span className="text-sm font-medium">تم السداد بالكامل</span>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </Card>

              {/* Payment History */}
              {payments.length > 0 && (
                <Card>
                  <div className="p-6">
                    <h3 className="text-lg font-semibold mb-4 flex items-center space-x-2 space-x-reverse">
                      <History className="w-5 h-5" />
                      <span>تاريخ المدفوعات</span>
                    </h3>
                    
                    <div className="space-y-3">
                      {payments.map((payment) => (
                        <div key={payment.id} className="border rounded-lg p-3">
                          <div className="flex justify-between items-start">
                            <div>
                              <div className="text-sm font-medium">
                                {formatCurrency(payment.amount)}
                              </div>
                              <div className="text-xs text-gray-500">
                                {formatDate(payment.payment_date)}
                              </div>
                              <div className="text-xs text-gray-500">
                                {payment.payment_method === 'cash' ? 'نقدي' : 'تحويل'}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </Card>
              )}
            </div>
          </div>
        </main>
    </div>
  );
};

export default IssueVoucherDetailsPage;