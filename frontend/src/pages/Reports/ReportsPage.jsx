import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Sidebar, Navbar } from '../../components/organisms';
import { Card } from '../../components/atoms';
import { BarChart3, FileText, TrendingUp } from 'lucide-react';

const ReportsPage = () => {
  const navigate = useNavigate();

  const reports = [
    {
      id: 'stock-valuation',
      title: 'تقرير تقييم المخزون',
      description: 'عرض قيمة المخزون الحالية لكل منتج حسب التكلفة',
      icon: BarChart3,
      path: '/reports/stock-valuation',
      color: 'bg-blue-500',
    },
    {
      id: 'customer-statement',
      title: 'كشف حساب العميل',
      description: 'عرض حركات العميل والرصيد الحالي',
      icon: FileText,
      path: '/reports/customer-statement',
      color: 'bg-green-500',
      disabled: true, // سيتم تفعيلها لاحقاً
    },
    {
      id: 'sales-summary',
      title: 'ملخص المبيعات',
      description: 'عرض إحصائيات المبيعات حسب الفترة والفرع',
      icon: TrendingUp,
      path: '/reports/sales-summary',
      color: 'bg-purple-500',
      disabled: true, // سيتم تفعيلها لاحقاً
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar />
      <Navbar />
      <main className="pt-16 lg:mr-64 p-4 md:p-6 min-h-screen">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">التقارير</h1>
          <p className="text-gray-600">
            اختر التقرير المطلوب من القائمة أدناه
          </p>
        </div>

        {/* Reports Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {reports.map((report) => {
            const Icon = report.icon;
            return (
              <Card
                key={report.id}
                className={`cursor-pointer hover:shadow-lg transition-shadow ${
                  report.disabled ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() => !report.disabled && navigate(report.path)}
              >
                <div className="p-6">
                  <div className="flex items-start gap-4">
                    <div
                      className={`${report.color} p-3 rounded-lg text-white`}
                    >
                      <Icon className="w-6 h-6" />
                    </div>
                    <div className="flex-1">
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        {report.title}
                        {report.disabled && (
                          <span className="text-xs text-gray-500 mr-2">
                            (قريباً)
                          </span>
                        )}
                      </h3>
                      <p className="text-sm text-gray-600">
                        {report.description}
                      </p>
                    </div>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>
      </main>
    </div>
  );
};

export default ReportsPage;
