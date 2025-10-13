<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\IssueVoucherItem;
use App\Models\ReturnVoucher;
use App\Models\ReturnVoucherItem;
use App\Models\CustomerLedger;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\InventoryMovement;
use App\Services\SequencerService;
use App\Services\InventoryService;
use Carbon\Carbon;

class RealisticTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعطيل Foreign Key Checks (SQLite compatible)
        DB::statement('PRAGMA foreign_keys = OFF;');
        
        // حذف البيانات القديمة
        $this->command->info('🗑️  حذف البيانات القديمة...');
        DB::table('cheques')->delete();
        DB::table('payments')->delete();
        DB::table('customer_ledger')->delete();
        DB::table('return_voucher_items')->delete();
        DB::table('return_vouchers')->delete();
        DB::table('issue_voucher_items')->delete();
        DB::table('issue_vouchers')->delete();
        DB::table('inventory_movements')->delete();
        DB::table('product_branch_stock')->delete();
        DB::table('products')->delete();
        DB::table('categories')->delete();
        DB::table('customers')->delete();
        DB::table('branches')->delete();
        DB::table('sequences')->delete();
        DB::table('users')->where('id', '>', 1)->delete();
        
        // إعادة تفعيل Foreign Key Checks
        DB::statement('PRAGMA foreign_keys = ON;');

        $this->command->info('✅ البيانات القديمة تم حذفها');
        $this->command->newLine();

        // إنشاء بيانات واقعية
        $this->command->info('📦 إنشاء بيانات واقعية...');
        $this->command->newLine();

        // 1. المستخدمين
        $this->seedUsers();
        
        // 2. الفروع
        $branches = $this->seedBranches();
        
        // 3. الفئات
        $categories = $this->seedCategories();
        
        // 4. المنتجات
        $products = $this->seedProducts($categories, $branches);
        
        // 5. العملاء
        $customers = $this->seedCustomers();
        
        // 6. سيناريو 1: أذون صرف متنوعة
        $this->scenario1_IssueVouchers($customers, $products, $branches);
        
        // 7. سيناريو 2: أذون ارتجاع
        $this->scenario2_ReturnVouchers($customers, $products, $branches);
        
        // 8. سيناريو 3: دفعات وشيكات
        $this->scenario3_PaymentsAndCheques($customers);
        
        // 9. سيناريو 4: تحويلات بين فروع
        $this->scenario4_Transfers($products, $branches);
        
        $this->command->newLine();
        $this->command->info('✅ تم إنشاء كل البيانات الواقعية بنجاح!');
        $this->command->info('🎉 جاهز للاختبار اليدوي!');
    }

    private function seedUsers()
    {
        $this->command->info('👤 إنشاء المستخدمين...');
        
        // المستخدم الأول موجود already من seeder سابق
        User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'أحمد محمد - المدير',
                'email' => 'admin@inventory.test',
                'password' => Hash::make('password'),
            ]
        );

        User::create([
            'name' => 'محمود حسن - أمين مخزن المصنع',
            'email' => 'factory@inventory.test',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'سارة علي - أمينة مخزن العتبة',
            'email' => 'ataba@inventory.test',
            'password' => Hash::make('password'),
        ]);
    }

    private function seedBranches()
    {
        $this->command->info('🏢 إنشاء الفروع...');
        
        $factory = Branch::create([
            'name' => 'المصنع',
            'code' => 'FAC',
            'location' => '6 أكتوبر - الحي الصناعي',
            'is_active' => true,
        ]);

        $ataba = Branch::create([
            'name' => 'العتبة',
            'code' => 'ATB',
            'location' => 'شارع الأزهر - العتبة',
            'is_active' => true,
        ]);

        $imbaba = Branch::create([
            'name' => 'إمبابة',
            'code' => 'IMB',
            'location' => 'شارع السودان - إمبابة',
            'is_active' => true,
        ]);

        return compact('factory', 'ataba', 'imbaba');
    }

    private function seedCategories()
    {
        $this->command->info('📁 إنشاء الفئات...');
        
        return [
            'lamps' => Category::create(['name' => 'لمبات LED', 'description' => 'لمبات ليد بجميع الأنواع']),
            'cables' => Category::create(['name' => 'كابلات كهربائية', 'description' => 'كابلات وأسلاك']),
            'switches' => Category::create(['name' => 'مفاتيح كهرباء', 'description' => 'مفاتيح إنارة وقواطع']),
            'tools' => Category::create(['name' => 'أدوات كهربائية', 'description' => 'أدوات ومعدات']),
            'accessories' => Category::create(['name' => 'إكسسوارات', 'description' => 'قطع غيار ومستلزمات']),
        ];
    }

    private function seedProducts($categories, $branches)
    {
        $this->command->info('🔌 إنشاء المنتجات...');
        
        $products = [];

        // لمبات LED
        $products[] = $this->createProduct([
            'sku' => 'LED-001',
            'name' => 'لمبة LED 9 وات',
            'brand' => 'Philips',
            'category_id' => $categories['lamps']->id,
            'unit' => 'حبة',
            'pack_size' => 50,
            'unit_price' => 25.00,
            'min_stock_default' => 100,
            'stocks' => [
                'factory' => ['stock' => 500, 'min' => 200],
                'ataba' => ['stock' => 300, 'min' => 100],
                'imbaba' => ['stock' => 200, 'min' => 80],
            ]
        ], $branches);

        $products[] = $this->createProduct([
            'sku' => 'LED-002',
            'name' => 'لمبة LED 12 وات',
            'brand' => 'Philips',
            'category_id' => $categories['lamps']->id,
            'unit' => 'حبة',
            'pack_size' => 50,
            'unit_price' => 35.00,
            'min_stock_default' => 100,
            'stocks' => [
                'factory' => ['stock' => 400, 'min' => 150],
                'ataba' => ['stock' => 250, 'min' => 80],
                'imbaba' => ['stock' => 150, 'min' => 60],
            ]
        ], $branches);

        $products[] = $this->createProduct([
            'sku' => 'LED-003',
            'name' => 'لمبة LED 18 وات',
            'brand' => 'Osram',
            'category_id' => $categories['lamps']->id,
            'unit' => 'حبة',
            'pack_size' => 30,
            'unit_price' => 55.00,
            'min_stock_default' => 60,
            'stocks' => [
                'factory' => ['stock' => 180, 'min' => 90],
                'ataba' => ['stock' => 120, 'min' => 50],
                'imbaba' => ['stock' => 90, 'min' => 40],
            ]
        ], $branches);

        // كابلات
        $products[] = $this->createProduct([
            'sku' => 'CBL-001',
            'name' => 'كابل 1.5 مم - أسود',
            'brand' => 'السويدي',
            'category_id' => $categories['cables']->id,
            'unit' => 'متر',
            'pack_size' => 100,
            'unit_price' => 12.50,
            'min_stock_default' => 500,
            'stocks' => [
                'factory' => ['stock' => 2000, 'min' => 800],
                'ataba' => ['stock' => 1000, 'min' => 400],
                'imbaba' => ['stock' => 800, 'min' => 300],
            ]
        ], $branches);

        $products[] = $this->createProduct([
            'sku' => 'CBL-002',
            'name' => 'كابل 2.5 مم - أحمر',
            'brand' => 'السويدي',
            'category_id' => $categories['cables']->id,
            'unit' => 'متر',
            'pack_size' => 100,
            'unit_price' => 18.00,
            'min_stock_default' => 500,
            'stocks' => [
                'factory' => ['stock' => 1500, 'min' => 600],
                'ataba' => ['stock' => 800, 'min' => 350],
                'imbaba' => ['stock' => 600, 'min' => 250],
            ]
        ], $branches);

        // مفاتيح
        $products[] = $this->createProduct([
            'sku' => 'SWT-001',
            'name' => 'مفتاح مفرد - أبيض',
            'brand' => 'Schneider',
            'category_id' => $categories['switches']->id,
            'unit' => 'حبة',
            'pack_size' => 20,
            'unit_price' => 15.00,
            'min_stock_default' => 100,
            'stocks' => [
                'factory' => ['stock' => 300, 'min' => 120],
                'ataba' => ['stock' => 180, 'min' => 80],
                'imbaba' => ['stock' => 120, 'min' => 60],
            ]
        ], $branches);

        $products[] = $this->createProduct([
            'sku' => 'SWT-002',
            'name' => 'مفتاح دوبل - أبيض',
            'brand' => 'Schneider',
            'category_id' => $categories['switches']->id,
            'unit' => 'حبة',
            'pack_size' => 20,
            'unit_price' => 22.00,
            'min_stock_default' => 80,
            'stocks' => [
                'factory' => ['stock' => 250, 'min' => 100],
                'ataba' => ['stock' => 150, 'min' => 70],
                'imbaba' => ['stock' => 100, 'min' => 50],
            ]
        ], $branches);

        // أدوات
        $products[] = $this->createProduct([
            'sku' => 'TLS-001',
            'name' => 'مفك كهربائي',
            'brand' => 'Bosch',
            'category_id' => $categories['tools']->id,
            'unit' => 'حبة',
            'pack_size' => 1,
            'unit_price' => 450.00,
            'min_stock_default' => 10,
            'stocks' => [
                'factory' => ['stock' => 25, 'min' => 10],
                'ataba' => ['stock' => 15, 'min' => 8],
                'imbaba' => ['stock' => 10, 'min' => 5],
            ]
        ], $branches);

        $products[] = $this->createProduct([
            'sku' => 'TLS-002',
            'name' => 'جهاز قياس فولت',
            'brand' => 'Fluke',
            'category_id' => $categories['tools']->id,
            'unit' => 'حبة',
            'pack_size' => 1,
            'unit_price' => 850.00,
            'min_stock_default' => 5,
            'stocks' => [
                'factory' => ['stock' => 12, 'min' => 6],
                'ataba' => ['stock' => 8, 'min' => 4],
                'imbaba' => ['stock' => 5, 'min' => 3],
            ]
        ], $branches);

        // إكسسوارات
        $products[] = $this->createProduct([
            'sku' => 'ACC-001',
            'name' => 'علبة توصيل مربعة',
            'brand' => 'محلي',
            'category_id' => $categories['accessories']->id,
            'unit' => 'حبة',
            'pack_size' => 100,
            'unit_price' => 5.00,
            'min_stock_default' => 200,
            'stocks' => [
                'factory' => ['stock' => 800, 'min' => 300],
                'ataba' => ['stock' => 500, 'min' => 200],
                'imbaba' => ['stock' => 400, 'min' => 150],
            ]
        ], $branches);

        return $products;
    }

    private function createProduct($data, $branches)
    {
        $product = Product::create([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'unit' => $data['unit'],
            'purchase_price' => $data['unit_price'] * 0.7, // سعر الشراء 70% من البيع
            'sale_price' => $data['unit_price'],
            'min_stock' => $data['min_stock_default'],
            'is_active' => true,
        ]);

        // إضافة الأرصدة الابتدائية لكل فرع
        foreach ($data['stocks'] as $branchKey => $stockData) {
            $branch = $branches[$branchKey];
            
            ProductBranchStock::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'current_stock' => $stockData['stock'],
                'reserved_stock' => 0,
            ]);

            // تسجيل حركة افتتاحية
            InventoryMovement::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'ADD',
                'quantity' => $stockData['stock'],
                'reference_type' => 'opening_balance',
                'reference_number' => 'افتتاحي',
                'notes' => 'رصيد افتتاحي للمنتج',
                'balance_after' => $stockData['stock'],
                'created_by' => 1,
            ]);
        }

        return $product;
    }

    private function seedCustomers()
    {
        $this->command->info('👥 إنشاء العملاء...');
        
        return [
            // عملاء جملة (نشطين جداً)
            Customer::create([
                'code' => 'CUST-001',
                'name' => 'شركة النور للكهرباء',
                'type' => 'wholesale',
                'phone' => '01001234567',
                'address' => 'شارع فيصل، الجيزة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'عميل ممتاز - دفعاته منتظمة',
            ]),

            Customer::create([
                'code' => 'CUST-002',
                'name' => 'محلات الإخلاص - العتبة',
                'type' => 'wholesale',
                'phone' => '01112345678',
                'address' => 'شارع الأزهر، العتبة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'يأخذ كميات كبيرة شهرياً',
            ]),

            // عملاء قطاعي
            Customer::create([
                'code' => 'CUST-003',
                'name' => 'المهندس أحمد حسين',
                'type' => 'retail',
                'phone' => '01223456789',
                'address' => 'المعادي، القاهرة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'مقاول تشطيبات',
            ]),

            Customer::create([
                'code' => 'CUST-004',
                'name' => 'شركة البناء الحديث',
                'type' => 'retail',
                'phone' => '01534567890',
                'address' => 'مدينة نصر، القاهرة',
                'balance' => 0,
                'is_active' => true,
            ]),

            // عميل نقدي (للمبيعات الفورية)
            Customer::create([
                'code' => 'CASH',
                'name' => 'عميل نقدي',
                'type' => 'cash',
                'phone' => null,
                'address' => null,
                'balance' => 0,
                'is_active' => true,
                'notes' => 'عميل نقدي افتراضي',
            ]),
        ];
    }

    private function scenario1_IssueVouchers($customers, $products, $branches)
    {
        $this->command->info('📝 سيناريو 1: أذون صرف متنوعة...');
        
        // إذن 1: بيع بدون خصم
        $this->createIssueVoucher([
            'customer' => $customers[0], // شركة النور
            'branch' => $branches['ataba'],
            'date' => Carbon::now()->subDays(10),
            'items' => [
                ['product' => $products[0], 'qty' => 100, 'price' => 25.00], // لمبة 9 وات
                ['product' => $products[3], 'qty' => 50, 'price' => 12.50],  // كابل 1.5
            ],
            'notes' => 'فاتورة شهر سبتمبر',
        ]);

        // إذن 2: بيع مع خصم على البنود
        $this->createIssueVoucher([
            'customer' => $customers[1], // الإخلاص
            'branch' => $branches['ataba'],
            'date' => Carbon::now()->subDays(8),
            'items' => [
                [
                    'product' => $products[1], 
                    'qty' => 80, 
                    'price' => 35.00,
                    'discount_type' => 'percentage',
                    'discount_value' => 10, // خصم 10%
                ],
                [
                    'product' => $products[5], 
                    'qty' => 40, 
                    'price' => 15.00,
                    'discount_type' => 'fixed',
                    'discount_value' => 50, // خصم 50 ج.م
                ],
            ],
            'notes' => 'خصم عميل مميز',
        ]);

        // إذن 3: بيع مع خصم على الفاتورة
        $this->createIssueVoucher([
            'customer' => $customers[2], // م. أحمد حسين
            'branch' => $branches['imbaba'],
            'date' => Carbon::now()->subDays(5),
            'items' => [
                ['product' => $products[0], 'qty' => 30, 'price' => 25.00],
                ['product' => $products[6], 'qty' => 20, 'price' => 22.00],
                ['product' => $products[9], 'qty' => 100, 'price' => 5.00],
            ],
            'voucher_discount' => [
                'type' => 'percentage',
                'value' => 5, // خصم 5% على الفاتورة
            ],
            'notes' => 'مشروع شقة المعادي',
        ]);

        // إذن 4: بيع بالخصمين معاً
        $this->createIssueVoucher([
            'customer' => $customers[0], // شركة النور
            'branch' => $branches['factory'],
            'date' => Carbon::now()->subDays(3),
            'items' => [
                [
                    'product' => $products[2], 
                    'qty' => 50, 
                    'price' => 55.00,
                    'discount_type' => 'percentage',
                    'discount_value' => 8, // خصم 8% على البند
                ],
                [
                    'product' => $products[4], 
                    'qty' => 100, 
                    'price' => 18.00,
                    'discount_type' => 'fixed',
                    'discount_value' => 100, // خصم 100 ج.م على البند
                ],
            ],
            'voucher_discount' => [
                'type' => 'fixed',
                'value' => 200, // خصم 200 ج.م على الفاتورة
            ],
            'notes' => 'عميل VIP - خصم خاص',
        ]);

        // إذن 5: بيع نقدي (بدون حساب)
        $this->createIssueVoucher([
            'customer' => $customers[4], // عميل نقدي
            'branch' => $branches['ataba'],
            'date' => Carbon::now()->subDays(1),
            'items' => [
                ['product' => $products[0], 'qty' => 10, 'price' => 25.00],
                ['product' => $products[5], 'qty' => 5, 'price' => 15.00],
            ],
            'notes' => 'بيع نقدي - تم التحصيل',
        ]);
    }

    private function createIssueVoucher($data)
    {
        $voucherNumber = SequencerService::getNext('issue_voucher', 'ISS-', 6);
        
        // حساب البنود
        $subtotal = 0;
        $itemsData = [];
        
        foreach ($data['items'] as $item) {
            $totalPrice = $item['qty'] * $item['price'];
            
            // حساب خصم البند
            $discountType = $item['discount_type'] ?? 'none';
            $discountValue = $item['discount_value'] ?? 0;
            $discountAmount = 0;
            
            if ($discountType === 'percentage') {
                $discountAmount = ($totalPrice * $discountValue) / 100;
            } elseif ($discountType === 'fixed') {
                $discountAmount = min($discountValue, $totalPrice);
            }
            
            $netPrice = $totalPrice - $discountAmount;
            $subtotal += $netPrice;
            
            $itemsData[] = [
                'product' => $item['product'],
                'quantity' => $item['qty'],
                'unit_price' => $item['price'],
                'total_price' => $totalPrice,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'net_price' => $netPrice,
            ];
        }
        
        // حساب خصم الفاتورة
        $voucherDiscountType = $data['voucher_discount']['type'] ?? 'none';
        $voucherDiscountValue = $data['voucher_discount']['value'] ?? 0;
        $voucherDiscountAmount = 0;
        
        if ($voucherDiscountType === 'percentage') {
            $voucherDiscountAmount = ($subtotal * $voucherDiscountValue) / 100;
        } elseif ($voucherDiscountType === 'fixed') {
            $voucherDiscountAmount = min($voucherDiscountValue, $subtotal);
        }
        
        $netTotal = $subtotal - $voucherDiscountAmount;
        
        // إنشاء الإذن
        $voucher = IssueVoucher::create([
            'voucher_number' => $voucherNumber,
            'customer_id' => $data['customer']->type !== 'cash' ? $data['customer']->id : null,
            'customer_name' => $data['customer']->type === 'cash' ? 'عميل نقدي' : null,
            'branch_id' => $data['branch']->id,
            'issue_date' => $data['date'],
            'notes' => $data['notes'] ?? null,
            'total_amount' => $subtotal + $voucherDiscountAmount,
            'discount_type' => $voucherDiscountType,
            'discount_value' => $voucherDiscountValue,
            'discount_amount' => $voucherDiscountAmount,
            'subtotal' => $subtotal,
            'net_total' => $netTotal,
            'status' => 'completed',
            'created_by' => 1,
            'created_at' => $data['date'],
        ]);
        
        // إضافة البنود وخصم المخزون
        foreach ($itemsData as $itemData) {
            IssueVoucherItem::create([
                'issue_voucher_id' => $voucher->id,
                'product_id' => $itemData['product']->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
                'discount_type' => $itemData['discount_type'],
                'discount_value' => $itemData['discount_value'],
                'discount_amount' => $itemData['discount_amount'],
                'net_price' => $itemData['net_price'],
            ]);
            
            // خصم المخزون
            $stock = ProductBranchStock::where('product_id', $itemData['product']->id)
                ->where('branch_id', $data['branch']->id)
                ->first();
            
            if ($stock) {
                $oldStock = $stock->current_stock;
                $stock->decrement('current_stock', $itemData['quantity']);
                
                InventoryMovement::create([
                    'product_id' => $itemData['product']->id,
                    'branch_id' => $data['branch']->id,
                    'movement_type' => 'ISSUE',
                    'quantity' => $itemData['quantity'],
                    'reference_type' => 'issue_voucher',
                    'reference_number' => $voucherNumber,
                    'reference_id' => $voucher->id,
                    'balance_after' => $oldStock - $itemData['quantity'],
                    'created_by' => 1,
                    'created_at' => $data['date'],
                ]);
            }
        }
        
        // تحديث رصيد العميل (إذا لم يكن نقدي)
        if ($voucher->customer_id) {
            $data['customer']->decrement('balance', $netTotal);
            
            CustomerLedger::create([
                'customer_id' => $voucher->customer_id,
                'transaction_type' => 'issue_voucher',
                'reference_number' => $voucherNumber,
                'reference_id' => $voucher->id,
                'transaction_date' => $data['date'],
                'debit' => 0,
                'credit' => $netTotal,
                'balance' => -$data['customer']->fresh()->balance,
                'notes' => 'إذن صرف - ' . $data['branch']->name,
                'created_by' => 1,
                'created_at' => $data['date'],
            ]);
        }
    }

    private function scenario2_ReturnVouchers($customers, $products, $branches)
    {
        $this->command->info('🔄 سيناريو 2: أذون ارتجاع...');
        
        // ارتجاع 1: عميل أرجع بضاعة معيبة
        $this->createReturnVoucher([
            'customer' => $customers[1], // الإخلاص
            'branch' => $branches['ataba'],
            'date' => Carbon::now()->subDays(4),
            'items' => [
                ['product' => $products[1], 'qty' => 10, 'price' => 35.00], // 10 لمبات معيبة
            ],
            'notes' => 'بضاعة معيبة - استبدال',
        ]);
        
        // ارتجاع 2: عميل أرجع فائض الطلبية
        $this->createReturnVoucher([
            'customer' => $customers[2], // م. أحمد
            'branch' => $branches['imbaba'],
            'date' => Carbon::now()->subDays(2),
            'items' => [
                ['product' => $products[0], 'qty' => 5, 'price' => 25.00],
                ['product' => $products[9], 'qty' => 20, 'price' => 5.00],
            ],
            'notes' => 'فائض عن الطلب',
        ]);
    }

    private function createReturnVoucher($data)
    {
        $voucherNumber = SequencerService::getNext('return_voucher', 'RET-', 6);
        
        $totalAmount = 0;
        
        // إنشاء الإذن
        $voucher = ReturnVoucher::create([
            'voucher_number' => $voucherNumber,
            'customer_id' => $data['customer']->id,
            'branch_id' => $data['branch']->id,
            'return_date' => $data['date'],
            'notes' => $data['notes'] ?? null,
            'status' => 'completed',
            'created_by' => 1,
            'created_at' => $data['date'],
        ]);
        
        // إضافة البنود وزيادة المخزون
        foreach ($data['items'] as $item) {
            $itemTotal = $item['qty'] * $item['price'];
            $totalAmount += $itemTotal;
            
            ReturnVoucherItem::create([
                'return_voucher_id' => $voucher->id,
                'product_id' => $item['product']->id,
                'quantity' => $item['qty'],
                'unit_price' => $item['price'],
            ]);
            
            // زيادة المخزون
            $stock = ProductBranchStock::where('product_id', $item['product']->id)
                ->where('branch_id', $data['branch']->id)
                ->first();
            
            if ($stock) {
                $oldStock = $stock->current_stock;
                $stock->increment('current_stock', $item['qty']);
                
                InventoryMovement::create([
                    'product_id' => $item['product']->id,
                    'branch_id' => $data['branch']->id,
                    'movement_type' => 'RETURN',
                    'quantity' => $item['qty'],
                    'reference_type' => 'return_voucher',
                    'reference_number' => $voucherNumber,
                    'reference_id' => $voucher->id,
                    'balance_after' => $oldStock + $item['qty'],
                    'created_by' => 1,
                    'created_at' => $data['date'],
                ]);
            }
        }
        
        $voucher->update(['total_amount' => $totalAmount]);
        
        // تحديث رصيد العميل (له - دائن)
        $data['customer']->increment('balance', $totalAmount);
        
        CustomerLedger::create([
            'customer_id' => $voucher->customer_id,
            'transaction_type' => 'return_voucher',
            'reference_number' => $voucherNumber,
            'reference_id' => $voucher->id,
            'transaction_date' => $data['date'],
            'debit' => $totalAmount,
            'credit' => 0,
            'balance' => -$data['customer']->fresh()->balance,
            'notes' => 'إذن ارتجاع - ' . $data['branch']->name,
            'created_by' => 1,
            'created_at' => $data['date'],
        ]);
    }

    private function scenario3_PaymentsAndCheques($customers)
    {
        $this->command->info('💰 سيناريو 3: دفعات وشيكات...');
        
        // دفعة نقدية من شركة النور
        Payment::create([
            'payment_number' => 'PAY-000001',
            'customer_id' => $customers[0]->id,
            'payment_date' => Carbon::now()->subDays(6),
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'notes' => 'دفعة نقدية على الحساب',
            'created_by' => 1,
        ]);
        
        $customers[0]->increment('balance', 1500);
        
        CustomerLedger::create([
            'customer_id' => $customers[0]->id,
            'transaction_type' => 'payment',
            'reference_number' => 'PAY-000001',
            'transaction_date' => Carbon::now()->subDays(6),
            'debit' => 1500.00,
            'credit' => 0,
            'balance' => -$customers[0]->fresh()->balance,
            'notes' => 'دفعة نقدية',
            'created_by' => 1,
        ]);
        
        // شيك قيد التحصيل
        Cheque::create([
            'customer_id' => $customers[1]->id,
            'cheque_number' => '12345678',
            'bank_name' => 'بنك مصر',
            'cheque_date' => Carbon::now()->addDays(10),
            'amount' => 2000.00,
            'invoice_number' => 'ISS-000002',
            'status' => 'PENDING',
            'notes' => 'شيك مؤجل',
            'created_by' => 1,
        ]);
        
        // شيك محصّل
        $cheque = Cheque::create([
            'customer_id' => $customers[2]->id,
            'cheque_number' => '87654321',
            'bank_name' => 'البنك الأهلي',
            'cheque_date' => Carbon::now()->subDays(2),
            'amount' => 800.00,
            'invoice_number' => 'ISS-000003',
            'status' => 'CLEARED',
            'cleared_date' => Carbon::now()->subDays(1),
            'notes' => 'تم التحصيل',
            'created_by' => 1,
        ]);
        
        $customers[2]->increment('balance', 800);
        
        CustomerLedger::create([
            'customer_id' => $customers[2]->id,
            'transaction_type' => 'cheque',
            'reference_number' => '87654321',
            'transaction_date' => Carbon::now()->subDays(1),
            'debit' => 800.00,
            'credit' => 0,
            'balance' => -$customers[2]->fresh()->balance,
            'notes' => 'تحصيل شيك - البنك الأهلي',
            'created_by' => 1,
        ]);
    }

    private function scenario4_Transfers($products, $branches)
    {
        $this->command->info('🔄 سيناريو 4: تحويلات بين الفروع...');
        
        // تحويل من المصنع للعتبة
        $this->createTransfer([
            'from_branch' => $branches['factory'],
            'to_branch' => $branches['ataba'],
            'date' => Carbon::now()->subDays(7),
            'items' => [
                ['product' => $products[0], 'qty' => 50], // لمبة 9 وات
                ['product' => $products[3], 'qty' => 100], // كابل
            ],
            'notes' => 'تموين فرع العتبة',
        ]);
        
        // تحويل من المصنع لإمبابة
        $this->createTransfer([
            'from_branch' => $branches['factory'],
            'to_branch' => $branches['imbaba'],
            'date' => Carbon::now()->subDays(5),
            'items' => [
                ['product' => $products[1], 'qty' => 30], // لمبة 12 وات
                ['product' => $products[5], 'qty' => 20], // مفاتيح
            ],
            'notes' => 'تموين فرع إمبابة',
        ]);
    }

    private function createTransfer($data)
    {
        $transferNumber = SequencerService::getNext('transfer', 'TRF-', 6);
        
        foreach ($data['items'] as $item) {
            // خصم من الفرع المصدر
            $fromStock = ProductBranchStock::where('product_id', $item['product']->id)
                ->where('branch_id', $data['from_branch']->id)
                ->first();
            
            if ($fromStock) {
                $oldStock = $fromStock->current_stock;
                $fromStock->decrement('current_stock', $item['qty']);
                
                InventoryMovement::create([
                    'product_id' => $item['product']->id,
                    'branch_id' => $data['from_branch']->id,
                    'movement_type' => 'TRANSFER_OUT',
                    'quantity' => $item['qty'],
                    'reference_type' => 'transfer',
                    'reference_number' => $transferNumber,
                    'notes' => 'تحويل إلى ' . $data['to_branch']->name,
                    'balance_after' => $oldStock - $item['qty'],
                    'created_by' => 1,
                    'created_at' => $data['date'],
                ]);
            }
            
            // إضافة للفرع المستهدف
            $toStock = ProductBranchStock::where('product_id', $item['product']->id)
                ->where('branch_id', $data['to_branch']->id)
                ->first();
            
            if ($toStock) {
                $oldStock = $toStock->current_stock;
                $toStock->increment('current_stock', $item['qty']);
                
                InventoryMovement::create([
                    'product_id' => $item['product']->id,
                    'branch_id' => $data['to_branch']->id,
                    'movement_type' => 'TRANSFER_IN',
                    'quantity' => $item['qty'],
                    'reference_type' => 'transfer',
                    'reference_number' => $transferNumber,
                    'notes' => 'تحويل من ' . $data['from_branch']->name,
                    'balance_after' => $oldStock + $item['qty'],
                    'created_by' => 1,
                    'created_at' => $data['date'],
                ]);
            }
        }
    }
}
