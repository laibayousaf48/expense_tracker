<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'amount',
        'expense_date',
        'description',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
{
    return $this->belongsTo(Category::class);
}

}
