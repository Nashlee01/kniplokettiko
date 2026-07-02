<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adres extends Model
{
    use HasFactory;

    protected $table = 'adressen';

    protected $fillable = [
        'klant_id',
        'straatnaam',
        'huisnummer',
        'postcode',
        'plaats',
    ];

    public function klant(): BelongsTo
    {
        return $this->belongsTo(Klant::class, 'klant_id');
    }
}
