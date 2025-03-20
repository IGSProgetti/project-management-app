<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Costruttore: applica middleware di autenticazione e controllo admin
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->except(['profile', 'updateProfile']);
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::with('resource')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Ottieni solo le risorse che non hanno un utente associato e sono attive
        $availableResources = Resource::whereDoesntHave('user')
            ->where('is_active', true)
            ->get();
            
        return view('users.create', compact('availableResources'));
    }

    /**
     * Store a newly created user in database.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'resource_id' => 'nullable|exists:resources,id',
            'is_admin' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Se è selezionata una risorsa, verifica che sia disponibile
        if ($request->resource_id) {
            $resource = Resource::find($request->resource_id);
            if (!$resource || !$resource->isAvailableForUser()) {
                return redirect()->back()
                    ->with('error', 'La risorsa selezionata non è disponibile o è già associata a un altro utente.')
                    ->withInput();
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'resource_id' => $request->resource_id,
            'is_admin' => $request->has('is_admin'),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Utente creato con successo.');
    }

    /**
     * Display the specified user.
     */
    public function show(string $id)
    {
        $user = User::with('resource')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(string $id)
    {
        $user = User::with('resource')->findOrFail($id);
        
        // Ottieni le risorse disponibili (che non hanno un utente associato o sono associate a questo utente)
        $availableResources = Resource::where(function($query) use ($user) {
                $query->whereDoesntHave('user')
                    ->orWhereHas('user', function($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
            })
            ->where('is_active', true)
            ->get();
            
        return view('users.edit', compact('user', 'availableResources'));
    }

    /**
     * Update the specified user in database.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'resource_id' => 'nullable|exists:resources,id',
            'is_admin' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Se è selezionata una risorsa, verifica che sia disponibile
        if ($request->resource_id && $request->resource_id != $user->resource_id) {
            $resource = Resource::find($request->resource_id);
            if (!$resource || !$resource->isAvailableForUser()) {
                return redirect()->back()
                    ->with('error', 'La risorsa selezionata non è disponibile o è già associata a un altro utente.')
                    ->withInput();
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->resource_id = $request->resource_id;
        $user->is_admin = $request->has('is_admin');
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'Utente aggiornato con successo.');
    }

    /**
     * Remove the specified user from database.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'Utente eliminato con successo.');
    }
    
    /**
     * Show the user profile page.
     */
    public function profile()
    {
        $user = auth()->user();
        return view('users.profile', compact('user'));
    }
    
    /**
     * Update the user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => 'nullable|string|required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verifica password attuale se si sta cambiando la password
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => 'La password attuale non è corretta.'])
                    ->withInput();
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return redirect()->route('users.profile')
            ->with('success', 'Profilo aggiornato con successo.');
    }
}