<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'trxId', 'terminalId', 'datetime' , 'amount',
        'feeMerchant','fromAccount','trxStatus', 'channel','billerId'
    ];
}
