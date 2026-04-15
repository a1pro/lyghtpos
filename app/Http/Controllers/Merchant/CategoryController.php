<?php
// app/Http/Controllers/Merchant/CategoryController.php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\MerchantProductImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $service;

    public function __construct(MerchantProductImportService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $categories = Cache::remember('cyclos_categories', 3600, function () {
            $cats = $this->service->getCategories();
            
            // Ensure all IDs are strings
            foreach ($cats as &$cat) {
                $cat['id'] = (string) $cat['id'];
                if (strpos($cat['id'], 'E') !== false || strpos($cat['id'], 'e') !== false) {
                    $cat['id'] = number_format((float) $cat['id'], 0, '', '');
                }
            }
            
            return $cats;
        });
        
        // Build full paths
        $categories = $this->buildFullPaths($categories);
        
        return view('merchant.pages.categories.index', compact('categories'));
    }
    
    protected function buildFullPaths(array $categories): array
    {
        // Create name to category mapping
        $map = [];
        foreach ($categories as $cat) {
            $map[$cat['name']] = $cat;
        }
        
        foreach ($categories as &$cat) {
            if (empty($cat['parent'])) {
                $cat['csv_name'] = $cat['name'];
            } else {
                $path = [$cat['name']];
                $parent = $cat['parent'];
                $loop = 0;
                
                while ($parent && $loop < 10) {
                    array_unshift($path, $parent);
                    $parent = $map[$parent]['parent'] ?? null;
                    $loop++;
                }
                
                $cat['csv_name'] = implode(' > ', $path);
            }
        }
        
        // Sort by path
        usort($categories, function($a, $b) {
            return strcmp($a['csv_name'], $b['csv_name']);
        });
        
        return $categories;
    }
    
    public function export()
    {
        $categories = Cache::remember('cyclos_categories', 3600, function () {
            $cats = $this->service->getCategories();
            foreach ($cats as &$cat) {
                $cat['id'] = (string) $cat['id'];
            }
            return $cats;
        });
        
        $categories = $this->buildFullPaths($categories);
        
        $filename = 'categories_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}"
        ];
        
        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            // Only Category Path and Internal Name (NO ID)
            fputcsv($file, ['Category Path (Use in CSV)', 'Internal Name']);
            
            foreach ($categories as $cat) {
                fputcsv($file, [
                    $cat['csv_name'],
                    $cat['internalName'] ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function refresh(Request $request)
    {
        Cache::forget('cyclos_categories');
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->route('merchant.categories.index')->with('success', 'Categories refreshed!');
    }
}