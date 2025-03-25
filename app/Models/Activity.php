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
     * Get the resource that owns the activity.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
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
     * Update actual cost based on actual minutes and resource rate.
     */
    public function updateActualCost()
    {
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
        $completedTasks = $this->tasks()->where('status', 'completed')->get();
        
        if ($totalTasks === 0) {
            return;
        }

        // Se i task hanno minuti effettivi specificati, usa quelli
        $totalActualMinutes = 0;
        foreach ($completedTasks as $task) {
            if ($task->actual_minutes > 0) {
                $totalActualMinutes += $task->actual_minutes;
            } else {
                // Altrimenti usa una proporzione dei minuti stimati dell'attività
                $totalActualMinutes += isset($task->estimated_minutes) && $task->estimated_minutes > 0 
                    ? $task->estimated_minutes 
                    : $this->estimated_minutes / $totalTasks;
            }
        }
        
        // Aggiorna i minuti effettivi
        $this->actual_minutes = round($totalActualMinutes);
        
        // Aggiorna il costo effettivo
        $this->updateActualCost();
        
        $this->save();
        
        // Aggiorna anche l'area associata
        $this->updateParentArea();
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
}