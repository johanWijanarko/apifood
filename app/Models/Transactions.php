<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Food;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transactions extends Model
{
   use HasFactory, SoftDeletes;

    protected $table = 'transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'food_id', 'user_id', 'quantity',
        'total','status','payment_url'
    ];

    public function food()
    {
        return $this->hasOne(Food::class, 'id', 'food_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function getCreatedAtAttribute($value){
        return Carbon::parse($value)->timestamp;
    }
    public function getUpdatedAtAttribute($value){
        return Carbon::parse($value)->timestamp;
    }
}
