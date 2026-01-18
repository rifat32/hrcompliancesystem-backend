<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'parent_comment_id',
        'description',
        'attachments',
        'status',
        'priority',
        'visibility',
        'tags',
        'resolution',
        'feedback',
        'hidden_note',
        'related_task_id',
        'task_id',
        'project_id',
        'type',
        'created_by',

    ];

    protected $casts = [
        'attachments' => 'array',
        'feedback' => 'array',
    ];

    public function recursiveChildren()
    {
        return $this->children()->with(
            [
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        "users.title",
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },

                "mentioned_users" => function ($query) {
                    $query->select(
                        'users.id',
                        "users.title",
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "recursiveChildren"


        ]);
    }
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_comment_id', 'id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function related_task()
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function mentions()
    {
        return $this->hasMany(TaskCommentMention::class,'comment_id','id');
    }


    public function mentioned_users() {
        return $this->belongsToMany(User::class, 'task_comment_mentions', 'comment_id', 'user_id');
    }




    public function getHiddenNoteAttribute()
    {
        $authenticatedUserId = auth()->user()->id;

        // Check if either 'created_by' or 'user_id' matches the authenticated user's ID
        if ($this->created_by == $authenticatedUserId || $this->user_id == $authenticatedUserId) {
            return $this->attributes['hidden_note'];
        }

        return null;
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
