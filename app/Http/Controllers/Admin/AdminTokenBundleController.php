<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TokenBundleRequest;
use App\Services\Admin\AdminTokenBundleService;
use DB;

class AdminTokenBundleController extends Controller
{
    protected $tokenBundleService;

    public function __construct(AdminTokenBundleService $tokenBundleService)
    {
        $this->tokenBundleService = $tokenBundleService;
    }

    public function index()
    {
        try {
            $bundles = $this->tokenBundleService->index();

            if (is_array($bundles) && isset($bundles['success']) && $bundles['success'] === false) {
                throw new \Exception($bundles['message']);
            }

            return view('admin.pages.token-bundles.index', compact('bundles'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.pages.token-bundles.create');
    }

    public function store(TokenBundleRequest $request)
    {
        try {
            DB::beginTransaction();

            $result = $this->tokenBundleService->storeTokenBundle($request->validated());

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();
            return redirect()->route('admin.token-bundles.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $result = $this->tokenBundleService->getTokenBundleById($id);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $bundle = $result['bundle'];

            return view('admin.pages.token-bundles.edit', compact('bundle'));
        } catch (\Exception $e) {
            return redirect()->route('admin.token-bundles.index')
                ->with('error', 'Bundle not found or error: ' . $e->getMessage());
        }
    }

    public function update(TokenBundleRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->tokenBundleService->updateBundle($id, $request->validated());

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();
            return redirect()->route('admin.token-bundles.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->tokenBundleService->deleteTokenBundle($id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }
}
