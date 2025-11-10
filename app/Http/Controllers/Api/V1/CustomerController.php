<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\CustomerLedgerEntryResource;
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
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
     * عرض تفاصيل عميل
     * 
     * GET /api/v1/customers/{id}
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        // Load ledger entries with optional date filtering
        $query = $customer->ledgerEntries();
        
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        $customer->setRelation('ledgerEntries', $query->orderBy('created_at', 'desc')->get());

        return response()->json([
            'data' => CustomerResource::make($customer),
        ], 200);
    }

    /**
     * Get customer ledger entries
     * 
     * GET /api/v1/customers/{id}/ledger
     */
    public function getLedger(Request $request, Customer $customer): JsonResponse
    {
        $query = $customer->ledgerEntries();
        
        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        
        $entries = $query->orderBy('transaction_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->paginate($request->get('per_page', 50));
        
        return response()->json([
            'data' => CustomerLedgerEntryResource::collection($entries),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ], 200);
    }

    /**
     * تحديث بيانات عميل
     * 
     * PUT/PATCH /api/v1/customers/{id}
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();

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
            $search = $request->get('search');
            $onlyWithBalance = $request->boolean('only_with_balance', false);
            $sortBy = $request->get('sort_by', 'name');

            // Get all customers with balances
            $allCustomers = $this->ledgerService->getCustomersBalances($onlyWithBalance, $sortBy);
            
            // Apply search filter if provided
            if ($search) {
                $allCustomers = $allCustomers->filter(function ($customer) use ($search) {
                    return stripos($customer['name'], $search) !== false ||
                           stripos($customer['code'], $search) !== false ||
                           stripos($customer['phone'] ?? '', $search) !== false;
                });
            }

            // Calculate statistics
            $statistics = [
                'total_customers' => $allCustomers->count(),
                'debtors_count' => $allCustomers->where('status', 'debtor')->count(),
                'creditors_count' => $allCustomers->where('status', 'creditor')->count(),
                'zero_balance_count' => $allCustomers->where('status', 'zero')->count(),
            ];

            // Transform customers to include additional stats
            $customers = $allCustomers->map(function ($customer) {
                return [
                    'id' => $customer['id'],
                    'code' => $customer['code'],
                    'name' => $customer['name'],
                    'phone' => $customer['phone'] ?? null,
                    'address' => null, // Add if needed
                    'balance' => $customer['balance'],
                    'status' => $customer['status'],
                    'last_activity_at' => $customer['last_activity_at'],
                    'purchases_count' => 0, // TODO: Calculate from vouchers
                    'purchases_total' => $customer['total_debit'],
                    'returns_count' => 0, // TODO: Calculate from return vouchers
                    'returns_total' => 0, // TODO: Calculate
                    'payments_total' => $customer['total_credit'],
                ];
            });

            return response()->json([
                'customers' => $customers->values(),
                'statistics' => $statistics,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('CustomerController@getCustomersWithBalances error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
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

            $statementData = $this->ledgerService->getCustomerStatement(
                $customer->id,
                $validated['from_date'],
                $validated['to_date'],
                $includeBalance
            );

            // Transform entries to match frontend expectations
            $entries = collect($statementData['entries'])->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->transaction_date,
                    'description' => $entry->notes ?? $entry->transaction_type,
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'running_balance' => $entry->running_balance ?? 0,
                    'reference_type' => $entry->transaction_type ?? '',
                    'reference_id' => $entry->reference_id ?? null,
                ];
            });

            // Get customer stats
            $customerBalance = $this->ledgerService->getCustomersBalances(false, 'name')
                ->firstWhere('id', $customer->id);

            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'balance' => $customerBalance['balance'] ?? 0,
                    'status' => $customerBalance['status'] ?? 'zero',
                    'purchases_count' => 0, // TODO
                    'purchases_total' => $customerBalance['total_debit'] ?? 0,
                    'returns_count' => 0, // TODO
                    'returns_total' => 0, // TODO
                    'payments_total' => $customerBalance['total_credit'] ?? 0,
                ],
                'opening_balance' => $statementData['opening_balance'],
                'entries' => $entries,
                'total_debit' => $statementData['total_debit'],
                'total_credit' => $statementData['total_credit'],
                'closing_balance' => $statementData['closing_balance'],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('CustomerController@getStatement error: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit,
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
     * تصدير كشف حساب العميل PDF
     * 
     * GET /api/v1/customers/{id}/statement/pdf
     */
    public function exportStatementPDF(Request $request, Customer $customer)
    {
        try {
            $query = $customer->ledgerEntries();
            
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }
            
            $ledgerEntries = $query->orderBy('created_at', 'desc')->get();
            
            // TODO: Implement actual PDF generation
            // For now, return a simple text file
            $content = "كشف حساب العميل: {$customer->name}\n";
            $content .= "الرصيد الحالي: " . number_format($customer->balance, 2) . " ج.م\n\n";
            $content .= "الحركات المالية:\n";
            $content .= str_repeat("-", 80) . "\n";
            
            foreach ($ledgerEntries as $entry) {
                $content .= sprintf(
                    "%s | %s | مدين: %s | دائن: %s | الرصيد: %s\n",
                    $entry->created_at->format('Y-m-d'),
                    $entry->description,
                    number_format($entry->debit_amount, 2),
                    number_format($entry->credit_amount, 2),
                    number_format($entry->running_balance, 2)
                );
            }
            
            return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"customer-{$customer->id}-statement.pdf\"");
                
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تصدير PDF',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * تصدير كشف حساب العميل Excel
     * 
     * GET /api/v1/customers/{id}/statement/excel
     */
    public function exportStatementExcel(Request $request, Customer $customer)
    {
        try {
            $query = $customer->ledgerEntries();
            
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }
            
            $ledgerEntries = $query->orderBy('created_at', 'desc')->get();
            
            // TODO: Implement actual Excel generation using PhpSpreadsheet or Laravel Excel
            // For now, return CSV format
            $csv = "التاريخ,الوصف,مدين,دائن,الرصيد\n";
            
            foreach ($ledgerEntries as $entry) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s"' . "\n",
                    $entry->created_at->format('Y-m-d H:i:s'),
                    $entry->description,
                    number_format($entry->debit_amount, 2),
                    number_format($entry->credit_amount, 2),
                    number_format($entry->running_balance, 2)
                );
            }
            
            return response($csv)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', "attachment; filename=\"customer-{$customer->id}-statement.xlsx\"");
                
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تصدير Excel',
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
