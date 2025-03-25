<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'description', 
        'project_id',
        'estimated_minutes',
        'actual_minutes'
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
    
    /**
     * Get the total estimated minutes from activities.
     */
    public function getActivitiesEstimatedMinutesAttribute()
    {
        return $this->activities->sum('estimated_minutes');
    }
    
    /**
     * Get the total actual minutes from activities.
     */
    public function getActivitiesActualMinutesAttribute()
    {
        return $this->activities->sum('actual_minutes');
    }
    
    /**
     * Get the remaining estimated minutes.
     */
    public function getRemainingEstimatedMinutesAttribute()
    {
        return max(0, $this->estimated_minutes - $this->activities_estimated_minutes);
    }
    
    /**
     * Get the remaining actual minutes.
     */
    public function getRemainingActualMinutesAttribute()
    {
        return max(0, $this->actual_minutes - $this->activities_actual_minutes);
    }
    
    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->estimated_minutes <= 0) {
            return 0;
        }
        
        return min(100, round(($this->actual_minutes / $this->estimated_minutes) * 100));
    }
    
    /**
     * Update the actual minutes from activities.
     */
    public function updateActualMinutesFromActivities()
    {
        $this->actual_minutes = $this->activities_actual_minutes;
        $this->save();
    }
    
    /**
     * Check if the area is over estimated.
     */
    public function getIsOverEstimatedAttribute()
    {
        return $this->actual_minutes > $this->estimated_minutes;
    }
}