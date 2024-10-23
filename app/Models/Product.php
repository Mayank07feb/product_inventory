<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Specify the table associated with the model (optional)
    protected $table = 'products';

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'product_name',
        'quantity',
        'price',
    ];
}
