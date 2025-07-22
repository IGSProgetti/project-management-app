<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HoursRedistribution extends Model
{
    protected $fillable = [
        'resource_id',
        'from_client_id',
        'to_client_id',
        'user_id',
        'redistribution_date',
        'hours',
        'standard_hours',    // 🆕 NUOVO
        'extra_hours',       // 🆕 NUOVO
        'hourly_rate',
        'extra_hourly_rate', // 🆕 NUOVO
        'total_value',
        'action_type',
        'notes'
    ];

    protected $casts = [
        'redistribution_date' => 'date',
        'hours' => 'decimal:2',
        'standard_hours' => 'decimal:2',    // 🆕 NUOVO
        'extra_hours' => 'decimal:2',       // 🆕 NUOVO
        'hourly_rate' => 'decimal:2',
        'extra_hourly_rate' => 'decimal:2', // 🆕 NUOVO
        'total_value' => 'decimal:2'
    ];

    /**
     * Relazione con la risorsa
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Relazione con il cliente di origine
     */
    public function fromClient()
    {
        return $this->belongsTo(Client::class, 'from_client_id');
    }

    /**
     * Relazione con il cliente di destinazione
     */
    public function toClient()
    {
        return $this->belongsTo(Client::class, 'to_client_id');
    }

    /**
     * Relazione con l'utente che ha fatto l'operazione
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope per filtrare per data
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('redistribution_date', $date);
    }

    /**
     * Scope per filtrare per risorsa
     */
    public function scopeForResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope per filtrare per cliente
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where(function($q) use ($clientId) {
            $q->where('from_client_id', $clientId)
              ->orWhere('to_client_id', $clientId);
        });
    }

    /**
     * Scope per le restituzioni
     */
    public function scopeReturns($query)
    {
        return $query->where('action_type', 'return');
    }

    /**
     * Scope per i trasferimenti
     */
    public function scopeTransfers($query)
    {
        return $query->where('action_type', 'transfer');
    }
}