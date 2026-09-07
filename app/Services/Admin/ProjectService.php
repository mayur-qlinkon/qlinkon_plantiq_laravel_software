<?php

namespace App\Services\Admin;

use App\Models\Project;
use App\Models\ProjectRenewal;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProjectService
{
    /**
     * Create a new project or service.
     */
    public function createProject(array $data): Project
    {
        try {
            DB::beginTransaction();

            $project = Project::create($data);

            DB::commit();
            
            Log::info("Project created successfully.", ['project_id' => $project->id, 'type' => $project->type]);
            
            return $project;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to create project: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Update an existing project or service.
     */
    public function updateProject(Project $project, array $data): Project
    {
        try {
            DB::beginTransaction();

            $project->update($data);

            DB::commit();

            Log::info("Project updated successfully.", ['project_id' => $project->id]);

            return $project;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update project [ID: {$project->id}]: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Process a renewal for a 'service' type project.
     */
    public function renewService(Project $project, array $data): ProjectRenewal
    {
        if ($project->type !== 'service' || !$project->is_renewable) {
            throw new Exception("This record is not a renewable service.");
        }

        try {
            DB::beginTransaction();

            $renewal = $project->renewals()->create([
                'company_id'   => $project->company_id,
                'store_id'     => $project->store_id,
                'renewal_date' => now(),
                'cost'         => $data['cost'] ?? $project->invoice_price,
                'expiry_date'  => $data['expiry_date'],
                'notes'        => $data['notes'] ?? null,
            ]);

            $project->update([
                'invoice_price' => $data['cost'] ?? $project->invoice_price,
                'expiry_date'   => $data['expiry_date'],
                'stage'         => 'active',
                'notes'         => $data['notes'] ?? $project->notes,
            ]);

            DB::commit();

            Log::info("Service renewed successfully.", ['project_id' => $project->id, 'renewal_id' => $renewal->id]);

            return $renewal;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to renew service [ID: {$project->id}]: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Add a polymorphic payment to the project/service.
     */
    public function recordPayment(Project $project, array $paymentData)
    {
        try {
            DB::beginTransaction();

            $dueAmount = $project->due_amount;
            if ($paymentData['amount'] > $dueAmount && $dueAmount > 0) {
                throw new Exception("Payment amount exceeds the due balance. Due: {$dueAmount}");
            }

            // Auto-assign default payment method if UI doesn't provide one
            $paymentMethodId = $paymentData['payment_method_id'] ?? DB::table('payment_methods')
                ->where('company_id', $project->company_id)->value('id') ?? 1;

            // Projects aren't store-scoped (no store_id column on `projects`),
            // but `payments.store_id` is a required FK — resolve a fallback
            // store the same way PaymentService does for guest/no-store documents.
            $storeId = Store::where('company_id', $project->company_id)
                ->where('is_active', true)->value('id')
                ?? Store::where('company_id', $project->company_id)->value('id');

            $payment = $project->payments()->create([
                'company_id'        => $project->company_id,
                'store_id'          => $storeId,
                'created_by'        => Auth::id(),
                'payment_method_id' => $paymentMethodId,
                'party_type'        => 'customer',
                'party_id'          => $project->client_id,
                'payment_number'    => $this->generatePaymentNumber($project->company_id),
                'reference'         => $paymentData['reference'] ?? null,
                'payment_date'      => $paymentData['payment_date'] ?? now(),
                'type'              => 'received',
                'amount'            => $paymentData['amount'],
                'amount_received'   => $paymentData['amount'],
                'status'            => 'completed',
                'payment_for'       => $project->type === 'service' ? 'Service Renewal/Subscription' : 'Project Invoice',
                'notes'             => $paymentData['notes'] ?? null,
            ]);

            // Auto-update stage
            if ($project->fresh()->due_amount <= 0 && in_array($project->stage, ['starting', 'running'])) {
                $project->update(['stage' => 'done']);
            }

            DB::commit();

            Log::info("Payment recorded successfully for project.", ['project_id' => $project->id, 'payment_id' => $payment->id]);

            return $payment;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Payment failed for project [ID: {$project->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a payment and revert stages if necessary.
     */
    public function deletePayment(Project $project, $paymentId): void
    {
        try {
            DB::beginTransaction();

            $payment = $project->payments()->findOrFail($paymentId);
            $payment->delete();

            // Revert project stage if it was 'done' but now owes money
            if ($project->fresh()->due_amount > 0 && $project->stage === 'done') {
                $project->update(['stage' => 'running']);
            }

            DB::commit();
            Log::info("Project payment deleted successfully.", ['project_id' => $project->id, 'payment_id' => $paymentId]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete payment [ID: {$paymentId}] for project [ID: {$project->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safely delete the project and log the action.
     */
    public function deleteProject(Project $project): bool
    {
        try {
            $projectId = $project->id;
            $deleted = $project->delete();
            Log::info("Project/Service deleted successfully.", ['project_id' => $projectId]);
            return $deleted;
        } catch (Exception $e) {
            Log::error("Failed to delete project [ID: {$project->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePaymentNumber($companyId): string
    {
        $prefix = 'PAY-' . date('Y') . '-';
        $lastPayment = DB::table('payments')
            ->where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastPayment) return $prefix . '0001';

        $lastNumber = (int) substr($lastPayment->payment_number, -4);
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}