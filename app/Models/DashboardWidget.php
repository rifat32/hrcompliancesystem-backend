<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasFactory;
    protected $fillable = ['widget_name', 'widget_order', 'user_id'];

    // Define the relationship with the 'payruns' table
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   

}
