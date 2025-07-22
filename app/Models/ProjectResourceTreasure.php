<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectResourceTreasure extends Model
{
    use HasFactory;

    protected $table = 'project_resource_treasure';

    protected $fillable = [
        'project_id',
        'resource_id',
        'allocated_treasure_hours',
        'treasure_hourly_rate',
        'treasure_total_cost'
    ];

    protected $casts = [
        'allocated_treasure_hours' => 'decimal:2',
        'treasure_hourly_rate' => 'decimal:2',
        'treasure_total_cost' => 'decimal:2'
    ];

    /**
     * Observer per aggiornare automaticamente il costo totale
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($allocation) {
            // Calcola automaticamente il costo totale
            $allocation->treasure_total_cost = $allocation->allocated_treasure_hours * $allocation->treasure_hourly_rate;
        });

        static::deleted(function ($allocation) {
            // Quando un'allocazione viene eliminata, aggiorna le ore disponibili della risorsa
            $allocation->resource->updateAvailableTreasureHours();
        });
    }

    /**
     * Relazione con il progetto
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relazione con la risorsa
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Scope per ottenere allocazioni per progetto
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope per ottenere allocazioni per risorsa
     */
    public function scopeForResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Calcola il costo totale delle allocazioni per un progetto
     */
    public static function getTotalCostForProject($projectId)
    {
        return static::where('project_id', $projectId)->sum('treasure_total_cost');
    }

    /**
     * Calcola le ore totali allocate per un progetto
     */
    public static function getTotalHoursForProject($projectId)
    {
        return static::where('project_id', $projectId)->sum('allocated_treasure_hours');
    }

    /**
     * Calcola le ore totali allocate per una risorsa
     */
    public static function getTotalHoursForResource($resourceId)
    {
        return static::where('resource_id', $resourceId)->sum('allocated_treasure_hours');
    }
}