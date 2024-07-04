<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    protected $table = 'todo';

    protected $fillable = [
        'activity',
        'keterangan',
        'jenisKunjungan',
        'debiturName',
        'kolektibilitas',
        'namaAO', // Pastikan namaAO di sini sesuai dengan kolom di database
        'unique'
    ];
}
