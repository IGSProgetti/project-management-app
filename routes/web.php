<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResourceHoursController;
use App\Http\Controllers\DailyHoursController; // 🆕 NUOVO IMPORT
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rotte di autenticazione standard di Laravel
Auth::routes();

// Tutte le rotte protette da autenticazione
Route::middleware(['auth'])->group(function () {
    // Dashboard principale
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rotte per la gestione delle risorse
    Route::resource('resources', ResourceController::class);
    Route::post('/resources/calculate-costs', [ResourceController::class, 'calculateCosts'])->name('resources.calculate-costs');
    Route::get('/resources/hours-availability', [ResourceController::class, 'hoursAvailability'])->name('resources.hours-availability');
    Route::post('/resources/{resource}/fix-legacy-hours', [ResourceController::class, 'fixLegacyHoursData'])->name('resources.fix-legacy-hours');
    
    // Rotte per la gestione dei clienti
    Route::resource('clients', ClientController::class);
    
    // Rotte per la gestione dei progetti
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/calculate-costs', [ProjectController::class, 'calculateCosts'])->name('projects.calculate-costs');
    
    // Rotte per la gestione delle aree
    Route::resource('areas', AreaController::class);
    Route::get('/areas/by-project/{project}', [AreaController::class, 'byProject'])->name('areas.by-project');
    
    // Rotte per la gestione delle attività
    Route::resource('activities', ActivityController::class);
    Route::get('/activities/by-project/{project}', [ActivityController::class, 'byProject'])->name('activities.by-project');
    Route::get('/activities/by-area/{area}', [ActivityController::class, 'byArea'])->name('activities.by-area');
    Route::put('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.updateStatus');
    
    // API activities
    Route::get('/api/activities', [ActivityController::class, 'getAllActivities'])->name('api.activities');
    Route::get('/api/activities/by-project/{project}', [ActivityController::class, 'byProjectForApi'])->name('api.activities.by-project');
    
    // Prima la rotta specifica timetracking
    Route::get('/tasks/timetracking', [TaskController::class, 'timeTracking'])->name('tasks.timetracking');
    
    // Poi eventuali altre rotte specifiche per i task
    Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
    Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/start', [TaskController::class, 'start'])->name('tasks.start');
    Route::get('/tasks/by-activity/{activity}', [TaskController::class, 'byActivity'])->name('tasks.by-activity');
    Route::post('/tasks/{task}/update-timer', [TaskController::class, 'updateTaskTimer'])->name('tasks.update-timer');
    
    // Poi la resource route (per ultima)
    Route::resource('tasks', TaskController::class);
    
    // Rotte per AJAX e API interne all'applicazione
    Route::get('/api/resources-by-project/{project}', [ResourceController::class, 'getByProject'])->name('api.resources-by-project');
    Route::get('/api/project-summary/{project}', [ProjectController::class, 'getSummary'])->name('api.project-summary');
    Route::get('/api/tasks', [TaskController::class, 'getTasks'])->name('api.tasks');
    Route::put('/api/tasks/{task}/status', [TaskController::class, 'updateStatusApi'])->name('api.tasks.update-status');
    Route::post('/api/tasks/reorder', [TaskController::class, 'reorderTasks'])->name('api.tasks.reorder');
    
    // Rotta home (se necessaria)
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    
    // Rotte per il calendario
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/api/calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
    
    // Rotte per la gestione orario
    Route::get('/hours', [ResourceHoursController::class, 'index'])->name('hours.index');
    Route::get('/hours/filter', [ResourceHoursController::class, 'filter'])->name('hours.filter');
    Route::get('/hours/export', [ResourceHoursController::class, 'export'])->name('hours.export');
    
    // Rotte per il profilo utente
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    
    // Dettagli task per risorsa
    Route::get('resource/{resourceId}/tasks', [ResourceHoursController::class, 'getResourceTaskDetails'])->name('resource.tasks');

    // API per creazione al volo da tasks
    Route::post('/tasks/create-client', [TaskController::class, 'createClientFromTasks'])->name('tasks.create-client');
    Route::post('/tasks/create-project', [TaskController::class, 'createProjectFromTasks'])->name('tasks.create-project');
    
    // API per caricamento dati dinamico
    Route::get('/api/projects-by-client/{client}', [TaskController::class, 'getProjectsByClient'])->name('api.projects-by-client');
    Route::get('/api/activities-by-project/{project}', [TaskController::class, 'getActivitiesByProject'])->name('api.activities-by-project');
    Route::get('/api/project-tasks/{project}', [TaskController::class, 'getProjectTasks'])->name('api.project-tasks');
    
    // Consolidamento clienti e progetti
    Route::patch('/clients/{client}/consolidate', [ClientController::class, 'consolidate'])->name('clients.consolidate');
    Route::patch('/projects/{project}/consolidate', [ProjectController::class, 'consolidate'])->name('projects.consolidate');
    Route::post('/projects/{project}/reassign-tasks', [ProjectController::class, 'reassignTasks'])->name('projects.reassign-tasks');
    
    // API per statistiche
    Route::get('/api/clients/tasks-created-stats', [ClientController::class, 'getTasksCreatedStats'])->name('api.clients.tasks-stats');
    Route::get('/api/projects/tasks-created-stats', [ProjectController::class, 'getTasksCreatedStats'])->name('api.projects.tasks-stats');
});

// Amministrazione utenti e ore giornaliere (solo per admin)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', UserController::class);
    
    // 🆕 NUOVE ROTTE per la gestione delle ore giornaliere
    Route::get('/daily-hours', [DailyHoursController::class, 'index'])
          ->name('daily-hours.index');
    Route::post('/daily-hours/redistribute', [DailyHoursController::class, 'redistributeHours'])
          ->name('daily-hours.redistribute');
    Route::get('/daily-hours/projects-by-client', [DailyHoursController::class, 'getProjectsByClient'])
          ->name('daily-hours.projects-by-client');
    Route::get('/daily-hours/export', [DailyHoursController::class, 'export'])
          ->name('daily-hours.export');
    Route::delete('/daily-hours/redistribution/{id}', [DailyHoursController::class, 'undoRedistribution'])
          ->name('daily-hours.undo-redistribution');
});

// Rotte per la gestione tesoretto nelle risorse
Route::get('/resources/{resource}/treasure', [ResourceController::class, 'getTreasureInfo'])
    ->name('resources.treasure.info');

Route::put('/resources/{resource}/treasure', [ResourceController::class, 'updateTreasure'])
    ->name('resources.treasure.update');

// Dashboard tesoretto (opzionale)
Route::get('/treasure/dashboard', function () {
    return view('treasure.dashboard');
})->name('treasure.dashboard');

// Rotte per la gestione tesoretto nelle risorse
Route::get('/resources/{resource}/treasure', [ResourceController::class, 'getTreasureInfo'])
    ->name('resources.treasure.info');

Route::put('/resources/{resource}/treasure', [ResourceController::class, 'updateTreasure'])
    ->name('resources.treasure.update');

// Dashboard tesoretto (opzionale)
Route::get('/treasure/dashboard', function () {
    return view('treasure.dashboard');
})->name('treasure.dashboard');

// Aggiungi questa riga nella sezione delle rotte daily-hours esistenti:

Route::post('/daily-hours/redistribute-unified', [DailyHoursController::class, 'redistributeUnifiedHours'])
      ->name('daily-hours.redistribute-unified');

