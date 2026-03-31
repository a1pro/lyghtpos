<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductImportRequest;
use App\Models\MerchantProductImport;
use App\Services\Merchant\MerchantProductImportService;
use Illuminate\Support\Facades\Auth;

class MerchantProductImportController extends Controller
{
    protected $service;

    public function __construct(MerchantProductImportService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $imports = MerchantProductImport::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('merchant.pages.products.import-list', compact('imports'));
    }


    public function create()
    {
        return view('merchant.pages.products.import');
    }

    public function store(ProductImportRequest $request)
    {
        try {
            $file = $request->file('file');

            $result = $this->service->upload($file);

            MerchantProductImport::create([
                'user_id' => Auth::id(),
                'cyclos_import_id' => $result['id'],
                'file_name' => $file->getClientOriginalName()
            ]);

            return redirect()
                ->route('merchant.products.import.show', $result['id'])
                ->with('success', 'File uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $data = $this->service->getImportDetails($id);

        return view('merchant.pages.products.import-result', compact('data'));
    }
}