<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Specify the fields that are mass assignable
    protected $fillable = [
        'barcode',
        'description',
        'price',
        'quantity',
        'category',
    ];
}
