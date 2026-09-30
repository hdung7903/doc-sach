<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name','email','password'];
    protected $hidden = ['password','remember_token'];

    public function books() { return $this->hasMany(Book::class); }
    public function readingProgress() { return $this->hasMany(ReadingProgress::class); }
    public function bookmarks() { return $this->hasMany(Bookmark::class); }
}
