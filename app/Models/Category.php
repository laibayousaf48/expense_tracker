<?php

namespace App\Models;

use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    // protected $fillable = ['name', 'user_id'];
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function budget()
    {
        return $this->hasOne(Budget::class);
    }
}
