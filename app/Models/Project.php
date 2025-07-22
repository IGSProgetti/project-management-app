<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'cost_steps',
        'total_cost',
        'start_date',
        'end_date',
        'status',
        'default_hours_type',
        'created_from_tasks',
        'tasks_created_at'
    ];

    protected $casts = [
        'cost_steps' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_from_tasks' => 'boolean',
        'tasks_created_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot('hours', 'hours_type', 'adjusted_rate', 'cost')
            ->withTimestamps();
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Calcola la tariffa oraria aggiustata in base agli step di costo abilitati.
     */
    public function calculateAdjustedRate($baseRate)
    {
        $stepValues = [
            1 => 25, // Costo struttura
            2 => 12.5, // Utile gestore azienda
            3 => 12.5, // Utile IGS
            4 => 20, // Compenso professionista
            5 => 5, // Bonus professionista
            6 => 3, // Gestore società
            7 => 8, // Chi porta il lavoro
            8 => 14 // Network IGS
        ];
        
        $totalDeduction = 0;
        foreach ($stepValues as $step => $percentage) {
            if (!in_array($step, $this->cost_steps ?: [1,2,3,4,5,6,7,8])) {
                $totalDeduction += $percentage;
            }
        }
        
        return $baseRate * (1 - $totalDeduction / 100);
    }

    /**
     * Calcola la percentuale di avanzamento del progetto
     */
    public function getProgressPercentageAttribute()
    {
        $totalActivities = $this->activities()->count();
        if ($totalActivities === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }
        
        $completedActivities = $this->activities()->where('status', 'completed')->count();
        return round(($completedActivities / $totalActivities) * 100);
    }

    /**
     * Aggiorna il costo totale del progetto
     */
    public function updateTotalCost()
    {
        $totalCost = $this->resources->sum(function ($resource) {
            return $resource->pivot->cost;
        });
        
        $this->total_cost = $totalCost;
        $this->save();
        
        return $this;
    }

    /**
     * Ottiene le ore standard effettive per risorsa
     */
    public function getStandardActualHoursByResourceAttribute()
    {
        $result = [];
        $resourceActivities = $this->activities()
            ->where('hours_type', 'standard')
            ->get()
            ->groupBy('resource_id');
            
        foreach ($resourceActivities as $resourceId => $activities) {
            $result[$resourceId] = $activities->sum('actual_minutes') / 60; // Conversione minuti in ore
        }
        
        return $result;
    }

    /**
     * Ottiene le ore extra effettive per risorsa
     */
    public function getExtraActualHoursByResourceAttribute()
    {
        $result = [];
        $resourceActivities = $this->activities()
            ->where('hours_type', 'extra')
            ->get()
            ->groupBy('resource_id');
            
        foreach ($resourceActivities as $resourceId => $activities) {
            $result[$resourceId] = $activities->sum('actual_minutes') / 60; // Conversione minuti in ore
        }
        
        return $result;
    }

    /**
     * Ottiene le risorse con ore standard
     */
    public function getStandardResourcesAttribute()
    {
        return $this->resources()
            ->wherePivot('hours_type', 'standard')
            ->get();
    }

    /**
     * Ottiene le risorse con ore extra
     */
    public function getExtraResourcesAttribute()
    {
        return $this->resources()
            ->wherePivot('hours_type', 'extra')
            ->get();
    }

    /**
     * Scope per progetti creati da tasks
     */
    public function scopeCreatedFromTasks($query)
    {
        return $query->where('created_from_tasks', true);
    }

    /**
     * Scope per progetti creati normalmente
     */
    public function scopeCreatedNormally($query)
    {
        return $query->where('created_from_tasks', false);
    }

    /**
     * Crea un progetto "al volo" per i tasks
     */
    public static function createFromTasks($name, $clientId, $description = null)
    {
        return self::create([
            'name' => $name,
            'description' => $description ?? 'Progetto creato automaticamente dalla gestione tasks. Da verificare e completare.',
            'client_id' => $clientId,
            'cost_steps' => [1,2,3,4,5,6,7,8], // Step di costo standard
            'total_cost' => 0,
            'status' => 'pending',
            'default_hours_type' => 'standard',
            'created_from_tasks' => true,
            'tasks_created_at' => now(),
        ]);
    }

    /**
     * Consolida un progetto creato da tasks
     */
    public function consolidate($data = [])
    {
        $updateData = array_merge($data, [
            'created_from_tasks' => false,
            'tasks_created_at' => null,
        ]);
        
        $this->update($updateData);
    }
}