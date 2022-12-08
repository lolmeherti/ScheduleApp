<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Task extends Model
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $primaryKey = 'id';
    protected $table = 'tasks';




    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'repeating',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'user_fid',
        'time_due',
        'date_due',
        'updated_at',
        'created_at',
    ];
}
