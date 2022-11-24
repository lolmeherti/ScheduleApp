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

    public mixed $description;
    public string $repeating;
    public string $monday;
    public string $tuesday;
    public string $wednesday;
    public string $thursday;
    public string $friday;
    public string $saturday;
    public string $sunday;
    public string $due_date;
    public string $due_time;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'repeating',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'due_date',
        'due_time'
    ];
}
