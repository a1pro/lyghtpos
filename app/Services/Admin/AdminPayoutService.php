<?php
// app/Services/Admin/AdminPayoutService.php

namespace App\Services\Admin;

use App\Models\Payout;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminPayoutService
{
    /**
     * Get all payouts with merchant details
     */
    public function getAllPayouts(array $filters = [])
    {
        $query = Payout::with(['merchant' => function($q) {
            $q->select('id', 'first_name', 'last_name', 'email', 'cyclos_user_id', 'wallet_amount');
        }])->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('requested_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('requested_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('payout_reference', 'like', "%{$search}%")
                  ->orWhereHas('merchant', function($sub) use ($search) {
                      $sub->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get payout statistics
     */
    public function getPayoutStatistics(): array
    {
        return [
            'total_payouts' => Payout::count(),
            'pending_payouts' => Payout::where('status', 'pending')->count(),
            'processing_payouts' => Payout::where('status', 'processing')->count(),
            'completed_payouts' => Payout::where('status', 'completed')->count(),
            'failed_payouts' => Payout::where('status', 'failed')->count(),
            'total_amount_pending' => Payout::where('status', 'pending')->sum('amount'),
            'total_amount_processing' => Payout::where('status', 'processing')->sum('amount'),
            'total_amount_completed' => Payout::where('status', 'completed')->sum('amount'),
            'total_amount_failed' => Payout::where('status', 'failed')->sum('amount'),
            'total_fees_collected' => Payout::where('status', 'completed')->sum('fee'),
        ];
    }

    /**
     * Get single payout details
     */
    public function getPayoutDetails(int $payoutId): ?Payout
    {
        return Payout::with(['merchant' => function($q) {
            $q->select('id', 'first_name', 'last_name', 'email', 'cyclos_user_id',  'wallet_amount', 'created_at');
        }, 'processor'])->find($payoutId);
    }

    /**
     * Update payout status - Fixed version
     */
    public function updatePayoutStatus(int $payoutId, array $data): array
    {
        try {
            DB::beginTransaction();

            $payout = Payout::with('merchant')->findOrFail($payoutId);
            $oldStatus = $payout->status;
            $newStatus = $data['status'];

            // Prepare update data
            $updateData = [
                'status' => $newStatus,
                'processed_at' => now(),
                'processed_notes' => $data['processed_notes'] ?? null,
                'processed_by' => auth()->id(),
            ];

            if ($newStatus === 'completed') {
                $updateData['completed_at'] = now();
                
                // Prepare payment details
                $paymentDetails = $payout->payment_details ?? [];
                $paymentDetails['transaction_id'] = $data['transaction_id'] ?? null;
                $paymentDetails['completed_by'] = auth()->user()->email;
                $paymentDetails['completed_at'] = now()->toDateTimeString();
                $updateData['payment_details'] = $paymentDetails;
            }

            if ($newStatus === 'failed') {
                $updateData['failure_reason'] = $data['failure_reason'] ?? null;
                
                // Prepare payment details
                $paymentDetails = $payout->payment_details ?? [];
                $paymentDetails['failed_by'] = auth()->user()->email;
                $paymentDetails['failed_at'] = now()->toDateTimeString();
                $paymentDetails['failure_reason'] = $data['failure_reason'] ?? null;
                $updateData['payment_details'] = $paymentDetails;
            }

            // Update payout
            $payout->update($updateData);

            // Create notification for merchant
            $this->createPayoutNotification($payout, $oldStatus, $newStatus);

            DB::commit();

            Log::info('Payout status updated', [
                'payout_id' => $payoutId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'processed_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "Payout status updated to " . ucfirst($newStatus) . " successfully",
                'payout' => $payout,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payout status', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update payout status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create notification for merchant about payout status change
     */
    protected function createPayoutNotification(Payout $payout, string $oldStatus, string $newStatus): void
    {
        $statusMessages = [
            'processing' => "Your payout request #{$payout->payout_reference} of $" . number_format($payout->amount, 2) . " is now being processed.",
            'completed' => "Your payout request #{$payout->payout_reference} of $" . number_format($payout->amount, 2) . " has been completed successfully.",
            'failed' => "Your payout request #{$payout->payout_reference} of $" . number_format($payout->amount, 2) . " has failed. Reason: " . ($payout->failure_reason ?? 'Please contact support for details.'),
        ];

        $message = $statusMessages[$newStatus] ?? "Your payout request #{$payout->payout_reference} status has been updated to " . ucfirst($newStatus);

        Notification::create([
            'user_id' => $payout->merchant_id,
            'message' => $message,
            'type' => 'payout',
            'related_id' => $payout->id,
            'status' => $newStatus === 'failed' ? 'error' : 'info',
        ]);
    }

    /**
     * Bulk update payouts
     */
    public function bulkUpdateStatus(array $payoutIds, string $status, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($payoutIds as $payoutId) {
                $result = $this->updatePayoutStatus($payoutId, array_merge($data, ['status' => $status]));
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Payout #{$payoutId}: " . $result['message'];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Updated {$successCount} payouts successfully. {$failedCount} failed.",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Export payouts to CSV
     */
    public function exportPayouts(array $filters = [])
    {
        $payouts = $this->getAllPayouts($filters)->getCollection();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payouts-' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($payouts) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            
            // Headers
            fputcsv($file, [
                'ID',
                'Reference',
                'Merchant Name',
                'Merchant Email',
                'Amount',
                'Payment Method',
                'Status',
                'Requested Date',
                'Processed Date',
                'Completed Date',
                'Transaction ID',
                'Notes',
            ]);
            
            // Data rows
            foreach ($payouts as $payout) {
                $transactionId = $payout->payment_details['transaction_id'] ?? '';
                
                fputcsv($file, [
                    $payout->id,
                    $payout->payout_reference,
                    $payout->merchant ? $payout->merchant->first_name . ' ' . $payout->merchant->last_name : 'N/A',
                    $payout->merchant ? $payout->merchant->email : 'N/A',
                    number_format($payout->amount, 2),
                    strtoupper(str_replace('_', ' ', $payout->payment_method)),
                    ucfirst($payout->status),
                    $payout->requested_at ? $payout->requested_at->format('Y-m-d H:i:s') : 'N/A',
                    $payout->processed_at ? $payout->processed_at->format('Y-m-d H:i:s') : 'N/A',
                    $payout->completed_at ? $payout->completed_at->format('Y-m-d H:i:s') : 'N/A',
                    $transactionId,
                    $payout->notes ?? '',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}