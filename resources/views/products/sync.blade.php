@php($title = 'Supplier Sync')
@extends('layouts.app')
@section('content')
<div class="grid">
    <div class="card">
        <h2>Import via CSV</h2>
        <p class="muted">Upload a supplier CSV file. Duplicate SKUs are aggregated and upserted.</p>
        <form method="post" action="{{ route('products.sync.csv') }}" enctype="multipart/form-data" class="mt-12">
            @csrf
            <div class="form-group mb-8">
                <label>CSV File</label>
                <input type="file" name="csv" accept=".csv,.txt" required>
                @error('csv')<div class="alert alert-error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Process CSV</button>
        </form>
    </div>

    <div class="card">
        <h2>Fetch via API</h2>
        <form method="post" action="{{ route('products.sync.api') }}" class="mt-12">
            @csrf
            <div class="form-group mb-8">
                <label>API URL</label>
                <input type="url" name="api_url" placeholder="https://supplier.example.com/feed" required>
                @error('api_url')<div class="alert alert-error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Fetch API</button>
        </form>
    </div>
</div>

@if(session('sync_result'))
    @php($r = session('sync_result'))
    <div class="card">
        <h2>Sync Summary</h2>
        <div class="kpi">
            <div class="item"><div class="label">Source</div><div class="value">{{ $r['source'] }}</div></div>
            <div class="item"><div class="label">Total</div><div class="value">{{ $r['total'] }}</div></div>
            <div class="item"><div class="label">Inserted</div><div class="value">{{ $r['inserted'] }}</div></div>
            <div class="item"><div class="label">Updated</div><div class="value">{{ $r['updated'] }}</div></div>
        </div>
        @if(!empty($r['errors']))
            <div class="alert alert-error mt-12">
                <strong>Errors:</strong>
                <ul>
                    @foreach($r['errors'] as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="alert alert-success mt-12">No validation errors.</div>
        @endif
        <div class="mt-12"><a class="btn" href="{{ route('products.index') }}">View Products</a></div>
    </div>
@endif
@endsection