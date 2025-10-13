<?php

namespace App\Http\Controllers;

use App\Imports\ProductStockImport;
use App\Imports\CustomerImport;
use App\Imports\ChequeImport;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Show import page
     */
    public function index()
    {
        return view('imports.index');
    }

    /**
     * Download CSV template for products
     */
    public function downloadTemplate()
    {
        $csv = "كود الفرع,كود المنتج (SKU),الكمية\n";
        $csv .= "FACTORY,PROD-001,100\n";
        $csv .= "FACTORY,PROD-002,200\n";
        $csv .= "ATABAH,PROD-001,150\n";
        $csv .= "IMBABAH,PROD-003,75\n";
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="قالب_استيراد_الأرصدة.csv"');
    }

    /**
     * Preview uploaded file before importing
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            // Read CSV file
            $handle = fopen($path, 'r');
            $headers = fgetcsv($handle);
            
            $rows = [];
            $count = 0;
            while (($row = fgetcsv($handle)) !== false && $count < 100) {
                $rows[] = $row;
                $count++;
            }
            fclose($handle);
            
            return view('imports.preview', [
                'headers' => $headers,
                'rows' => $rows,
                'totalRows' => $count,
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في قراءة الملف: ' . $e->getMessage());
        }
    }

    /**
     * Execute the product stock import
     */
    public function execute(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            // Create import instance
            $import = new ProductStockImport($this->inventoryService);
            
            // Import the file
            $import->importFromCsv($path);
            
            // Get results
            $results = $import->getResults();
            
            // Count success/errors
            $successCount = collect($results)->where('status', 'success')->count();
            $errorCount = collect($results)->where('status', 'error')->count();
            
            return view('imports.results', [
                'results' => $results,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }
    }

    /**
     * Download customer CSV template
     */
    public function downloadCustomerTemplate()
    {
        $csv = "كود العميل,الاسم,الهاتف,العنوان,الرصيد الافتتاحي\n";
        $csv .= "CUST-001,محمد أحمد,01012345678,القاهرة - المعادي,5000\n";
        $csv .= "CUST-002,أحمد علي,01098765432,الجيزة - فيصل,-2500\n";
        $csv .= "CUST-003,خالد محمود,01155544433,القاهرة - مصر الجديدة,0\n";
        $csv .= "CUST-004,سامي حسن,01066677788,الجيزة - الهرم,10000\n";
        $csv .= "CUST-005,عمرو سعيد,01122233344,القاهرة - النزهة,3500\n";
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="قالب_استيراد_العملاء.csv"');
    }

    /**
     * Execute customer import
     */
    public function executeCustomerImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            // Create import instance
            $import = new CustomerImport();
            
            // Import the file
            $import->importFromCsv($path);
            
            // Get results
            $results = $import->getResults();
            
            // Count success/errors
            $successCount = collect($results)->where('status', 'success')->count();
            $errorCount = collect($results)->where('status', 'error')->count();
            
            return view('imports.results', [
                'results' => $results,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }
    }

    /**
     * Download cheque CSV template
     */
    public function downloadChequeTemplate()
    {
        $csv = "كود العميل,رقم الشيك,البنك,تاريخ الاستحقاق,المبلغ,رقم الفاتورة (اختياري)\n";
        $csv .= "CUST-001,CHQ-12345,بنك مصر,2025-11-15,5000,\n";
        $csv .= "CUST-002,CHQ-12346,البنك الأهلي,2025-12-01,8500,\n";
        $csv .= "CUST-003,CHQ-12347,بنك القاهرة,2025-10-20,3200,\n";
        $csv .= "CUST-004,CHQ-12348,CIB,2025-11-30,12000,\n";
        $csv .= "CUST-005,CHQ-12349,QNB,2025-12-15,6700,\n";
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="قالب_استيراد_الشيكات.csv"');
    }

    /**
     * Execute cheque import
     */
    public function executeChequeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            // Create import instance
            $import = new ChequeImport();
            
            // Import the file
            $import->importFromCsv($path);
            
            // Get results
            $results = $import->getResults();
            
            // Count success/errors
            $successCount = collect($results)->where('status', 'success')->count();
            $errorCount = collect($results)->where('status', 'error')->count();
            
            return view('imports.results', [
                'results' => $results,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطأ في الاستيراد: ' . $e->getMessage());
        }
    }
}
