<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Client\RegistrationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Models\State;
use App\Services\ClientService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'registration_type']);
        $filters['with'] = ['state']; // Eager load state for the location column
        
        // Handle dynamic pagination limit (50, 100, 200)
        $perPage = (int) $request->input('per_page', 50);
        $perPage = in_array($perPage, [50, 100, 200]) ? $perPage : 50;

        $clients = $this->clientService->getPaginatedClients($filters, $perPage)
                                       ->withQueryString();

        // Pass states and enum options to the view for the dropdowns
        $states = State::where('is_active', true)->orderBy('name')->get();
        $registrationTypes = RegistrationType::options();

        return view('admin.clients.index', [
            'clients' => $clients,
            'states' => $states,
            'registrationTypes' => $registrationTypes,
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? null,
            'registrationType' => $filters['registration_type'] ?? null,
            'perPage' => $perPage,
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        
        // Set default enum value if not provided
        $validated['registration_type'] = $validated['registration_type'] ?? RegistrationType::UNREGISTERED->value;        

        $client = $this->clientService->createClient($validated);

        // Handle AJAX/API responses (e.g., from an Invoice Create Modal)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Client added successfully',
                'client' => $client,
            ]);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Client created successfully!');
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $validated = $request->validated();                

        $this->clientService->updateClient($client, $validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Client updated successfully!']);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Client updated successfully!');
    }

    public function ajaxSearch(Request $request)
    {
        // Tenantable restricts this automatically
        $query = Client::query();

        if ($request->filled('term')) {
            $term = $request->term;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%");
            });
        }

        $clients = $query->latest()->take($request->input('limit', 15))->get();

        $results = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'company_name' => $client->company_name,
                'city' => $client->city,
                'display_text' => $client->name . ($client->phone ? " ({$client->phone})" : ''),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $filters = $request->only(['search', 'status', 'registration_type']);
        $filters['with'] = ['state']; // Eager load 'state' for the PDF loop

        $clients = $this->clientService->getAllClients($filters);
        
        $generatedAt = now()->format('d-M-Y h:i A');

        $pdf = Pdf::loadView('admin.clients.pdf', [
            'clients' => $clients,
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? null,
            'registrationType' => $filters['registration_type'] ?? null,
            'generatedAt' => $generatedAt
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('clients-export-' . now()->format('Y-m-d') . '.pdf');
    }

    public function destroy(Client $client)
    {
        $this->clientService->deleteClient($client);

        return redirect()->route('admin.clients.index')->with('success', 'Client deleted successfully.');
    }
    /**
     * Delete the selected clients.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        try {
            $deleted = $this->clientService->bulkDelete($validated['ids']);
            $requested = count($validated['ids']);

            $message = $deleted === $requested
                ? "{$deleted} client(s) deleted successfully."
                : "{$deleted} of {$requested} client(s) deleted. The rest could not be removed.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not delete the selected clients.',
            ], 422);
        }
    }

    /**
     * Generate and download a CSV list of filtered clients.
     */
    public function downloadCsv(Request $request)
    {
        $filters = $request->only(['search', 'status', 'registration_type']);
        $filters['with'] = ['state']; 

        // Gets ALL matching clients across all pages
        $clients = $this->clientService->getAllClients($filters); 

        $filename = 'clients-export-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ["Name", "Email", "Phone", "Company", "GSTIN", "Address", "City", "State", "Zip Code", "Country"];

        $callback = function() use($clients, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($clients as $client) {
                $row = [
                    $client->name,
                    $client->email,
                    $client->phone,
                    $client->company_name,
                    $client->gst_number,
                    $client->address,
                    $client->city,
                    $client->state ? $client->state->name : '',
                    $client->zip_code,
                    $client->country,                                        
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}