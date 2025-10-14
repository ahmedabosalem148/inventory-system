<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    protected CustomerLedgerService $ledgerService;

    public function __construct(CustomerLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * عرض قائمة العملاء مع فلترة
     * 
     * GET /api/v1/customers
     * Query: ?search=أحمد&is_active=1&balance_type=credit
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        \Log::info('CustomerController@index called', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'params' => $request->all(),
            'has_is_active' => $request->has('is_active'),
            'filled_is_active' => $request->filled('is_active'),
            'is_active_value' => $request->get('is_active'),
        ]);
        
        $query = Customer::query();
        
        \Log::info('Initial query count: ' . Customer::count());

        // Search by name or phone
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by balance type
        if ($request->filled('balance_type')) {
            match ($request->balance_type) {
                'credit' => $query->withCredit(),
                'debit' => $query->withDebit(),
                'zero' => $query->where('balance', 0),
                default => null,
            };
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $customers = $query->paginate($perPage);
        
        \Log::info('Query result', [
            'total' => $customers->total(),
            'count' => $customers->count(),
            'sql' => $query->toSql(),
        ]);

        return CustomerResource::collection($customers);
    }

    /**
     * إنشاء عميل جديد
     * 
     * POST /api/v1/customers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'nullable|string|in:retail,wholesale',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'tax_id' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب',
            'name.max' => 'اسم العميل طويل جداً',
            'type.in' => 'نوع العميل غير صحيح',
            'credit_limit.min' => 'حد الائتمان يجب أن يكون رقم موجب',
        ]);

        try {
            // Generate unique code
            $lastCustomer = Customer::latest('id')->first();
            $nextNumber = $lastCustomer ? ($lastCustomer->id + 1) : 1;
            $validated['code'] = 'CUS-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $customer = Customer::create($validated);

            return response()->json([
                'message' => 'تم إضافة العميل بنجاح',
                'data' => CustomerResource::make($customer),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إضافة العميل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * عرض تفاصيل عميل واحد
     * 
     * GET /api/v1/customers/{id}
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load('ledgerEntries');

        return response()->json([
            'data' => CustomerResource::make($customer),
        ], 200);
    }

    /**
     * تحديث بيانات عميل
     * 
     * PUT/PATCH /api/v1/customers/{id}
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'type' => 'nullable|string|in:retail,wholesale',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'tax_id' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ], [
            'name.max' => 'اسم العميل طويل جداً',
            'type.in' => 'نوع العميل غير صحيح',
            'credit_limit.min' => 'حد الائتمان يجب أن يكون رقم موجب',
        ]);

        try {
            $customer->update($validated);

            return response()->json([
                'message' => 'تم تحديث بيانات العميل بنجاح',
                'data' => CustomerResource::make($customer->fresh()),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث العميل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * حذف عميل
     * 
     * DELETE /api/v1/customers/{id}
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            // Check if customer has balance
            if ($customer->balance != 0) {
                $balanceFormatted = number_format(abs($customer->balance), 2);
                $balanceType = $customer->balance > 0 ? 'دائن' : 'مدين';
                
                return response()->json([
                    'message' => "لا يمكن حذف العميل. يوجد رصيد: {$balanceFormatted} ج.م ({$balanceType})",
                    'balance' => $customer->balance,
                ], 422);
            }

            // Check for vouchers
            $hasVouchers = DB::table('issue_vouchers')
                ->where('customer_id', $customer->id)
                ->exists();

            if ($hasVouchers) {
                return response()->json([
                    'message' => 'لا يمكن حذف العميل. يوجد فواتير مسجلة لهذا العميل',
                ], 422);
            }

            $customer->delete();

            return response()->json([
                'message' => 'تم حذف العميل بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف العميل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * بحث سريع عن عملاء (للـ autocomplete)
     * 
     * GET /api/v1/search/customers?q=أحمد
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []], 200);
        }

        $customers = Customer::active()
            ->search($query)
            ->limit(10)
            ->get(['id', 'code', 'name', 'phone', 'balance']);

        return response()->json([
            'data' => $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'balance' => (float) $customer->balance,
                    'balance_formatted' => $customer->formatted_balance,
                ];
            }),
        ], 200);
    }

    /**
     * الحصول على قائمة العملاء مع الأرصدة
     * 
     * GET /api/v1/customers/balances
     * Query: ?only_with_balance=1&sort_by=balance
     */
    public function getCustomersWithBalances(Request $request): JsonResponse
    {
        $request->validate([
            'only_with_balance' => 'sometimes|boolean',
            'sort_by' => 'sometimes|in:name,balance,last_activity',
        ]);

        try {
            $onlyWithBalance = $request->boolean('only_with_balance', false);
            $sortBy = $request->get('sort_by', 'name');

            $customers = $this->ledgerService->getCustomersBalances($onlyWithBalance, $sortBy);

            return response()->json([
                'data' => $customers,
                'meta' => [
                    'total_count' => $customers->count(),
                    'total_debtors' => $this->ledgerService->getTotalDebtors(),
                    'total_creditors' => $this->ledgerService->getTotalCreditors(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب أرصدة العملاء',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * كشف حساب العميل
     * 
     * GET /api/v1/customers/{id}/statement
     * Query: ?from_date=2025-01-01&to_date=2025-12-31&include_balance=1
     */
    public function getStatement(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'include_balance' => 'sometimes|boolean',
        ], [
            'from_date.required' => 'تاريخ البداية مطلوب',
            'to_date.required' => 'تاريخ النهاية مطلوب',
            'to_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
        ]);

        try {
            $includeBalance = $request->boolean('include_balance', true);

            $statement = $this->ledgerService->getCustomerStatement(
                $customer->id,
                $validated['from_date'],
                $validated['to_date'],
                $includeBalance
            );

            return response()->json([
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'code' => $customer->code,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ],
                    'statement' => $statement,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب كشف الحساب',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * الحصول على رصيد العميل الحالي
     * 
     * GET /api/v1/customers/{id}/balance
     */
    public function getBalance(Customer $customer): JsonResponse
    {
        try {
            $balance = $this->ledgerService->calculateBalance($customer->id);

            return response()->json([
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'balance' => $balance,
                    'balance_formatted' => number_format(abs($balance), 2) . ' ج.م',
                    'status' => $balance > 0 ? 'مدين' : ($balance < 0 ? 'دائن' : 'متوازن'),
                    'status_english' => $balance > 0 ? 'debtor' : ($balance < 0 ? 'creditor' : 'zero'),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حساب الرصيد',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * الحصول على نشاط العميل
     * 
     * GET /api/v1/customers/{id}/activity
     */
    public function getActivity(Customer $customer): JsonResponse
    {
        try {
            // الحصول على آخر 10 قيود
            $recentEntries = $customer->ledgerEntries()
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();

            // الحصول على إحصائيات
            $stats = $customer->ledgerEntries()
                ->selectRaw('
                    COUNT(*) as total_entries,
                    SUM(debit_aliah) as total_debit,
                    SUM(credit_lah) as total_credit,
                    MAX(entry_date) as last_entry_date,
                    MIN(entry_date) as first_entry_date
                ')
                ->first();

            // الحصول على آخر فاتورة
            $lastVoucher = DB::table('issue_vouchers')
                ->where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                    ],
                    'current_balance' => $this->ledgerService->calculateBalance($customer->id),
                    'recent_entries' => $recentEntries,
                    'statistics' => [
                        'total_entries' => $stats->total_entries ?? 0,
                        'total_debit' => round($stats->total_debit ?? 0, 2),
                        'total_credit' => round($stats->total_credit ?? 0, 2),
                        'last_entry_date' => $stats->last_entry_date,
                        'first_entry_date' => $stats->first_entry_date,
                        'last_voucher_date' => $lastVoucher?->created_at,
                        'last_voucher_number' => $lastVoucher?->voucher_number,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب نشاط العميل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * إحصائيات دفتر العملاء
     * 
     * GET /api/v1/customers/statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->ledgerService->getStatistics();

            return response()->json([
                'data' => $statistics,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }
}
