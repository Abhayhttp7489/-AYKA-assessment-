<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSupplierFeed;
use App\Repositories\ProductRepository;
use App\Services\SupplierProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierSyncController extends Controller
{
    public function form()
    {
        return view('products.sync');
    }

    public function importCsv(Request $request, SupplierProductSyncService $service)
    {
        $data = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $path = $request->file('csv')->store('uploads');
        $absolute = Storage::path($path);

        $result = $service->syncFromCsv($absolute);

        return back()->with('sync_result', $result);
    }

    public function fetchApi(Request $request, SupplierProductSyncService $service)
    {
        $data = $request->validate([
            'api_url' => ['required', 'url'],
            'token' => ['nullable', 'string'],
        ]);

        $result = $service->syncFromApi($data['api_url'], $data['token'] ?? null);

        return back()->with('sync_result', $result);
    }
}