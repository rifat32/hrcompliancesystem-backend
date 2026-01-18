<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'file_name',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function creator() {
        return $this->belongsTo(User::class, "created_by","id");
    }

    

    // Define your model properties and relationships here

protected static function boot()
{
    parent::boot();

    // Listen for the "deleting" event on the Candidate model
    static::deleting(function($item) {
        // Call the deleteFiles method to delete associated files
        $item->deleteFiles();
    });
}

/**
 * Delete associated files.
 *
 * @return void
 */



public function deleteFiles()
{
    // Get the file paths associated with the candidate
    $filePaths = [$this->file_name];

    // Iterate over each file and delete it
    foreach ($filePaths as $filePath) {
        if (File::exists(public_path($filePath))) {
            File::delete(public_path($filePath));
        }
    }
}



























}
