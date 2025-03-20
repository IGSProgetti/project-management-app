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
        'default_hours_type'
    ];

    protected $casts = [
        'cost_steps' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
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
     *
     * @param float $baseRate La tariffa oraria di base
     * @return float La tariffa oraria aggiustata
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
        
        // Calcola la percentuale totale di deduzione
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
     * Ottiene l'utilizzo percentuale di ore standard per risorsa
     */
    public function getStandardHoursUtilizationAttribute()
    {
        $result = [];
        $resourceHours = [];
        
        // Ottieni le ore pianificate per risorsa
        $this->resources()
            ->wherePivot('hours_type', 'standard')
            ->get()
            ->each(function ($resource) use (&$resourceHours) {
                $resourceHours[$resource->id] = [
                    'planned' => $resource->pivot->hours,
                    'actual' => 0
                ];
            });
        
        // Aggiungi le ore effettive dalle attività
        $standardHoursUsed = $this->standard_actual_hours_by_resource;
        foreach ($standardHoursUsed as $resourceId => $hours) {
            if (isset($resourceHours[$resourceId])) {
                $resourceHours[$resourceId]['actual'] = $hours;
            }
        }
        
        // Calcola la percentuale di utilizzo
        foreach ($resourceHours as $resourceId => $hours) {
            $planned = $hours['planned'] ?: 0.01; // Evita divisione per zero
            $result[$resourceId] = min(100, round(($hours['actual'] / $planned) * 100));
        }
        
        return $result;
    }

    /**
     * Ottiene l'utilizzo percentuale di ore extra per risorsa
     */
    public function getExtraHoursUtilizationAttribute()
    {
        $result = [];
        $resourceHours = [];
        
        // Ottieni le ore pianificate per risorsa
        $this->resources()
            ->wherePivot('hours_type', 'extra')
            ->get()
            ->each(function ($resource) use (&$resourceHours) {
                $resourceHours[$resource->id] = [
                    'planned' => $resource->pivot->hours,
                    'actual' => 0
                ];
            });
        
        // Aggiungi le ore effettive dalle attività
        $extraHoursUsed = $this->extra_actual_hours_by_resource;
        foreach ($extraHoursUsed as $resourceId => $hours) {
            if (isset($resourceHours[$resourceId])) {
                $resourceHours[$resourceId]['actual'] = $hours;
            }
        }
        
        // Calcola la percentuale di utilizzo
        foreach ($resourceHours as $resourceId => $hours) {
            $planned = $hours['planned'] ?: 0.01; // Evita divisione per zero
            $result[$resourceId] = min(100, round(($hours['actual'] / $planned) * 100));
        }
        
        return $result;
    }

    /**
     * Verifica se il progetto è sopra budget
     */
    public function getIsOverBudgetAttribute()
    {
        $totalActualCost = $this->activities->sum('actual_cost');
        return $totalActualCost > $this->total_cost;
    }

    /**
     * Ottiene il budget utilizzato
     */
    public function getBudgetUsedAttribute()
    {
        return $this->activities->sum('actual_cost');
    }

    /**
     * Ottiene la percentuale di budget utilizzato
     */
    public function getBudgetUsedPercentageAttribute()
    {
        if ($this->total_cost <= 0) {
            return 0;
        }
        
        return min(100, round(($this->budget_used / $this->total_cost) * 100));
    }

    /**
     * Ottiene il budget rimanente
     */
    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->total_cost - $this->budget_used);
    }
}