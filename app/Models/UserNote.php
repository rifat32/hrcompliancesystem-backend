<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'description',
        // 'hidden_note',
        'history',
        'created_by'
    ];


    public function mentions()
    {
        return $this->hasMany(UserNoteMention::class, 'user_note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,"user_id","id");
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by',"id");
    }

   
  // Convert history from JSON to array
  public function getHistoryAttribute($value)
  {
      return $this->created_by == auth()->user()->id ? json_decode($value, true) : null;
  }

  // Convert history from array to JSON before saving
  public function setHistoryAttribute($value)
  {
      $this->attributes['history'] = json_encode($value);
  }

  // Function to update history when saving changes
  public function updateHistory(array $changes)
  {
      // Exclude 'hidden_note' from changes
    //   unset($changes['hidden_note']);

      $history = $this->history ?? [];
      $history[] = $changes;
      $this->update(['history' => $history]);
  }


}
