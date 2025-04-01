<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'activity_id',
        'resource_id', // Aggiunto resource_id
        'status',
        'due_date',
        'order',
        'estimated_minutes',
        'actual_minutes'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the activity that owns the task.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the resource assigned to the task.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Check if the task is overdue.
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->status !== 'completed' && $this->due_date->isPast();
    }

    /**
     * Mark the task as completed and update the actual minutes.
     */
    public function complete($actualMinutes = null)
    {
        $this->status = 'completed';
        if ($actualMinutes !== null) {
            $this->actual_minutes = $actualMinutes;
        }
        $this->save();
        
        // Aggiorna lo stato e i minuti effettivi dell'attività associata
        $this->updateParentActivity();
        return $this;
    }

    /**
     * Mark the task as in progress.
     */
    public function start()
    {
        $this->status = 'in_progress';
        $this->save();
        
        // Update parent activity status
        $this->activity->updateStatusFromTasks();
        return $this;
    }

    /**
     * Reset the task to pending.
     */
    public function reset()
    {
        $this->status = 'pending';
        $this->save();
        
        // Update parent activity status
        $this->activity->updateStatusFromTasks();
        return $this;
    }

    /**
     * Update the parent activity with the task's data.
     */
    public function updateParentActivity()
    {
        if ($this->activity) {
            // Aggiorna lo stato dell'attività in base ai task
            $this->activity->updateStatusFromTasks();
            
            // Calcola e aggiorna i minuti effettivi dell'attività
            $this->activity->updateActualMinutesFromTasks();
        }
    }

    /**
     * Update estimated progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->estimated_minutes <= 0) {
            return $this->status === 'completed' ? 100 : 0;
        }
        return min(100, round(($this->actual_minutes / $this->estimated_minutes) * 100));
    }

    /**
     * Check if task is overestimated.
     */
    public function getIsOverEstimatedAttribute()
    {
        return $this->actual_minutes > $this->estimated_minutes && $this->estimated_minutes > 0;
    }
}