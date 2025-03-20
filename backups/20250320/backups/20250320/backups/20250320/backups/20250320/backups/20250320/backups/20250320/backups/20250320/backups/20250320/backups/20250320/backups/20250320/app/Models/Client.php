<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'budget',
        'notes'
    ];

    /**
     * Get the projects that belong to the client.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all resources through projects.
     */
    public function resources()
    {
        return $this->hasManyThrough(
            Resource::class,
            Project::class,
            'client_id', 
            'id', 
            'id', 
            'resource_id'
        );
    }

    /**
     * Calculate total budget used by all projects.
     */
    public function getTotalBudgetUsedAttribute()
    {
        return $this->projects()->sum('total_cost');
    }

    /**
     * Calculate remaining budget.
     */
    public function getRemainingBudgetAttribute()
    {
        return $this->budget - $this->total_budget_used;
    }

    /**
     * Get budget usage percentage.
     */
    public function getBudgetUsagePercentageAttribute()
    {
        if ($this->budget <= 0) {
            return 0;
        }

        return min(100, round(($this->total_budget_used / $this->budget) * 100));
    }
}