@php($title = 'Products')
@extends('layouts.app')
@section('content')
<div class="card">
    <div class="kpi">
        <div class="item"><div class="label">Total Products</div><div class="value">{{ $products->total() }}</div></div>
        <div class="item"><div class="label">Page</div><div class="value">{{ $products->currentPage() }}/{{ $products->lastPage() }}</div></div>
        <div class="item"><div class="label">Per Page</div><div class="value">{{ $products->perPage() }}</div></div>
    </div>
</div>

<div class="card">
    <h2>Listing</h2>
    <table>
        <thead>
        <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Incoming</th>
            <th>Supplier</th>
        </tr>
        </thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td class="muted">{{ $p->sku }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->price ? number_format($p->price,2) : '-' }} {{ $p->currency }}</td>
                <td>{{ $p->stock }}</td>
                <td>{{ $p->incoming_stock }}</td>
                <td>{{ $p->supplier_name ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">No products yet. Try syncing a supplier feed.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-12">{{ $products->links() }}</div>
    <div class="mt-12"><a class="btn btn-primary" href="{{ route('products.sync.form') }}">Sync Supplier Feed</a></div>
 </div>
@endsection