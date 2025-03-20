<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;

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

// Pagina di benvenuto
#Route::get('/', function () {
    #return view('welcome');
#});

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Dashboard principale
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Rotte per la gestione delle risorse
Route::resource('resources', ResourceController::class);
Route::post('/resources/calculate-costs', [ResourceController::class, 'calculateCosts'])->name('resources.calculate-costs');

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

// Rotte per la gestione dei task
Route::resource('tasks', TaskController::class);
Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
Route::post('/tasks/{task}/start', [TaskController::class, 'start'])->name('tasks.start');
Route::get('/tasks/by-activity/{activity}', [TaskController::class, 'byActivity'])->name('tasks.by-activity');

// Nuova rotta per aggiornare i minuti effettivi dal cronometro
Route::post('/tasks/{task}/update-timer', [TaskController::class, 'updateTaskTimer'])->name('tasks.updateTimer');

// Rotte per AJAX e API interne all'applicazione
Route::get('/api/resources-by-project/{project}', [ResourceController::class, 'getByProject'])->name('api.resources-by-project');
Route::get('/api/project-summary/{project}', [ProjectController::class, 'getSummary'])->name('api.project-summary');

// Route per la vista Kanban (board)
Route::get('/tasks/board', [TaskController::class, 'board'])->name('tasks.board');

// Routes per le API della board
Route::get('/api/tasks', [TaskController::class, 'getTasks'])->name('api.tasks');
Route::put('/api/tasks/{task}/status', [TaskController::class, 'updateStatusApi'])->name('api.tasks.update-status');
Route::post('/api/tasks/reorder', [TaskController::class, 'reorderTasks'])->name('api.tasks.reorder');

// Se prevedi l'autenticazione, puoi aggiungere il middleware auth
// Per proteggere le rotte che richiedono autenticazione
Route::middleware(['auth'])->group(function () {
    // Rotte protette che richiedono autenticazione
});

Route::put('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.updateStatus');

Route::get('resources/hours-availability', [ResourceController::class, 'hoursAvailability'])->name('resources.hours-availability');

Route::post('/resources/{resource}/fix-legacy-hours', [ResourceController::class, 'fixLegacyHoursData'])->name('resources.fix-legacy-hours');

// Auth::routes(); // Decommentare se utilizzi Laravel UI per l'autenticazione
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rotte per la gestione degli utenti (solo per Admin)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class);
});

Route::put('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.updateStatus');

Route::get('resources/hours-availability', [ResourceController::class, 'hoursAvailability'])->name('resources.hours-availability');

Route::post('/resources/{resource}/fix-legacy-hours', [ResourceController::class, 'fixLegacyHoursData'])->name('resources.fix-legacy-hours');

Route::get('/users/profile', [UserController::class, 'profile'])->name('users.profile');
