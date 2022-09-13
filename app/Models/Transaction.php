<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'currency',
        'externalId',
        'payerMessage',
        'payeeNote',
        'sender',
        'receiver',
    ];
}
