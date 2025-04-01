<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'area_id',
        'resource_id',
        'has_multiple_resources',
        'estimated_minutes',
        'actual_minutes',
        'hours_type',
        'estimated_cost',
        'actual_cost',
        'due_date',
        'status'
    ];

    protected $casts = [
        'due_date' => 'date',
        'has_multiple_resources' => 'boolean',
    ];

    /**
     * Get the project that owns the activity.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the area that owns the activity.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the main resource associated with the activity (legacy support).
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get all resources associated with this activity.
     */
    public function resources()
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot('estimated_minutes', 'actual_minutes', 'hours_type', 'estimated_cost', 'actual_cost')
            ->withTimestamps();
    }

    /**
     * Get the tasks for the activity.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Check if the activity is overdue.
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->status !== 'completed' && $this->due_date->isPast();
    }

    /**
     * Check if the activity has gone over the estimated minutes.
     */
    public function getIsOverEstimatedAttribute()
    {
        return $this->actual_minutes > $this->estimated_minutes;
    }

    /**
     * Get the progress percentage based on tasks.
     */
    public function getProgressPercentageAttribute()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Calculate estimated hours (minutes converted to hours).
     */
    public function getEstimatedHoursAttribute()
    {
        return round($this->estimated_minutes / 60, 2);
    }

    /**
     * Calculate actual hours (minutes converted to hours).
     */
    public function getActualHoursAttribute()
    {
        return round($this->actual_minutes / 60, 2);
    }

    /**
     * Update actual cost based on actual minutes and resource rates.
     */
    public function updateActualCost()
    {
        if ($this->has_multiple_resources) {
            // Se l'attività ha risorse multiple, somma i costi di tutte le risorse
            $totalActualCost = 0;
            
            foreach ($this->resources as $resource) {
                $pivotData = $resource->pivot;
                // Determina la tariffa oraria in base al tipo di ore
                if ($pivotData->hours_type === 'standard') {
                    $hourlyRate = $resource->selling_price;
                } else {
                    $hourlyRate = $resource->extra_selling_price ?? ($resource->selling_price * 1.2);
                }
                
                $resourceActualCost = ($pivotData->actual_minutes / 60) * $hourlyRate;
                $totalActualCost += $resourceActualCost;
                
                // Aggiorna anche il costo nella tabella pivot
                $this->resources()->updateExistingPivot($resource->id, [
                    'actual_cost' => $resourceActualCost
                ]);
            }
            
            $this->actual_cost = $totalActualCost;
        } else {
            // Gestione legacy per singola risorsa
            if (!$this->resource) {
                return;
            }

            // Determina la tariffa oraria in base al tipo di ore
            if ($this->hours_type === 'standard') {
                $hourlyRate = $this->resource->selling_price;
            } else {
                $hourlyRate = $this->resource->extra_selling_price ?? ($this->resource->selling_price * 1.2);
            }
            
            $this->actual_cost = ($this->actual_minutes / 60) * $hourlyRate;
        }
        
        $this->save();
    }

    /**
     * Set the status based on task completion.
     */
    public function updateStatusFromTasks()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        
        if ($completedTasks === $totalTasks) {
            $this->status = 'completed';
        } elseif ($completedTasks > 0) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
        
        // Aggiorna anche l'area associata
        $this->updateParentArea();
    }

    /**
     * Update actual minutes based on task completion.
     */
    public function updateActualMinutesFromTasks()
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return;
        }
        
        // Minuti totali per l'intera attività
        $totalActualMinutes = 0;
        
        // Array per tenere traccia dei minuti effettivi per risorsa
        $resourceMinutes = [];
        
        // Calcola i minuti effettivi per ogni risorsa in base ai task
        $tasks = $this->tasks()->get();
        foreach ($tasks as $task) {
            $taskMinutes = $task->actual_minutes > 0 ? $task->actual_minutes : 0;
            $totalActualMinutes += $taskMinutes;
            
            // Se il task ha una risorsa assegnata, aggiungi i minuti a quella risorsa
            if ($task->resource_id) {
                if (!isset($resourceMinutes[$task->resource_id])) {
                    $resourceMinutes[$task->resource_id] = 0;
                }
                $resourceMinutes[$task->resource_id] += $taskMinutes;
            }
        }
        
        // Aggiorna i minuti effettivi dell'attività
        $this->actual_minutes = $totalActualMinutes;
        
        // Se l'attività ha risorse multiple, distribuisci i minuti in base ai task
        if ($this->has_multiple_resources && $this->resources->count() > 0) {
            $this->distributeActualMinutesToResourcesFromTasks($resourceMinutes);
        }
        
        // Aggiorna il costo effettivo
        $this->updateActualCost();
        
        $this->save();
        
        // Aggiorna anche l'area associata
        $this->updateParentArea();
    }

    /**
     * Distribuisci i minuti effettivi alle risorse in base ai task completati.
     */
    protected function distributeActualMinutesToResourcesFromTasks($resourceMinutes)
    {
        foreach ($this->resources as $resource) {
            $resourceId = $resource->id;
            $actualMinutes = isset($resourceMinutes[$resourceId]) ? $resourceMinutes[$resourceId] : 0;
            
            $this->resources()->updateExistingPivot($resourceId, [
                'actual_minutes' => $actualMinutes
            ]);
        }
    }

    /**
 * Distribuisci i minuti effettivi tra le risorse proporzionalmente ai minuti stimati.
 */
protected function distributeActualMinutesToResources($totalActualMinutes)
{
    $totalEstimatedMinutes = $this->resources->sum('pivot.estimated_minutes');
    
    if ($totalEstimatedMinutes <= 0) {
        // Se non ci sono minuti stimati, distribuisci equamente
        $equalMinutes = $totalActualMinutes / max(1, $this->resources->count());
        
        foreach ($this->resources as $resource) {
            $this->resources()->updateExistingPivot($resource->id, [
                'actual_minutes' => round($equalMinutes)
            ]);
        }
    } else {
        // Distribuisci proporzionalmente ai minuti stimati
        foreach ($this->resources as $resource) {
            $proportion = $resource->pivot->estimated_minutes / $totalEstimatedMinutes;
            $resourceActualMinutes = round($totalActualMinutes * $proportion);
            
            $this->resources()->updateExistingPivot($resource->id, [
                'actual_minutes' => $resourceActualMinutes
            ]);
        }
    }
}
    
    /**
     * Update parent area with actual minutes.
     */
    public function updateParentArea()
    {
        if ($this->area_id) {
            $area = Area::find($this->area_id);
            if ($area) {
                $area->updateActualMinutesFromActivities();
            }
        }
    }
    
    /**
     * Get the remaining estimated minutes for this activity.
     */
    public function getRemainingEstimatedMinutesAttribute()
    {
        $tasksEstimatedMinutes = $this->tasks()->sum('estimated_minutes');
        return max(0, $this->estimated_minutes - $tasksEstimatedMinutes);
    }
    
    /**
     * Get the remaining actual minutes for this activity.
     */
    public function getRemainingActualMinutesAttribute()
    {
        $tasksActualMinutes = $this->tasks()->sum('actual_minutes');
        return max(0, $this->actual_minutes - $tasksActualMinutes);
    }
    
    /**
     * Migra i dati dalla relazione one-to-many alla many-to-many per supportare risorse multiple.
     */
    public function migrateToMultipleResources()
    {
        // Solo se l'attività ha una risorsa principale e non ha ancora risorse multiple
        if ($this->resource_id && !$this->has_multiple_resources) {
            $resource = $this->resource;
            
            // Aggiungi la risorsa principale alla relazione many-to-many
            $this->resources()->attach($resource->id, [
                'estimated_minutes' => $this->estimated_minutes,
                'actual_minutes' => $this->actual_minutes,
                'hours_type' => $this->hours_type,
                'estimated_cost' => $this->estimated_cost,
                'actual_cost' => $this->actual_cost
            ]);
            
            // Aggiorna il flag
            $this->has_multiple_resources = true;
            $this->save();
        }
    }
}