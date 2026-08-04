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
    // Löschbestätigung
    $('body').on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var message = $(this).data('confirm-message') || 'Möchten Sie diesen Eintrag wirklich löschen?';
        var title = $(this).data('confirm-title') || 'Löschen bestätigen';
        
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
    });
    
    // Aktionsbestätigung
    $('body').on('click', '.confirm-action', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var message = $(this).data('confirm-message') || 'Möchten Sie diese Aktion wirklich ausführen?';
        var title = $(this).data('confirm-title') || 'Aktion bestätigen';
        
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
    });
}

// Datumsauswahl initialisieren
function initDatePickers() {
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            language: 'de',
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom auto'
        });
    }
    
    // HTML5 Datumsfelder - aktuelles Datum als Standard
    $('input[type="date"]').each(function() {
        if (!$(this).val()) {
            $(this).val(new Date().toISOString().split('T')[0]);
        }
    });
}

// AJAX-Funktionen
function ajaxRequest(url, data, method, successCallback, errorCallback) {
    method = method || 'GET';
    
    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        beforeSend: function() {
            // Loading Indicator zeigen
            if ($('#loading-indicator').length === 0) {
                $('body').append('<div id="loading-indicator" class="position-fixed top-50 start-50 translate-middle"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            }
        },
        complete: function() {
            // Loading Indicator entfernen
            $('#loading-indicator').remove();
        },
        success: function(response) {
            if (response.success) {
                if (successCallback) {
                    successCallback(response);
                }
            } else {
                showError(response.message || 'Ein Fehler ist aufgetreten');
                if (errorCallback) {
                    errorCallback(response);
                }
            }
        },
        error: function(xhr, status, error) {
            showError('Ein Fehler ist aufgetreten: ' + error);
            if (errorCallback) {
                errorCallback({ success: false, message: error });
            }
        }
    });
}

// Fehlermeldung anzeigen
function showError(message) {
    // Bootstrap Toast oder Alert verwenden
    if ($('#error-toast').length === 0) {
        $('body').append(`
            <div id="error-toast" class="toast align-items-center text-white bg-danger border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-triangle"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
    } else {
        $('#error-toast .toast-body').html('<i class="fas fa-exclamation-triangle"></i> ' + message);
    }
    
    var toastElement = $('#error-toast')[0];
    var toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
}

// Erfolgsmeldung anzeigen
function showSuccess(message) {
    if ($('#success-toast').length === 0) {
        $('body').append(`
            <div id="success-toast" class="toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
    } else {
        $('#success-toast .toast-body').html('<i class="fas fa-check-circle"></i> ' + message);
    }
    
    var toastElement = $('#success-toast')[0];
    var toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
}

// Dynamische Abhängigkeiten zwischen Feldern
function updateDependentFields() {
    // Beispiel: Bahnlänge basierend auf Alter berechnen
    $('.geburtsjahr').on('change', function() {
        var geburtsjahr = parseInt($(this).val());
        var currentYear = new Date().getFullYear();
        var alter = currentYear - geburtsjahr;
        var bahnenlaenge = (alter < 14) ? 25 : 50;
        
        $(this).closest('form').find('.bahnenlaenge_m').val(bahnenlaenge);
    });
}

// Suchfunktionalität
function initSearch() {
    $('.search-input').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var table = $(this).data('target-table');
        
        $(table + ' tbody tr').each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
}

// Berechnungen durchführen
function calculateSpendenbetrag() {
    // Berechnung für Schwimmer-Sponsoren
    $('.calculate-spenden').on('click', function() {
        var row = $(this).closest('tr');
        var anzahlBahnen = parseFloat(row.find('.anzahl_bahnen').val()) || 0;
        var betragProBahn = parseFloat(row.find('.betrag_pro_bahn').val()) || 0;
        var limitBetrag = parseFloat(row.find('.limit_betrag').val()) || null;
        
        var spendenbetragRoh = anzahlBahnen * betragProBahn;
        var spendenbetrag = limitBetrag ? Math.min(spendenbetragRoh, limitBetrag) : spendenbetragRoh;
        
        row.find('.spendenbetrag_roh').val(spendenbetragRoh.toFixed(2));
        row.find('.spendenbetrag').val(spendenbetrag.toFixed(2));
    });
}

// Automatische Berechnung bei Änderungen
function initAutoCalculations() {
    // Schwimmleistungen - Gesamtstrecke berechnen
    $('.anzahl_bahnen, .geburtsjahr').on('change', function() {
        var form = $(this).closest('form');
        var anzahlBahnen = parseFloat(form.find('.anzahl_bahnen').val()) || 0;
        var geburtsjahr = parseInt(form.find('.geburtsjahr').val()) || 0;
        var currentYear = new Date().getFullYear();
        var alter = currentYear - geburtsjahr;
        var bahnenlaenge = (alter < 14) ? 25 : 50;
        var gesamtstrecke = anzahlBahnen * bahnenlaenge;
        
        form.find('.gesamtstrecke_m').val(gesamtstrecke);
    });
}

// Export-Funktionen
function exportToCSV(tableId, filename) {
    var table = document.getElementById(tableId);
    var rows = table.querySelectorAll('tr');
    var csv = [];
    
    for (var i = 0; i < rows.length; i++) {
        var row = [];
        var cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        
        csv.push(row.join(';'));
    }
    
    var csvString = csv.join('\n');
    var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Import-Funktionen
function initFileUpload() {
    $('.file-upload').on('change', function() {
        var file = this.files[0];
        var form = $(this).closest('form');
        var fileType = $(this).data('file-type');
        
        if (file) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var content = e.target.result;
                
                if (fileType === 'csv') {
                    // CSV-Import
                    var lines = content.split('\n');
                    var headers = lines[0].split(';');
                    
                    for (var i = 1; i < lines.length; i++) {
                        if (lines[i].trim() === '') continue;
                        
                        var values = lines[i].split(';');
                        var rowData = {};
                        
                        for (var j = 0; j < headers.length; j++) {
                            rowData[headers[j].trim()] = values[j] ? values[j].trim() : '';
                        }
                        
                        // Hier könnte man die Daten verarbeiten
                        console.log('Importierte Zeile:', rowData);
                    }
                }
            };
            
            if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
                reader.readAsText(file);
            }
        }
    });
}

// Initialisierung aller Funktionen
$(document).ready(function() {
    initTooltips();
    initFormValidation();
    initConfirmationDialogs();
    initDatePickers();
    updateDependentFields();
    initSearch();
    calculateSpendenbetrag();
    initAutoCalculations();
    initFileUpload();
    
    // Datenbank-Schema Anzeige
    $('.show-schema').on('click', function() {
        var table = $(this).data('table');
        $.ajax({
            url: 'schema.php',
            type: 'GET',
            data: { table: table },
            success: function(response) {
                bootbox.dialog({
                    title: '<i class="fas fa-database"></i> Tabellen-Schema: ' + table,
                    message: response,
                    size: 'large'
                });
            }
        });
    });
});

// Utility Funktionen
function formatCurrency(value) {
    if (value === null || value === '') return '';
    return parseFloat(value).toFixed(2).replace('.', ',') + ' €';
}

function formatNumber(value) {
    if (value === null || value === '') return '';
    return parseFloat(value).toLocaleString('de-DE');
}

function formatDate(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleDateString('de-DE');
}

// Chart-Funktionen (falls Chart.js verfügbar)
function createChart(ctx, type, data, options) {
    if (typeof Chart !== 'undefined') {
        return new Chart(ctx, {
            type: type,
            data: data,
            options: $.extend({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatCurrency(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }, options)
        });
    }
    return null;
}
