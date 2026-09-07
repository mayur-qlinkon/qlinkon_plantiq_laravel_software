<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ClientService
{
    /**
     * Get a paginated list of clients with advanced filtering.
     */
    public function getPaginatedClients(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->latest()->paginate($perPage);
    }

    /**
     * Get an unpaginated collection of clients (useful for PDF/Excel exports).
     */
    public function getAllClients(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->latest()->get();
    }

    /**
     * Build the base query using provided filters.
     */
    private function buildQuery(array $filters)
    {
        $query = Client::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('gst_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('client_code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (!empty($filters['registration_type'])) {
            $query->where('registration_type', $filters['registration_type']);
        }

        if (!empty($filters['with'])) {
            $query->with($filters['with']); // Eager load relationships like 'state'
        }

        return $query;
    }

    /**
     * Safely create a new client using DB transactions.
     */
    public function createClient(array $data): Client
    {
        try {
            return DB::transaction(function () use ($data) {
                
                // Auto-generate client code if not provided
                if (empty($data['client_code'])) {
                    $data['client_code'] = $this->generateClientCode();
                }

                return Client::create($data);
            });
        } catch (Exception $e) {
            Log::error('Failed to create client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safely update an existing client using DB transactions.
     */
    public function updateClient(Client $client, array $data): Client
    {
        try {
            return DB::transaction(function () use ($client, $data) {
                $client->update($data);
                
                return $client->fresh();
            });
        } catch (Exception $e) {
            Log::error("Failed to update client ID {$client->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safely delete a client using DB transactions.
     */
    public function deleteClient(Client $client): bool
    {
        try {
            return DB::transaction(function () use ($client) {
                // You can add logic here to check if the client has active invoices
                // before allowing deletion, or cascade actions if necessary.
                
                return $client->delete();
            });
        } catch (Exception $e) {
            Log::error("Failed to delete client ID {$client->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete many clients in one transaction.
     *
     * IDs are re-queried through the model rather than passed straight to a
     * mass delete, so the tenant scope applies and a crafted request cannot
     * reach another company's records. Returns a count so the caller can tell
     * the user how many of their selected rows actually went.
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::transaction(function () use ($ids) {
            $clients = Client::whereIn('id', $ids)->get();

            $deleted = 0;

            foreach ($clients as $client) {
                try {
                    $client->delete();
                    $deleted++;
                } catch (Exception $e) {
                    // One failure must not roll back the rest — a client tied to
                    // invoices is a normal outcome here, not an error worth
                    // aborting the whole batch for.
                    Log::warning("Skipped client ID {$client->id} during bulk delete: ".$e->getMessage());
                }
            }

            return $deleted;
        });
    }

    /**
     * Generate a unique sequential client code.
     */
    private function generateClientCode(): string
    {
        $lastClient = Client::latest('id')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1;
        
        return 'CLI-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }
}