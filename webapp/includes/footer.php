<?php
// Session-Nachrichten für die nächste Seite vorbereiten
if (isset($message)) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type ?? 'info';
}
?>
    </div>
    
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> VAIBad - Datenverwaltungssystem</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Version 1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery muss VOR bootbox geladen werden -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootbox für Bestätigungsdialoge -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>js/main.js"></script>
    
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
