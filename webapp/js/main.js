// Haupt-JavaScript-Datei für VAIBad Webanwendung

$(document).ready(function() {
    // Initialisierung
    initTooltips();
    initDataTables();
    initFormValidation();
    initConfirmationDialogs();
    initDatePickers();
    
    // Globale Variablen
    window.base_url = $('#base_url').data('url') || '/webapp/';
});

// Tooltips initialisieren
function initTooltips() {
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover'
    });
}

// DataTables für Tabellen initialisieren
function initDataTables() {
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'
            },
            responsive: true,
            dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rtip',
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="fas fa-download"></i> Export',
                    className: 'btn btn-outline-secondary',
                    buttons: [
                        { extend: 'csv', text: '<i class="fas fa-file-csv"></i> CSV' },
                        { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel' },
                        { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF' },
                        { extend: 'print', text: '<i class="fas fa-print"></i> Drucken' }
                    ]
                }
            ],
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'Alle']
            ],
            pageLength: 25
        });
    }
}

// Formularvalidierung initialisieren
function initFormValidation() {
    // Bootstrap Form Validation
    if (typeof window.BootstrapValidation !== 'undefined') {
        Array.from(document.querySelectorAll('.needs-validation')).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Benutzerdefinierte Validierung für Zahlen
    $('.validate-number').on('input', function() {
        var value = $(this).val();
        if (value !== '' && !$.isNumeric(value)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Benutzerdefinierte Validierung für Dezimalzahlen
    $('.validate-decimal').on('input', function() {
        var value = $(this).val();
        var decimalRegex = /^[\d]+(\.[\d]{1,2})?$/;
        if (value !== '' && !decimalRegex.test(value)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
}

// Bestätigungsdialoge initialisieren
function initConfirmationDialogs() {
    // Prüfen, ob bootbox verfügbar ist
    var useBootbox = typeof bootbox !== 'undefined';
    
    // Löschbestätigung
    $('body').on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var message = $(this).data('confirm-message') || 'Möchten Sie diesen Eintrag wirklich löschen?';
        var title = $(this).data('confirm-title') || 'Löschen bestätigen';
        
        if (useBootbox) {
            // Bootbox-Dialog (schöner)
            bootbox.confirm({
                title: '<i class="fas fa-exclamation-triangle text-warning"></i> ' + title,
                message: message,
                buttons: {
                    confirm: {
                        label: '<i class="fas fa-check"></i> Ja, löschen',
                        className: 'btn-danger'
                    },
                    cancel: {
                        label: '<i class="fas fa-times"></i> Abbrechen',
                        className: 'btn-secondary'
                    }
                },
                callback: function(result) {
                    if (result) {
                        window.location.href = href;
                    }
                }
            });
        } else {
            // Fallback: Standard-JavaScript confirm()
            if (confirm(message)) {
                window.location.href = href;
            }
        }
    });
    
    // Aktionsbestätigung
    $('body').on('click', '.confirm-action', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var message = $(this).data('confirm-message') || 'Möchten Sie diese Aktion wirklich ausführen?';
        var title = $(this).data('confirm-title') || 'Aktion bestätigen';
        
        if (useBootbox) {
            // Bootbox-Dialog (schöner)
            bootbox.confirm({
                title: '<i class="fas fa-question-circle text-info"></i> ' + title,
                message: message,
                buttons: {
                    confirm: {
                        label: '<i class="fas fa-check"></i> Ja, ausführen',
                        className: 'btn-primary'
                    },
                    cancel: {
                        label: '<i class="fas fa-times"></i> Abbrechen',
                        className: 'btn-secondary'
                    }
                },
                callback: function(result) {
                    if (result) {
                        window.location.href = href;
                    }
                }
            });
        } else {
            // Fallback: Standard-JavaScript confirm()
            if (confirm(message)) {
                window.location.href = href;
            }
        }
    });
}

// Datumsauswahl initialisieren
function initDatePickers() {
