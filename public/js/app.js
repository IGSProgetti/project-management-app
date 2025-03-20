// Funzioni comuni per l'applicazione
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip di Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inizializza i popover di Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Funzione per gestire l'autochiusura degli alert
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Funzione per formattare i numeri come valuta
function formatCurrency(value) {
    return parseFloat(value).toFixed(2) + ' €';
}

// Funzione per confermare le azioni di eliminazione
function confirmDelete(formId) {
    if (confirm('Sei sicuro di voler eliminare questo elemento? Questa azione non può essere annullata.')) {
        document.getElementById(formId).submit();
    }
}