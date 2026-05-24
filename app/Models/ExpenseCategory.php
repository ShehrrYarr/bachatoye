<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'color'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
