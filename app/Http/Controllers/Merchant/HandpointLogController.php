<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\MerchantHandpointLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class HandpointLogController extends Controller
{
    protected MerchantHandpointLogService $handpointLogService;

    public function __construct(MerchantHandpointLogService $handpointLogService)
    {
        $this->handpointLogService = $handpointLogService;
    }

    /**
     * Display a listing of handpoint transaction logs for the authenticated merchant.
     */
    public function index(Request $request): View|JsonResponse
    {
        $logs = $this->handpointLogService->getLogs();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        }

        return view('merchant.pages.handpoint-logs.index', compact('logs'));
    }

    /**
     * Display the specified handpoint transaction log.
     */
    public function show($id): View
    {
        $log = $this->handpointLogService->getLogById($id);

        if (!$log) {
            abort(404, 'Transaction log not found');
        }

        return view('merchant.pages.handpoint-logs.view', compact('log'));
    }
}
