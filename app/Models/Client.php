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
        'notes',
        'created_from_tasks',
        'tasks_created_at'
    ];

    protected $casts = [
        'created_from_tasks' => 'boolean',
        'tasks_created_at' => 'datetime',
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

    /**
     * Scope per clienti creati da tasks
     */
    public function scopeCreatedFromTasks($query)
    {
        return $query->where('created_from_tasks', true);
    }

    /**
     * Scope per clienti creati normalmente
     */
    public function scopeCreatedNormally($query)
    {
        return $query->where('created_from_tasks', false);
    }

    /**
     * Crea un cliente "al volo" per i tasks
     */
    public static function createFromTasks($name, $estimatedBudget = 10000)
    {
        return self::create([
            'name' => $name,
            'budget' => $estimatedBudget,
            'notes' => 'Cliente creato automaticamente dalla gestione tasks. Da verificare e completare.',
            'created_from_tasks' => true,
            'tasks_created_at' => now(),
        ]);
    }

    /**
     * Consolida un cliente creato da tasks
     */
    public function consolidate($budget = null, $notes = null)
    {
        $this->update([
            'budget' => $budget ?? $this->budget,
            'notes' => $notes ?? $this->notes,
            'created_from_tasks' => false,
            'tasks_created_at' => null,
        ]);
    }
}