<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'currency',
        'stock',
        'incoming_stock',
        'supplier_name',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'int',
        'incoming_stock' => 'int',
    ];
}