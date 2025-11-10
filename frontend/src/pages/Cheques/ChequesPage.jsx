import { useState, useEffect } from 'react';
import { 
  Plus, 
  Search, 
  Filter, 
  FileCheck, 
  Clock, 
  AlertCircle, 
  CheckCircle,
  XCircle,
  Calendar,
  CreditCard,
  User,
  DollarSign
} from 'lucide-react';
import chequeService from '../../services/chequeService';
import { toast } from 'react-hot-toast';

const ChequesPage = () => {
  const [cheques, setCheques] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all'); // all, pending, overdue, cleared, bounced
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0
  });
  const [stats, setStats] = useState({
    pending: 0,
    overdue: 0,
    cleared: 0,
    returned: 0,
    total_amount: 0
  });
  const [selectedCheque, setSelectedCheque] = useState(null);
  const [showClearDialog, setShowClearDialog] = useState(false);
  const [showBounceDialog, setShowBounceDialog] = useState(false);

  useEffect(() => {
    loadCheques();
    loadStats();
  }, [statusFilter, pagination.current_page]);

  const loadCheques = async () => {
    try {
      setLoading(true);
      let response;
      
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        search: searchTerm
      };

      switch (statusFilter) {
        case 'pending':
          response = await chequeService.getPendingCheques(params);
          break;
        case 'overdue':
          response = await chequeService.getOverdueCheques(params);
          break;
        case 'cleared':
          response = await chequeService.getClearedCheques(params);
          break;
        default:
          response = await chequeService.getAllCheques(params);
      }

      setCheques(response.data || []);
      setPagination({
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 20,
        total: response.total || 0
      });
    } catch (error) {
      console.error('Error loading cheques:', error);
      toast.error('فشل تحميل الشيكات');
    } finally {
      setLoading(false);
    }
  };

  const loadStats = async () => {
    try {
      const response = await chequeService.getChequeStats();
      setStats(response);
    } catch (error) {
      console.error('Error loading stats:', error);
    }
  };

  const handleClearCheque = async (cheque) => {
    setSelectedCheque(cheque);
    setShowClearDialog(true);
  };

  const handleBounceCheque = async (cheque) => {
    setSelectedCheque(cheque);
    setShowBounceDialog(true);
  };

  const confirmClearCheque = async () => {
    try {
      await chequeService.clearCheque(selectedCheque.id, {
        cleared_at: new Date().toISOString().split('T')[0]
      });
      toast.success('تم صرف الشيك بنجاح');
      setShowClearDialog(false);
      loadCheques();
      loadStats();
    } catch (error) {
      console.error('Error clearing cheque:', error);
      toast.error('فشل صرف الشيك');
    }
  };

  const confirmBounceCheque = async (reason) => {
    try {
      await chequeService.bounceCheque(selectedCheque.id, {
        return_reason: reason
      });
      toast.success('تم إرجاع الشيك بنجاح');
      setShowBounceDialog(false);
      loadCheques();
      loadStats();
    } catch (error) {
      console.error('Error bouncing cheque:', error);
      toast.error('فشل إرجاع الشيك');
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      PENDING: { 
        label: 'معلق', 
        className: 'bg-yellow-100 text-yellow-800',
        icon: Clock 
      },
      CLEARED: { 
        label: 'مصرف', 
        className: 'bg-green-100 text-green-800',
        icon: CheckCircle 
      },
      RETURNED: { 
        label: 'مرتد', 
        className: 'bg-red-100 text-red-800',
        icon: XCircle 
      }
    };

    const config = statusConfig[status] || statusConfig.PENDING;
    const Icon = config.icon;

    return (
      <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${config.className}`}>
        <Icon className="w-3 h-3" />
        {config.label}
      </span>
    );
  };

  const isOverdue = (dueDate, status) => {
    if (status !== 'PENDING') return false;
    return new Date(dueDate) < new Date();
  };

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">الشيكات</h1>
            <p className="text-gray-600 mt-1">إدارة الشيكات وحالاتها</p>
          </div>
          <button
            onClick={() => {/* TODO: Add cheque dialog */}}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus className="w-5 h-5" />
            <span>إضافة شيك</span>
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">شيكات معلقة</p>
              <p className="text-2xl font-bold text-yellow-600">{stats.pending || 0}</p>
            </div>
            <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <Clock className="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">شيكات متأخرة</p>
              <p className="text-2xl font-bold text-red-600">{stats.overdue || 0}</p>
            </div>
            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-red-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">شيكات مصرفة</p>
              <p className="text-2xl font-bold text-green-600">{stats.cleared || 0}</p>
            </div>
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">الإجمالي</p>
              <p className="text-2xl font-bold text-blue-600">{stats.total_amount?.toLocaleString() || 0} ج.م</p>
            </div>
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 mb-6">
        <div className="flex flex-col md:flex-row gap-4">
          {/* Search */}
          <div className="flex-1 relative">
            <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              placeholder="بحث برقم الشيك أو اسم العميل..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && loadCheques()}
              className="w-full pr-10 pl-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          {/* Status Filter */}
          <div className="flex items-center gap-2">
            <Filter className="w-5 h-5 text-gray-400" />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">جميع الشيكات</option>
              <option value="pending">معلقة</option>
              <option value="overdue">متأخرة</option>
              <option value="cleared">مصرفة</option>
            </select>
          </div>

          <button
            onClick={loadCheques}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            بحث
          </button>
        </div>
      </div>

      {/* Cheques Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        ) : cheques.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-64 text-gray-500">
            <FileCheck className="w-16 h-16 mb-4" />
            <p className="text-lg">لا توجد شيكات</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    رقم الشيك
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    العميل
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    البنك
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    المبلغ
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    تاريخ الاستحقاق
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    الحالة
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    إجراءات
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {cheques.map((cheque) => (
                  <tr 
                    key={cheque.id}
                    className={`hover:bg-gray-50 ${isOverdue(cheque.due_date, cheque.status) ? 'bg-red-50' : ''}`}
                  >
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <CreditCard className="w-4 h-4 text-gray-400" />
                        <span className="text-sm font-medium text-gray-900">
                          {cheque.cheque_number}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <User className="w-4 h-4 text-gray-400" />
                        <span className="text-sm text-gray-900">
                          {cheque.customer?.name || 'غير معروف'}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {cheque.bank_name}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm font-semibold text-gray-900">
                        {Number(cheque.amount).toLocaleString()} ج.م
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <Calendar className="w-4 h-4 text-gray-400" />
                        <span className="text-sm text-gray-900">
                          {new Date(cheque.due_date).toLocaleDateString('ar-EG')}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(cheque.status)}
                      {isOverdue(cheque.due_date, cheque.status) && (
                        <span className="mr-2 text-xs text-red-600">(متأخر)</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                      <div className="flex items-center gap-2">
                        {cheque.status === 'PENDING' && (
                          <>
                            <button
                              onClick={() => handleClearCheque(cheque)}
                              className="text-green-600 hover:text-green-800 font-medium"
                            >
                              صرف
                            </button>
                            <span className="text-gray-300">|</span>
                            <button
                              onClick={() => handleBounceCheque(cheque)}
                              className="text-red-600 hover:text-red-800 font-medium"
                            >
                              إرجاع
                            </button>
                          </>
                        )}
                        {cheque.status === 'CLEARED' && (
                          <span className="text-gray-500">تم الصرف</span>
                        )}
                        {cheque.status === 'RETURNED' && (
                          <span className="text-gray-500">مرتد</span>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {pagination.last_page > 1 && (
          <div className="px-6 py-4 border-t border-gray-200">
            <div className="flex items-center justify-between">
              <div className="text-sm text-gray-700">
                عرض {cheques.length} من {pagination.total} شيك
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page - 1 }))}
                  disabled={pagination.current_page === 1}
                  className="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                  السابق
                </button>
                <span className="px-3 py-1">
                  صفحة {pagination.current_page} من {pagination.last_page}
                </span>
                <button
                  onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page + 1 }))}
                  disabled={pagination.current_page === pagination.last_page}
                  className="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                  التالي
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Clear Cheque Dialog */}
      {showClearDialog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-bold text-gray-900 mb-4">تأكيد صرف الشيك</h3>
            <p className="text-gray-600 mb-6">
              هل أنت متأكد من صرف الشيك رقم <strong>{selectedCheque?.cheque_number}</strong> بمبلغ{' '}
              <strong>{Number(selectedCheque?.amount).toLocaleString()} ج.م</strong>؟
            </p>
            <div className="flex gap-3 justify-end">
              <button
                onClick={() => setShowClearDialog(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                إلغاء
              </button>
              <button
                onClick={confirmClearCheque}
                className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              >
                تأكيد الصرف
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Bounce Cheque Dialog */}
      {showBounceDialog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-bold text-gray-900 mb-4">إرجاع الشيك</h3>
            <p className="text-gray-600 mb-4">
              شيك رقم: <strong>{selectedCheque?.cheque_number}</strong>
            </p>
            <textarea
              placeholder="سبب الإرجاع..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
              rows="3"
              id="bounce-reason"
            />
            <div className="flex gap-3 justify-end">
              <button
                onClick={() => setShowBounceDialog(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                إلغاء
              </button>
              <button
                onClick={() => {
                  const reason = document.getElementById('bounce-reason').value;
                  if (reason.trim()) {
                    confirmBounceCheque(reason);
                  } else {
                    toast.error('يرجى إدخال سبب الإرجاع');
                  }
                }}
                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
              >
                تأكيد الإرجاع
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ChequesPage;
