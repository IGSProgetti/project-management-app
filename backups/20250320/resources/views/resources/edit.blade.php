@extends('layouts.app')

@section('title', 'Modifica Risorsa')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Modifica Risorsa</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('resources.update', $resource->id) }}" method="POST" id="resourceForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Risorsa</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $resource->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="role">Ruolo</label>
                        <input type="text" id="role" name="role" class="form-control @error('role') is-invalid @enderror" value="{{ old('role', $resource->role) }}" required>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h4>Informazioni di Contatto</h4>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $resource->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone">Telefono</label>
                        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $resource->phone) }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h4>Informazioni Lavorative</h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="monthly_compensation">Compenso Mensile (€)</label>
                        <input type="number" id="monthly_compensation" name="monthly_compensation" class="form-control @error('monthly_compensation') is-invalid @enderror" value="{{ old('monthly_compensation', $resource->monthly_compensation) }}" min="0" step="0.01" required>
                        @error('monthly_compensation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="working_days_year">Giorni Lavorativi/Anno</label>
                        <input type="number" id="working_days_year" name="working_days_year" class="form-control @error('working_days_year') is-invalid @enderror" value="{{ old('working_days_year', $resource->working_days_year) }}" min="1" max="365" required>
                        @error('working_days_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="working_hours_day">Ore Standard/Giorno</label>
                        <input type="number" id="working_hours_day" name="working_hours_day" class="form-control @error('working_hours_day') is-invalid @enderror" value="{{ old('working_hours_day', $resource->working_hours_day) }}" min="0.5" max="24" step="0.5" required>
                        @error('working_hours_day')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="extra_hours_day">Ore Extra/Giorno</label>
                        <input type="number" id="extra_hours_day" name="extra_hours_day" class="form-control @error('extra_hours_day') is-invalid @enderror" value="{{ old('extra_hours_day', $resource->extra_hours_day) }}" min="0" max="24" step="0.5">
                        @error('extra_hours_day')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="cost_price">Prezzo di Costo (€/h)</label>
                        <input type="number" id="cost_price" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', $resource->cost_price) }}" min="0" step="0.01" required>
                        @error('cost_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="selling_price">Prezzo di Vendita (€/h)</label>
                        <input type="number" id="selling_price" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', $resource->selling_price) }}" min="0" step="0.01" required>
                        @error('selling_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="extra_cost_price">Prezzo di Costo Extra (€/h)</label>
                        <input type="number" id="extra_cost_price" name="extra_cost_price" class="form-control @error('extra_cost_price') is-invalid @enderror" value="{{ old('extra_cost_price', $resource->extra_cost_price) }}" min="0" step="0.01">
                        @error('extra_cost_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="extra_selling_price">Prezzo di Vendita Extra (€/h)</label>
                        <input type="number" id="extra_selling_price" name="extra_selling_price" class="form-control @error('extra_selling_price') is-invalid @enderror" value="{{ old('extra_selling_price', $resource->extra_selling_price) }}" min="0" step="0.01">
                        @error('extra_selling_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $resource->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Risorsa attiva
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <button type="button" id="calculateCostsBtn" class="btn btn-info">
                            Ricalcola Costi
                        </button>
                        <button type="button" id="fixLegacyDataBtn" class="btn btn-warning ms-2">
                            Ripara Dati Ore Legacy
                        </button>
                    </div>
                </div>
                
                <div id="resultsSection" style="display: none;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Risultati Calcolo Costi</h3>
                        </div>
                        <div class="card-body">
                            <div id="costResults" class="mb-4"></div>
                            
                            <h4>Schema Remunerativo</h4>
                            <div class="table-responsive">
                                <table class="table" id="breakdownTable">
                                    <thead>
                                        <tr>
                                            <th>Componente</th>
                                            <th>Percentuale</th>
                                            <th>Valore (€/h)</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            
                            <!-- Campi nascosti per l'invio dei dati calcolati -->
                            <input type="hidden" id="remuneration_breakdown" name="remuneration_breakdown" value="{{ old('remuneration_breakdown', json_encode($resource->remuneration_breakdown)) }}">
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Progetti con Ore Allocate -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Ore Allocate nei Progetti</h3>
                    </div>
                    <div class="card-body">
                        @if($resource->projects->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Progetto</th>
                                            <th>Cliente</th>
                                            <th>Tipo Ore</th>
                                            <th>Ore Allocate</th>
                                            <th>Tariffa</th>
                                            <th>Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($resource->projects as $project)
                                        <tr>
                                            <td>{{ $project->name }}</td>
                                            <td>{{ $project->client->name }}</td>
                                            <td>
                                                @if($project->pivot->hours_type == 'standard')
                                                    <span class="badge bg-primary">Standard</span>
                                                @elseif($project->pivot->hours_type == 'extra')
                                                    <span class="badge bg-warning">Extra</span>
                                                @endif
                                            </td>
                                            <td>{{ $project->pivot->hours }}</td>
                                            <td>{{ number_format($project->pivot->adjusted_rate, 2) }} €/h</td>
                                            <td>{{ number_format($project->pivot->cost, 2) }} €</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                Questa risorsa non è ancora assegnata a nessun progetto.
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Aggiorna Risorsa</button>
                        <a href="{{ route('resources.show', $resource->id) }}" class="btn btn-secondary">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calcolo dei costi al click del pulsante
        document.getElementById('calculateCostsBtn').addEventListener('click', function() {
            const monthlyCompensation = parseFloat(document.getElementById('monthly_compensation').value) || 0;
            const workingDaysYear = parseInt(document.getElementById('working_days_year').value) || 0;
            const workingHoursDay = parseFloat(document.getElementById('working_hours_day').value) || 0;
            const extraHoursDay = parseFloat(document.getElementById('extra_hours_day').value) || 0;

            if (validateFormData(monthlyCompensation, workingDaysYear, workingHoursDay)) {
                // Calcolo costo e prezzo standard
                calculateStandardRates(monthlyCompensation, workingDaysYear, workingHoursDay);
                
                // Calcolo costo e prezzo extra se specificato
                if (extraHoursDay > 0) {
                    calculateExtraRates(monthlyCompensation, workingDaysYear, extraHoursDay);
                }
            }
        });

        // Pulsante per riparare dati legacy
        document.getElementById('fixLegacyDataBtn').addEventListener('click', function() {
            if (confirm('Questa operazione convertirà i dati dalle ore extra (extra_hours) al nuovo formato che utilizza record separati (hours_type). Vuoi procedere?')) {
                fixLegacyData();
            }
        });

        function fixLegacyData() {
            fetch('{{ route("resources.fix-legacy-hours", $resource->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Dati convertiti con successo. La pagina verrà ricaricata per vedere i cambiamenti.');
                    location.reload();
                } else {
                    alert('Errore nella conversione dei dati: ' + (data.message || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella richiesta. Riprova più tardi.');
            });
        }

        function validateFormData(monthlyCompensation, workingDaysYear, workingHoursDay) {
            const errors = [];

            if (!monthlyCompensation || monthlyCompensation <= 0) {
                errors.push('Il compenso mensile deve essere maggiore di zero');
            }
            if (!workingDaysYear || workingDaysYear <= 0 || workingDaysYear > 365) {
                errors.push('I giorni lavorativi devono essere tra 1 e 365');
            }
            if (!workingHoursDay || workingHoursDay <= 0 || workingHoursDay > 24) {
                errors.push('Le ore lavorative giornaliere devono essere tra 1 e 24');
            }

            if (errors.length > 0) {
                alert(errors.join('\n'));
                return false;
            }
            return true;
        }

        function calculateStandardRates(monthlyCompensation, workingDaysYear, workingHoursDay) {
            // Usa fetch per fare una richiesta AJAX
            fetch('{{ route("resources.calculate-costs") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    monthly_compensation: monthlyCompensation,
                    working_days_year: workingDaysYear,
                    working_hours_day: workingHoursDay
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data);
                    document.getElementById('resultsSection').style.display = 'block';
                    
                    // Imposta i valori nei campi
                    document.getElementById('cost_price').value = data.costPrice;
                    document.getElementById('selling_price').value = data.sellingPrice;
                    document.getElementById('remuneration_breakdown').value = JSON.stringify(data.breakdown);
                } else {
                    alert('Errore nel calcolo dei costi: ' + JSON.stringify(data.errors));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella richiesta. Verifica i dati inseriti.');
            });
        }

        function calculateExtraRates(monthlyCompensation, workingDaysYear, extraHoursDay) {
            // Calcolo dei costi extra (normalmente con fattore maggiorato)
            const yearlyCompensation = monthlyCompensation * 12;
            const yearlyStandardHours = workingDaysYear * document.getElementById('working_hours_day').value;
            const yearlyExtraHours = workingDaysYear * extraHoursDay;
            
            // Calcola costo orario extra con maggiorazione del 20%
            const extraHourlyRate = (yearlyCompensation / yearlyStandardHours) * 1.2;
            const extraSellingPrice = (extraHourlyRate * 100) / 20;
            
            // Imposta i valori nei campi nascosti
            document.getElementById('extra_cost_price').value = extraHourlyRate.toFixed(2);
            document.getElementById('extra_selling_price').value = extraSellingPrice.toFixed(2);
            
            // Aggiungi informazioni sui costi extra nel riepilogo
            const costResults = document.getElementById('costResults');
            const extraCostInfo = document.createElement('div');
            extraCostInfo.classList.add('mt-3');
            extraCostInfo.innerHTML = `
                <h5>Costi Ore Extra:</h5>
                <p><strong>Prezzo di Costo Extra:</strong> ${extraHourlyRate.toFixed(2)} €/h</p>
                <p><strong>Costo Orario di Vendita Extra:</strong> ${extraSellingPrice.toFixed(2)} €/h</p>
            `;
            costResults.appendChild(extraCostInfo);
        }

        function displayResults(data) {
            const costResults = document.getElementById('costResults');
            costResults.innerHTML = `
                <h5>Costi Ore Standard:</h5>
                <p><strong>Prezzo di Costo:</strong> ${data.costPrice.toFixed(2)} €/h</p>
                <p><strong>Costo Orario di Vendita:</strong> ${data.sellingPrice.toFixed(2)} €/h</p>
            `;

            const breakdownTable = document.getElementById('breakdownTable').getElementsByTagName('tbody')[0];
            breakdownTable.innerHTML = '';

            for (const [component, value] of Object.entries(data.breakdown)) {
                const percentage = getPercentageByComponent(component);
                const row = breakdownTable.insertRow();
                
                const cell1 = row.insertCell(0);
                const cell2 = row.insertCell(1);
                const cell3 = row.insertCell(2);
                
                cell1.innerHTML = component;
                cell2.innerHTML = `${percentage}%`;
                cell3.innerHTML = `${value.toFixed(2)} €`;
            }
        }

        function getPercentageByComponent(component) {
            const percentages = {
                'Costo struttura': 25,
                'Utile gestore azienda': 12.5,
                'Utile IGS': 12.5,
                'Compenso professionista': 20,
                'Bonus professionista': 5,
                'Gestore società': 3,
                'Chi porta il lavoro': 8,
                'Network IGS': 14
            };
            return percentages[component] || 0;
        }
    });
</script>
@endpush