@extends('layouts.app')

@section('title', 'Nuova Risorsa')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Nuova Risorsa</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('resources.store') }}" method="POST" id="resourceForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nome Risorsa</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="role">Ruolo</label>
                        <input type="text" id="role" name="role" class="form-control @error('role') is-invalid @enderror" value="{{ old('role') }}" required>
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
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone">Telefono</label>
                        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h4>Informazioni Lavorative</h4>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="monthly_compensation">Compenso Mensile (€)</label>
                        <input type="number" id="monthly_compensation" name="monthly_compensation" class="form-control @error('monthly_compensation') is-invalid @enderror" value="{{ old('monthly_compensation', 4000) }}" min="0" step="0.01" required>
                        @error('monthly_compensation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="working_days_year">Giorni Lavorativi/Anno</label>
                        <input type="number" id="working_days_year" name="working_days_year" class="form-control @error('working_days_year') is-invalid @enderror" value="{{ old('working_days_year', 220) }}" min="1" max="365" required>
                        @error('working_days_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="working_hours_day">Ore Standard/Giorno</label>
                        <input type="number" id="working_hours_day" name="working_hours_day" class="form-control @error('working_hours_day') is-invalid @enderror" value="{{ old('working_hours_day', 8) }}" min="0.5" max="24" step="0.5" required>
                        @error('working_hours_day')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="extra_hours_day">Ore Extra/Giorno</label>
                        <input type="number" id="extra_hours_day" name="extra_hours_day" class="form-control @error('extra_hours_day') is-invalid @enderror" value="{{ old('extra_hours_day', 3) }}" min="0" max="24" step="0.5">
                        @error('extra_hours_day')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-8 mb-3 d-flex align-items-end">
                        <button type="button" id="calculateCostsBtn" class="btn btn-info">
                            <i class="fas fa-calculator"></i> Calcola Costi
                        </button>
                    </div>
                </div>
                
                <!-- Sezione risultati calcolo -->
                <div id="resultsSection" style="display: none;">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h4>Risultati Calcolo</h4>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <h5>Breakdown Remunerazione</h5>
                            <table class="table table-striped" id="remunerationTable">
                                <thead>
                                    <tr>
                                        <th>Componente</th>
                                        <th>Percentuale</th>
                                        <th>Importo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <!-- Campi nascosti per l'invio dei dati calcolati -->
                            <input type="hidden" id="cost_price" name="cost_price" value="{{ old('cost_price') }}">
                            <input type="hidden" id="selling_price" name="selling_price" value="{{ old('selling_price') }}">
                            <input type="hidden" id="extra_cost_price" name="extra_cost_price" value="{{ old('extra_cost_price') }}">
                            <input type="hidden" id="extra_selling_price" name="extra_selling_price" value="{{ old('extra_selling_price') }}">
                            <input type="hidden" id="remuneration_breakdown" name="remuneration_breakdown" value="{{ old('remuneration_breakdown') }}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salva Risorsa</button>
                        <a href="{{ route('resources.index') }}" class="btn btn-secondary">Annulla</a>
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
        // Funzione helper per convertire valori in numeri sicuri
        function safeNumber(value, defaultValue = 0) {
            const num = parseFloat(value);
            return isNaN(num) ? defaultValue : num;
        }

        // Funzione helper per formattare numeri con toFixed sicuro
        function safeToFixed(value, decimals = 2) {
            const num = safeNumber(value, 0);
            return num.toFixed(decimals);
        }

        // Calcolo dei costi al click del pulsante
        document.getElementById('calculateCostsBtn').addEventListener('click', function() {
            const monthlyCompensation = parseFloat(document.getElementById('monthly_compensation').value) || 0;
            const workingDaysYear = parseInt(document.getElementById('working_days_year').value) || 0;
            const workingHoursDay = parseFloat(document.getElementById('working_hours_day').value) || 0;
            const extraHoursDay = parseFloat(document.getElementById('extra_hours_day').value) || 0;

            if (validateFormData(monthlyCompensation, workingDaysYear, workingHoursDay)) {
                // Calcolo costo e prezzo standard
                calculateCosts(monthlyCompensation, workingDaysYear, workingHoursDay, extraHoursDay);
            }
        });

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

        function calculateCosts(monthlyCompensation, workingDaysYear, workingHoursDay, extraHoursDay) {
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
                    working_hours_day: workingHoursDay,
                    extra_hours_day: extraHoursDay
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data, workingDaysYear, workingHoursDay, extraHoursDay);
                    document.getElementById('resultsSection').style.display = 'block';
                } else {
                    alert('Errore nel calcolo dei costi: ' + JSON.stringify(data.errors));
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nella richiesta. Verifica i dati inseriti.');
            });
        }

        function displayResults(data, workingDaysYear, workingHoursDay, extraHoursDay) {
            const tbody = document.querySelector('#remunerationTable tbody');
            
            if (tbody && data.breakdown) {
                tbody.innerHTML = '';

                for (const [component, value] of Object.entries(data.breakdown)) {
                    const percentage = getPercentageByComponent(component);
                    const row = tbody.insertRow();
                    
                    const cell1 = row.insertCell(0);
                    const cell2 = row.insertCell(1);
                    const cell3 = row.insertCell(2);
                    
                    cell1.innerHTML = component;
                    cell2.innerHTML = `${percentage}%`;
                    // Usa safeToFixed invece di toFixed diretto - supporta sia value.amount che value diretto
                    cell3.innerHTML = `${safeToFixed(value.amount || value, 2)} €`;
                }
            }
            
            // Aggiorna i campi nascosti per l'invio del form - con validazione
            const costPriceField = document.getElementById('cost_price');
            const sellingPriceField = document.getElementById('selling_price');
            const remunerationField = document.getElementById('remuneration_breakdown');
            
            if (costPriceField) {
                costPriceField.value = safeToFixed(data.costPrice, 2);
            }
            
            if (sellingPriceField) {
                sellingPriceField.value = safeToFixed(data.sellingPrice, 2);
            }
            
            if (remunerationField) {
                remunerationField.value = JSON.stringify(data.breakdown || {});
            }
            
            // Gestisci i campi extra se presenti
            if (data.extraCostPrice && data.extraSellingPrice) {
                const extraCostField = document.getElementById('extra_cost_price');
                const extraSellingField = document.getElementById('extra_selling_price');
                
                if (extraCostField) {
                    extraCostField.value = safeToFixed(data.extraCostPrice, 2);
                }
                
                if (extraSellingField) {
                    extraSellingField.value = safeToFixed(data.extraSellingPrice, 2);
                }
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