<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Services\File\FileAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientDeletionService
{
    public function __construct(
        private FileAttachmentService $fileAttachmentService
    ) {}

    /**
     * Soft delete a client
     */
    public function softDelete(Client $client, int $deletedBy): bool
    {
        DB::beginTransaction();

        try {
            Log::info('=== CLIENT SOFT DELETE START ===', [
                'client_id' => $client->id,
                'vendor_id' => $client->vendor_id,
                'business_name' => $client->business_name,
                'has_logo' => !empty($client->logo_path),
                'logo_path' => $client->logo_path,
                'deleted_by' => $deletedBy,
            ]);

            // Mark as deleted but keep the record and logo file
            $client->update([
                'deleted_by' => $deletedBy,
                'deleted_at' => now(),
            ]);
            
            $client->delete();

            Log::info('Client soft deleted', [
                'client_id' => $client->id,
                'vendor_id' => $client->vendor_id,
                'business_name' => $client->business_name,
                'deleted_by' => $deletedBy,
                'logo_preserved' => true,
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to soft delete client', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Force delete a client (permanent)
     */
    public function forceDelete(Client $client, int $deletedBy): bool
    {
        DB::beginTransaction();

        try {
            Log::info('=== CLIENT FORCE DELETE START ===', [
                'client_id' => $client->id,
                'vendor_id' => $client->vendor_id,
                'business_name' => $client->business_name,
                'has_logo' => !empty($client->logo_path),
                'logo_path' => $client->logo_path,
                'deleted_by' => $deletedBy,
            ]);

            // Delete associated logo file from private storage
            $this->deleteClientLogo($client);
            
            // Permanently delete the client record
            $client->forceDelete();

            Log::warning('Client permanently deleted', [
                'client_id' => $client->id,
                'vendor_id' => $client->vendor_id,
                'business_name' => $client->business_name,
                'deleted_by' => $deletedBy,
                'logo_deleted' => !empty($client->logo_path),
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to permanently delete client', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Restore a soft deleted client
     */
    public function restore(int $vendorId, int $clientId, int $restoredBy): Client
    {
        DB::beginTransaction();

        try {
            Log::info('=== CLIENT RESTORE START ===', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'restored_by' => $restoredBy,
            ]);

            $client = Client::withTrashed()
                ->where('vendor_id', $vendorId)
                ->where('id', $clientId)
                ->first();

            if (!$client) {
                throw new \Exception('Client not found or not soft deleted');
            }

            $client->restore();
            
            // Clear deleted_by and update updated_by
            $client->update([
                'deleted_by' => null,
                'updated_by' => $restoredBy,
            ]);

            Log::info('Client restored', [
                'client_id' => $client->id,
                'vendor_id' => $vendorId,
                'business_name' => $client->business_name,
                'restored_by' => $restoredBy,
                'has_logo' => !empty($client->logo_path),
            ]);

            DB::commit();
            return $client->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore client', [
                'vendor_id' => $vendorId,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'restored_by' => $restoredBy,
            ]);
            throw $e;
        }
    }

    /**
     * Delete all clients for a vendor (soft delete)
     */
    public function deleteAllForVendor(int $vendorId, int $deletedBy): int
    {
        DB::beginTransaction();

        try {
            Log::info('=== DELETE ALL CLIENTS FOR VENDOR START ===', [
                'vendor_id' => $vendorId,
                'deleted_by' => $deletedBy,
            ]);

            $clients = Client::where('vendor_id', $vendorId)->get();
            $count = 0;

            foreach ($clients as $client) {
                $client->update([
                    'deleted_by' => $deletedBy,
                    'deleted_at' => now(),
                ]);
                $client->delete();
                $count++;
            }

            Log::warning('All clients soft deleted for vendor', [
                'vendor_id' => $vendorId,
                'client_count' => $count,
                'deleted_by' => $deletedBy,
                'logos_preserved' => true,
            ]);

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete all clients for vendor', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Force delete all clients for a vendor (permanent)
     */
    public function forceDeleteAllForVendor(int $vendorId, int $deletedBy): int
    {
        DB::beginTransaction();

        try {
            Log::warning('=== FORCE DELETE ALL CLIENTS FOR VENDOR START ===', [
                'vendor_id' => $vendorId,
                'deleted_by' => $deletedBy,
            ]);

            $clients = Client::where('vendor_id', $vendorId)->get();
            $count = 0;

            foreach ($clients as $client) {
                // Delete logo file
                $this->deleteClientLogo($client);
                
                // Permanently delete client
                $client->forceDelete();
                $count++;
            }

            Log::warning('All clients permanently deleted for vendor', [
                'vendor_id' => $vendorId,
                'client_count' => $count,
                'deleted_by' => $deletedBy,
                'logos_deleted' => true,
            ]);

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to permanently delete all clients for vendor', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Check if client can be deleted
     */
    public function canDelete(Client $client): array
    {
        Log::info('Checking if client can be deleted', [
            'client_id' => $client->id,
            'business_name' => $client->business_name,
        ]);

        $canDelete = true;
        $dependencies = [];
        $message = 'Client can be deleted';

        // Check for invoices
        if (method_exists($client, 'invoices') && $client->invoices()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'invoices';
        }

        // Check for projects
        if (method_exists($client, 'projects') && $client->projects()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'projects';
        }

        // Check for payments
        if (method_exists($client, 'payments') && $client->payments()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'payments';
        }

        // Check for contracts
        if (method_exists($client, 'contracts') && $client->contracts()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'contracts';
        }

        // Check for appointments
        if (method_exists($client, 'appointments') && $client->appointments()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'appointments';
        }

        // Check for support tickets
        if (method_exists($client, 'supportTickets') && $client->supportTickets()->count() > 0) {
            $canDelete = false;
            $dependencies[] = 'support_tickets';
        }

        if (!$canDelete) {
            $dependencyCount = count($dependencies);
            $message = "Cannot delete client. It has {$dependencyCount} active dependency(ies): " . 
                      implode(', ', $dependencies) . 
                      ". Please resolve or archive these items first.";
        }

        return [
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'dependency_count' => count($dependencies),
            'message' => $message,
        ];
    }

    /**
     * Delete client logo file from private storage
     */
    private function deleteClientLogo(Client $client): void
    {
        if (empty($client->logo_path)) {
            Log::info('No logo to delete for client', [
                'client_id' => $client->id,
                'logo_path' => $client->logo_path,
            ]);
            return;
        }

        try {
            Log::info('Attempting to delete client logo', [
                'client_id' => $client->id,
                'logo_path' => $client->logo_path,
            ]);

            // Use the reusable file attachment service to delete the logo
            $deleted = $this->fileAttachmentService->deleteFile($client->logo_path);
            
            if ($deleted) {
                Log::info('Client logo deleted successfully', [
                    'client_id' => $client->id,
                    'logo_path' => $client->logo_path,
                ]);
            } else {
                Log::warning('Client logo not found or already deleted', [
                    'client_id' => $client->id,
                    'logo_path' => $client->logo_path,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete client logo', [
                'client_id' => $client->id,
                'logo_path' => $client->logo_path,
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception - logo deletion failure shouldn't prevent client deletion
            // Just log the error and continue
        }
    }

    /**
     * Bulk soft delete multiple clients
     */
    public function bulkSoftDelete(array $clientIds, int $deletedBy): int
    {
        DB::beginTransaction();

        try {
            Log::info('=== BULK SOFT DELETE CLIENTS START ===', [
                'client_ids' => $clientIds,
                'client_count' => count($clientIds),
                'deleted_by' => $deletedBy,
            ]);

            $count = 0;
            $clients = Client::whereIn('id', $clientIds)->get();

            foreach ($clients as $client) {
                $client->update([
                    'deleted_by' => $deletedBy,
                    'deleted_at' => now(),
                ]);
                $client->delete();
                $count++;
            }

            Log::info('Bulk soft delete completed', [
                'client_count' => $count,
                'deleted_by' => $deletedBy,
            ]);

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk soft delete clients', [
                'client_ids' => $clientIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk force delete multiple clients
     */
    public function bulkForceDelete(array $clientIds, int $deletedBy): int
    {
        DB::beginTransaction();

        try {
            Log::warning('=== BULK FORCE DELETE CLIENTS START ===', [
                'client_ids' => $clientIds,
                'client_count' => count($clientIds),
                'deleted_by' => $deletedBy,
            ]);

            $count = 0;
            $clients = Client::whereIn('id', $clientIds)->get();

            foreach ($clients as $client) {
                // Delete logo file
                $this->deleteClientLogo($client);
                
                // Permanently delete client
                $client->forceDelete();
                $count++;
            }

            Log::warning('Bulk force delete completed', [
                'client_count' => $count,
                'deleted_by' => $deletedBy,
                'logos_deleted' => true,
            ]);

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk force delete clients', [
                'client_ids' => $clientIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'deleted_by' => $deletedBy,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk restore multiple clients
     */
    public function bulkRestore(array $clientIds, int $restoredBy): int
    {
        DB::beginTransaction();

        try {
            Log::info('=== BULK RESTORE CLIENTS START ===', [
                'client_ids' => $clientIds,
                'client_count' => count($clientIds),
                'restored_by' => $restoredBy,
            ]);

            $count = 0;
            $clients = Client::withTrashed()->whereIn('id', $clientIds)->get();

            foreach ($clients as $client) {
                $client->restore();
                $client->update([
                    'deleted_by' => null,
                    'updated_by' => $restoredBy,
                ]);
                $count++;
            }

            Log::info('Bulk restore completed', [
                'client_count' => $count,
                'restored_by' => $restoredBy,
            ]);

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk restore clients', [
                'client_ids' => $clientIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'restored_by' => $restoredBy,
            ]);
            throw $e;
        }
    }

    /**
     * Get all soft deleted clients for a vendor
     */
    public function getSoftDeletedClients(int $vendorId, array $filters = [], int $perPage = 15)
    {
        $query = Client::withTrashed()
            ->where('vendor_id', $vendorId)
            ->whereNotNull('deleted_at');

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['deleted_from'])) {
            $query->where('deleted_at', '>=', $filters['deleted_from']);
        }

        if (!empty($filters['deleted_to'])) {
            $query->where('deleted_at', '<=', $filters['deleted_to']);
        }

        // Order by deletion date (newest first)
        $query->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get deletion statistics for a vendor
     */
    public function getDeletionStatistics(int $vendorId): array
    {
        $totalClients = Client::where('vendor_id', $vendorId)->withTrashed()->count();
        $activeClients = Client::where('vendor_id', $vendorId)->count();
        $deletedClients = Client::where('vendor_id', $vendorId)->onlyTrashed()->count();
        
        $recentlyDeleted = Client::where('vendor_id', $vendorId)
            ->onlyTrashed()
            ->where('deleted_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'deleted_clients' => $deletedClients,
            'deletion_rate_percentage' => $totalClients > 0 ? round(($deletedClients / $totalClients) * 100, 2) : 0,
            'recently_deleted_last_30_days' => $recentlyDeleted,
        ];
    }
}