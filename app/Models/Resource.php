<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'monthly_compensation',
        'working_days_year',
        'working_hours_day',
        'extra_hours_day',
        'cost_price',
        'selling_price',
        'extra_cost_price',
        'extra_selling_price',
        'remuneration_breakdown',
        'email',
        'phone',
        'is_active'
    ];

    protected $casts = [
        'remuneration_breakdown' => 'array',
        'is_active' => 'boolean',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('hours', 'hours_type', 'adjusted_rate', 'cost')
            ->withTimestamps();
    }

    /**
     * Relazione many-to-many con le attività.
     */
    public function activities()
    {
        return $this->belongsToMany(Activity::class)
            ->withPivot('estimated_minutes', 'actual_minutes', 'hours_type', 'estimated_cost', 'actual_cost')
            ->withTimestamps();
    }

    /**
     * Relazione one-to-many con le attività (legacy support).
     */
    public function primaryActivities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Ottiene i progetti con ore standard
     */
    public function standardProjects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('hours', 'adjusted_rate', 'cost')
            ->withTimestamps()
            ->wherePivot('hours_type', 'standard');
    }

    /**
     * Ottiene i progetti con ore extra
     */
    public function extraProjects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('hours', 'adjusted_rate', 'cost')
            ->withTimestamps()
            ->wherePivot('hours_type', 'extra');
    }

    /**
     * Relazione con User - una risorsa può essere associata a un utente
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Controlla se questa risorsa ha un utente associato
     */
    public function hasUser()
    {
        return $this->user()->exists();
    }

    /**
     * Controlla se questa risorsa è disponibile per associazione a un utente
     */
    public function isAvailableForUser()
    {
        return !$this->hasUser() && $this->is_active;
    }

    /**
     * Ottieni le ore standard disponibili all'anno.
     */
    public function getStandardHoursPerYearAttribute()
    {
        return $this->working_days_year * $this->working_hours_day;
    }
    
    /**
     * Ottieni le ore extra disponibili all'anno.
     */
    public function getExtraHoursPerYearAttribute()
    {
        return $this->working_days_year * ($this->extra_hours_day ?? 0);
    }
    
    /**
     * Calcola il totale delle ore standard stimate per tutti i progetti.
     */
    public function getTotalStandardEstimatedHoursAttribute()
    {
        return $this->standardProjects()
            ->sum('project_resource.hours');
    }
    
    /**
     * Calcola il totale delle ore extra stimate per tutti i progetti.
     */
    public function getTotalExtraEstimatedHoursAttribute()
    {
        return $this->extraProjects()
            ->sum('project_resource.hours');
    }
    
    /**
     * Calcola il totale dei minuti standard stimati per tutte le attività.
     */
    public function getTotalStandardEstimatedMinutesAttribute()
    {
        $fromPrimary = $this->primaryActivities()
            ->where('has_multiple_resources', false)
            ->where('hours_type', 'standard')
            ->sum('estimated_minutes');
            
        $fromMultiple = DB::table('activity_resource')
            ->where('resource_id', $this->id)
            ->where('hours_type', 'standard')
            ->sum('estimated_minutes');
            
        return $fromPrimary + $fromMultiple;
    }
    
    /**
     * Calcola il totale dei minuti extra stimati per tutte le attività.
     */
    public function getTotalExtraEstimatedMinutesAttribute()
    {
        $fromPrimary = $this->primaryActivities()
            ->where('has_multiple_resources', false)
            ->where('hours_type', 'extra')
            ->sum('estimated_minutes');
            
        $fromMultiple = DB::table('activity_resource')
            ->where('resource_id', $this->id)
            ->where('hours_type', 'extra')
            ->sum('estimated_minutes');
            
        return $fromPrimary + $fromMultiple;
    }
    
    /**
     * Calcola il totale dei minuti standard effettivi per tutte le attività.
     */
    public function getTotalStandardActualMinutesAttribute()
    {
        $fromPrimary = $this->primaryActivities()
            ->where('has_multiple_resources', false)
            ->where('hours_type', 'standard')
            ->sum('actual_minutes');
            
        $fromMultiple = DB::table('activity_resource')
            ->where('resource_id', $this->id)
            ->where('hours_type', 'standard')
            ->sum('actual_minutes');
            
        return $fromPrimary + $fromMultiple;
    }
    
    /**
     * Calcola il totale dei minuti extra effettivi per tutte le attività.
     */
    public function getTotalExtraActualMinutesAttribute()
    {
        $fromPrimary = $this->primaryActivities()
            ->where('has_multiple_resources', false)
            ->where('hours_type', 'extra')
            ->sum('actual_minutes');
            
        $fromMultiple = DB::table('activity_resource')
            ->where('resource_id', $this->id)
            ->where('hours_type', 'extra')
            ->sum('actual_minutes');
            
        return $fromPrimary + $fromMultiple;
    }
    
    /**
     * Calcola il totale delle ore standard stimate per tutte le attività.
     */
    public function getTotalStandardEstimatedHoursFromActivitiesAttribute()
    {
        return $this->total_standard_estimated_minutes / 60;
    }
    
    /**
     * Calcola il totale delle ore extra stimate per tutte le attività.
     */
    public function getTotalExtraEstimatedHoursFromActivitiesAttribute()
    {
        return $this->total_extra_estimated_minutes / 60;
    }
    
    /**
     * Calcola il totale delle ore standard effettive per tutte le attività.
     */
    public function getTotalStandardActualHoursAttribute()
    {
        return $this->total_standard_actual_minutes / 60;
    }
    
    /**
     * Calcola il totale delle ore extra effettive per tutte le attività.
     */
    public function getTotalExtraActualHoursAttribute()
    {
        return $this->total_extra_actual_minutes / 60;
    }
    
    /**
     * Calcola le ore standard rimanenti stimate.
     */
    public function getRemainingStandardEstimatedHoursAttribute()
    {
        return max(0, $this->standard_hours_per_year - $this->total_standard_estimated_hours_from_activities);
    }
    
    /**
     * Calcola le ore extra rimanenti stimate.
     */
    public function getRemainingExtraEstimatedHoursAttribute()
    {
        return max(0, $this->extra_hours_per_year - $this->total_extra_estimated_hours_from_activities);
    }
    
    /**
     * Calcola le ore standard rimanenti effettive.
     */
    public function getRemainingStandardActualHoursAttribute()
    {
        return max(0, $this->standard_hours_per_year - $this->total_standard_actual_hours);
    }
    
    /**
     * Calcola le ore extra rimanenti effettive.
     */
    public function getRemainingExtraActualHoursAttribute()
    {
        return max(0, $this->extra_hours_per_year - $this->total_extra_actual_hours);
    }

    /**
     * Corregge i dati legacy convertendo campi extra_hours in record separati con hours_type=extra
     */
    public function correctLegacyHoursData()
    {
        // Se ci sono progetti con campi legacy extra_hours
        $legacyProjects = DB::table('project_resource')
            ->where('resource_id', $this->id)
            ->whereNotNull('extra_hours')
            ->where('extra_hours', '>', 0)
            ->get();
            
        foreach ($legacyProjects as $pivot) {
            // Crea un nuovo record per le ore extra
            DB::table('project_resource')->insert([
                'project_id' => $pivot->project_id,
                'resource_id' => $this->id,
                'hours' => $pivot->extra_hours,
                'hours_type' => 'extra',
                'adjusted_rate' => $pivot->extra_adjusted_rate ?? $pivot->adjusted_rate,
                'cost' => $pivot->extra_hours * ($pivot->extra_adjusted_rate ?? $pivot->adjusted_rate),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Aggiorna il record originale per essere esplicitamente standard
            DB::table('project_resource')
                ->where('id', $pivot->id)
                ->update([
                    'hours_type' => 'standard',
                    'extra_hours' => null,
                    'extra_adjusted_rate' => null,
                    'updated_at' => now()
                ]);
        }
        
        return $this;
    }
}