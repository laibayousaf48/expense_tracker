<?php

namespace App\Models;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    // protected $fillable = ['user_id', 'category_id', 'amount'];
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
