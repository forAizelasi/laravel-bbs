<?php

namespace App\Models;

class Reply extends Model
{
    protected $fillable = ['content'];

    public function topic(Topic $topic)
    {
        return $this->belongsTo (Topic::class);
    }

    public function user()
    {
        return $this->belongsTo (User::class);
    }
}
