<?php
/**
 * Smart Queue Management - VERSION AMÉLIORÉE
 * ✅ Système de commentaires anonymes
 * ✅ Délai de réinitialisation: 60 secondes
 * ✅ Interface Admin avec notifications de commentaires
 * ✅ 6 Fonctionnalités avancées
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

define('DB_HOST', '127.0.0.1:3307');
define('DB_NAME', 'fastqueue');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'fr';
}

$translations = [
    'fr' => [
        'welcome' => 'Bienvenue à Smart Queue Management',
        'select_language' => 'Sélectionnez votre langue',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scannez ce code QR pour continuer sur mobile',
        'select_service' => 'Sélectionnez un service',
        'next' => 'Suivant',
        'back' => 'Retour',
        'full_name' => 'Nom Complet',
        'phone' => 'Téléphone',
        'status' => 'État',
        'standard' => 'Standard',
        'pregnant' => 'Femme enceinte',
        'disability' => 'PMR/Handicap',
        'print_ticket' => 'Imprimer Ticket',
        'download_ticket' => 'Télécharger le Ticket',
        'error_empty' => 'Tous les champs sont obligatoires',
        'ticket_number' => 'Numéro de Ticket',
        'service' => 'Service',
        'domain' => 'Domaine',
        'priority' => 'Priorité',
        'change_language' => 'Changer de langue',
        'form_info' => 'Veuillez saisir vos informations',
        'ticket_confirmed' => 'Ticket généré avec succès',
        'new_ticket' => 'Nouveau ticket',
        'select_service_first' => 'Veuillez sélectionner un service',
        'processing' => 'Traitement...',
        'printing' => 'Impression...',
        'step1' => 'ACCUEIL',
        'step2' => 'SERVICE',
        'step3' => 'INFOS',
        'step4' => 'TICKET',
        'date_time' => 'Date/Heure',
        'people_before' => 'personnes avant vous',
        'estimated_wait' => 'Temps d\'attente estimé',
        'minutes' => 'minutes',
        'feedback_title' => 'Votre avis compte',
        'thank_you_feedback' => 'Merci pour votre avis !',
        'autoremove_in' => 'Réinitialisation automatique dans',
        'seconds' => 'secondes',
        'voice_your_number' => 'Votre numéro est le',
        'share_comment' => 'Partager un commentaire (optionnel)',
        'comment_placeholder' => 'Votre avis sur notre service...'
    ],
    'ar' => [
        'welcome' => 'مرحبا بك في نظام إدارة الطوابير الذكية',
        'select_language' => 'اختر لغتك',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'امسح رمز QR هذا للمتابعة على الهاتف المحمول',
        'select_service' => 'اختر خدمة',
        'next' => 'التالي',
        'back' => 'العودة',
        'full_name' => 'الاسم الكامل',
        'phone' => 'الهاتف',
        'status' => 'الحالة',
        'standard' => 'عادي',
        'pregnant' => 'حامل',
        'disability' => 'الإعاقة',
        'print_ticket' => 'طباعة التذكرة',
        'download_ticket' => 'تحميل التذكرة',
        'error_empty' => 'جميع الحقول مطلوبة',
        'ticket_number' => 'رقم التذكرة',
        'service' => 'الخدمة',
        'domain' => 'المجال',
        'priority' => 'الأولوية',
        'change_language' => 'تغيير اللغة',
        'form_info' => 'يرجى إدخال معلوماتك',
        'ticket_confirmed' => 'تم إنشاء التذكرة بنجاح',
        'new_ticket' => 'تذكرة جديدة',
        'select_service_first' => 'الرجاء اختيار خدمة',
        'processing' => 'جاري المعالجة...',
        'printing' => 'جاري الطباعة...',
        'step1' => 'الرئيسية',
        'step2' => 'الخدمة',
        'step3' => 'المعلومات',
        'step4' => 'التذكرة',
        'date_time' => 'التاريخ/الوقت',
        'people_before' => 'أشخاص قبلك',
        'estimated_wait' => 'الوقت المتوقع للانتظار',
        'minutes' => 'دقيقة',
        'feedback_title' => 'رأيك يهمنا',
        'thank_you_feedback' => 'شكرا على رأيك !',
        'autoremove_in' => 'إعادة تعيين تلقائية في',
        'seconds' => 'ثانية',
        'voice_your_number' => 'رقمك هو',
        'share_comment' => 'مشاركة تعليق (اختياري)',
        'comment_placeholder' => 'رأيك في خدمتنا...'
    ],
    'en' => [
        'welcome' => 'Welcome to Smart Queue Management',
        'select_language' => 'Select your language',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scan this QR code to continue on mobile',
        'select_service' => 'Select a service',
        'next' => 'Next',
        'back' => 'Back',
        'full_name' => 'Full name',
        'phone' => 'Phone',
        'status' => 'Status',
        'standard' => 'Standard',
        'pregnant' => 'Pregnant',
        'disability' => 'Disability',
        'print_ticket' => 'Print ticket',
        'download_ticket' => 'Download Ticket',
        'error_empty' => 'All fields are required',
        'ticket_number' => 'Ticket number',
        'service' => 'Service',
        'domain' => 'Domain',
        'priority' => 'Priority',
        'change_language' => 'Change language',
        'form_info' => 'Please enter your information',
        'ticket_confirmed' => 'Ticket generated successfully',
        'new_ticket' => 'New ticket',
        'select_service_first' => 'Please select a service',
        'processing' => 'Processing...',
        'printing' => 'Printing...',
        'step1' => 'HOME',
        'step2' => 'SERVICE',
        'step3' => 'INFO',
        'step4' => 'TICKET',
        'date_time' => 'Date/Time',
        'people_before' => 'people before you',
        'estimated_wait' => 'Estimated wait time',
        'minutes' => 'minutes',
        'feedback_title' => 'Your feedback matters',
        'thank_you_feedback' => 'Thank you for your feedback!',
        'autoremove_in' => 'Automatic reset in',
        'seconds' => 'seconds',
        'voice_your_number' => 'Your number is',
        'share_comment' => 'Share a comment (optional)',
        'comment_placeholder' => 'Your opinion on our service...'
    ],
    'it' => [
        'welcome' => 'Benvenuto in Smart Queue Management',
        'select_language' => 'Seleziona la tua lingua',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scansiona questo codice QR per continuare sul mobile',
        'select_service' => 'Seleziona un servizio',
        'next' => 'Avanti',
        'back' => 'Indietro',
        'full_name' => 'Nome completo',
        'phone' => 'Telefono',
        'status' => 'Stato',
        'standard' => 'Standard',
        'pregnant' => 'In attesa',
        'disability' => 'Disabilità',
        'print_ticket' => 'Stampa biglietto',
        'download_ticket' => 'Scarica il biglietto',
        'error_empty' => 'Tutti i campi sono obbligatori',
        'ticket_number' => 'Numero del biglietto',
        'service' => 'Servizio',
        'domain' => 'Dominio',
        'priority' => 'Priorità',
        'change_language' => 'Cambia lingua',
        'form_info' => 'Inserisci le tue informazioni',
        'ticket_confirmed' => 'Biglietto generato con successo',
        'new_ticket' => 'Nuovo biglietto',
        'select_service_first' => 'Seleziona un servizio',
        'processing' => 'Elaborazione...',
        'printing' => 'Stampa in corso...',
        'step1' => 'HOME',
        'step2' => 'SERVIZIO',
        'step3' => 'INFO',
        'step4' => 'BIGLIETTO',
        'date_time' => 'Data/Ora',
        'people_before' => 'persone prima di te',
        'estimated_wait' => 'Tempo di attesa stimato',
        'minutes' => 'minuti',
        'feedback_title' => 'Il tuo feedback è importante',
        'thank_you_feedback' => 'Grazie per il tuo feedback!',
        'autoremove_in' => 'Ripristino automatico tra',
        'seconds' => 'secondi',
        'voice_your_number' => 'Il tuo numero è',
        'share_comment' => 'Condividi un commento (facoltativo)',
        'comment_placeholder' => 'Il tuo parere sul nostro servizio...'
    ]
];

$lang = $_SESSION['language'];
$t = $translations[$lang];
$isRTL = ($lang === 'ar');

function createPDO(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

$domaines  = [];
$db_error  = null;

try {
    $pdo = createPDO();

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'topical_domain'");
    if ($tableCheck->rowCount() === 0) {
        $db_error = "Table 'topical_domain' introuvable";
    } else {
        $stmt = $pdo->prepare(
            "SELECT td_id AS id, td_name AS nom, td_description AS description, td_code AS code
             FROM topical_domain
             WHERE td_active = 1
             ORDER BY td_name ASC"
        );
        $stmt->execute();
        $domaines = $stmt->fetchAll();
        error_log("✅ Connexion réussie! Domaines trouvés: " . count($domaines));
    }
} catch (PDOException $e) {
    $db_error = "Erreur BD: " . $e->getMessage();
    error_log("❌ " . $db_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'set_language') {
        $allowed = ['fr', 'ar', 'en', 'it'];
        $newLang = $_POST['language'] ?? '';
        if (in_array($newLang, $allowed, true)) {
            $_SESSION['language'] = $newLang;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Langue invalide']);
        }
        exit;
    }

    if ($action === 'set_service') {
        $_SESSION['service']      = $_POST['service']      ?? '';
        $_SESSION['service_code'] = $_POST['service_code'] ?? '';
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_ticket') {
        $name        = trim($_POST['name']         ?? '');
        $phone       = trim($_POST['phone']        ?? '');
        $status      = trim($_POST['status']       ?? '');
        $service     = trim($_POST['service']      ?? '');
        $serviceCode = strtoupper(trim($_POST['service_code'] ?? ''));

        if (empty($name) || empty($phone) || empty($status) || empty($service) || empty($serviceCode)) {
            echo json_encode(['success' => false, 'message' => $t['error_empty']]);
            exit;
        }

        if (!preg_match('/^[0-9\s\-\+\(\)]{8,}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
            exit;
        }

        try {
            $pdo  = createPDO();
            $today = date('Y-m-d');

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS ticket_counter (
                    tc_id           INT PRIMARY KEY AUTO_INCREMENT,
                    tc_service_code VARCHAR(10)  NOT NULL,
                    tc_date         DATE         NOT NULL,
                    tc_count        INT UNSIGNED NOT NULL DEFAULT 0,
                    UNIQUE KEY uq_service_date (tc_service_code, tc_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS tickets (
                    id              INT PRIMARY KEY AUTO_INCREMENT,
                    ticket_number   VARCHAR(50)  NOT NULL UNIQUE,
                    name            VARCHAR(255) NOT NULL,
                    phone           VARCHAR(20)  NOT NULL,
                    status          VARCHAR(50)  NOT NULL,
                    service         VARCHAR(255) NOT NULL,
                    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_service_status (service, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS feedback (
                    fb_id           INT PRIMARY KEY AUTO_INCREMENT,
                    ticket_number   VARCHAR(50)  NOT NULL,
                    rating          INT          NOT NULL,
                    comment         TEXT,
                    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (ticket_number) REFERENCES tickets(ticket_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            // ✅ NOUVELLE TABLE: commentaires anonymes
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS commentaires (
                    cm_id           INT PRIMARY KEY AUTO_INCREMENT,
                    ticket_number   VARCHAR(50),
                    service         VARCHAR(255),
                    contenu         LONGTEXT NOT NULL,
                    langue          VARCHAR(5),
                    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at),
                    INDEX idx_service (service)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO ticket_counter (tc_service_code, tc_date, tc_count)
                 VALUES (:code, :date, 1)
                 ON DUPLICATE KEY UPDATE
                     tc_count = LAST_INSERT_ID(tc_count + 1)"
            );
            $stmt->execute([
                'code' => $serviceCode,
                'date' => $today,
            ]);

            $nextNumber    = (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
            $ticket_number = $serviceCode . '-' . $nextNumber;

            $stmt = $pdo->prepare(
                "INSERT INTO tickets (ticket_number, name, phone, status, service, created_at)
                 VALUES (:ticket_number, :name, :phone, :status, :service, :created_at)"
            );
            $stmt->execute([
                'ticket_number' => $ticket_number,
                'name'          => $name,
                'phone'         => $phone,
                'status'        => $status,
                'service'       => $service,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $stmtWait = $pdo->prepare(
                "SELECT COUNT(*) as count FROM tickets 
                 WHERE service = :service AND status IN ('standard', 'pregnant', 'disability')"
            );
            $stmtWait->execute(['service' => $service]);
            $waitCount = (int) $stmtWait->fetchColumn();

            $pdo->commit();

            error_log("✅ Ticket créé: $ticket_number pour $name");

            echo json_encode([
                'success'         => true,
                'ticket_number'   => $ticket_number,
                'name'            => $name,
                'phone'           => $phone,
                'status'          => $status,
                'service'         => $service,
                'service_code'    => $serviceCode,
                'wait_count'      => $waitCount,
                'estimated_wait'  => max(0, $waitCount - 1) * 5
            ]);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('❌ Erreur sauvegarde ticket: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_feedback') {
        $ticketNumber = trim($_POST['ticket_number'] ?? '');
        $rating       = (int) ($_POST['rating'] ?? 0);
        $comment      = trim($_POST['comment'] ?? '');
        $service      = trim($_POST['service'] ?? '');

        if (empty($ticketNumber) || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        try {
            $pdo = createPDO();
            $stmt = $pdo->prepare(
                "INSERT INTO feedback (ticket_number, rating, comment)
                 VALUES (:ticket_number, :rating, :comment)"
            );
            $stmt->execute([
                'ticket_number' => $ticketNumber,
                'rating'        => $rating,
                'comment'       => $comment,
            ]);

            // ✅ Sauvegarder le commentaire anonyme si fourni
            if (!empty($comment)) {
                $stmtCom = $pdo->prepare(
                    "INSERT INTO commentaires (ticket_number, service, contenu, langue)
                     VALUES (:ticket_number, :service, :contenu, :langue)"
                );
                $stmtCom->execute([
                    'ticket_number' => $ticketNumber,
                    'service'       => $service,
                    'contenu'       => $comment,
                    'langue'        => $lang,
                ]);
            }

            error_log("✅ Feedback sauvegardé pour $ticketNumber (rating: $rating)");

            echo json_encode(['success' => true, 'message' => 'Merci !']);

        } catch (Exception $e) {
            error_log('❌ Erreur sauvegarde feedback: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }

    // ✅ NOUVELLE ACTION: récupérer les commentaires anonymes
    if ($action === 'get_comments') {
        try {
            $pdo = createPDO();
            $stmt = $pdo->prepare(
                "SELECT cm_id, service, contenu, created_at FROM commentaires 
                 ORDER BY created_at DESC LIMIT 50"
            );
            $stmt->execute();
            $comments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'comments' => $comments,
                'count' => count($comments)
            ]);
        } catch (Exception $e) {
            error_log('❌ Erreur récupération commentaires: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        :root {
            --primary: #5a67d8;
            --accent: #6b5ce7;
            --success: #48bb78;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-light: #f7fafc;
            --border-light: #e2e8f0;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            --shadow-xl: 0 16px 40px rgba(0,0,0,0.18);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 50%, #e6f0ff 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .container-main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .progress-container {
            margin-bottom: 48px;
            position: relative;
            width: 100%;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            width: 100%;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--border-light);
            border-radius: 3px;
            z-index: 1;
        }

        .progress-steps::after {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 3px;
            transition: width 0.5s ease;
            z-index: 2;
        }

        html[dir="rtl"] .progress-steps::after {
            left: auto;
            right: 0;
            background: linear-gradient(270deg, var(--primary) 0%, var(--accent) 100%);
        }

        .progress-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
            background: transparent;
            z-index: 3;
        }

        .progress-step-circle {
            width: 44px;
            height: 44px;
            background: white;
            border: 2.5px solid #cbd5e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            color: #94a3b8;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .progress-step.active .progress-step-circle {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-color: transparent;
            color: white;
            transform: scale(1.08);
            box-shadow: var(--shadow-md);
        }

        .progress-step.completed .progress-step-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .progress-step-label {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .progress-step.active .progress-step-label {
            color: var(--primary);
            font-weight: 800;
        }

        .progress-step.completed .progress-step-label {
            color: var(--success);
        }

        .card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            padding: 48px;
            max-width: 640px;
            width: 100%;
            animation: slideIn 0.5s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes ticketPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: slideIn 0.4s ease;
        }

        h1 {
            font-size: 32px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            color: var(--text-dark);
        }

        .subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 32px;
            font-size: 14px;
            font-weight: 500;
        }

        .qr-container {
            text-align: center;
            margin-bottom: 32px;
            padding: 32px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid var(--border-light);
            border-radius: 20px;
        }

        .qr-container p {
            margin-bottom: 20px;
            font-size: 14px;
            color: #475569;
            font-weight: 500;
        }

        #qrcode {
            display: inline-block;
            padding: 12px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }

        .language-selector {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }

        html[dir="rtl"] .language-selector {
            right: auto;
            left: 24px;
        }

        .language-toggle-btn-float {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            cursor: pointer;
            font-size: 26px;
            color: white;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .language-toggle-btn-float:hover {
            transform: scale(1.08);
        }

        .language-menu-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0);
            z-index: 999;
            transition: background 0.3s;
        }

        .language-menu-backdrop.active {
            display: block;
            background: rgba(0, 0, 0, 0.4);
        }

        .language-dropdown-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 12px;
            min-width: 200px;
            display: none;
            flex-direction: column;
            gap: 8px;
            z-index: 1002;
            animation: scaleIn 0.2s ease;
        }

        html[dir="rtl"] .language-dropdown-menu {
            right: auto;
            left: 0;
        }

        .language-dropdown-menu.active {
            display: flex;
        }

        .language-menu-item {
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            background: #f8fafc;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
        }

        .language-menu-item:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }

        .language-menu-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }

        .language-toggle {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 32px;
        }

        .lang-toggle-btn {
            padding: 12px 16px;
            border: 1.5px solid var(--border-light);
            background: white;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 14px;
        }

        .lang-toggle-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .lang-toggle-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
        }

        .form-group {
            margin-bottom: 24px;
            animation: slideInRight 0.4s ease;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 16px 18px;
            border: 1.5px solid var(--border-light);
            border-radius: 14px;
            font-size: 15px;
            background: white;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.1);
        }

        html[dir="rtl"] input,
        html[dir="rtl"] select,
        html[dir="rtl"] textarea {
            text-align: right;
        }

        .service-buttons {
            display: grid;
            gap: 12px;
            margin: 28px 0;
        }

        .service-btn {
            padding: 18px 20px;
            border: 1.5px solid var(--border-light);
            background: white;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 600;
            text-align: left;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        html[dir="rtl"] .service-btn {
            text-align: right;
        }

        .service-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .service-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
        }

        .service-btn small {
            display: block;
            font-size: 12px;
            font-weight: 400;
            margin-top: 6px;
            opacity: 0.8;
        }

        .no-services {
            padding: 40px;
            text-align: center;
            background: #fee2e2;
            border-radius: 16px;
            color: #991b1b;
            font-weight: 600;
        }

        .db-error {
            padding: 14px;
            background: #fef3c7;
            border-radius: 14px;
            color: #92400e;
            margin-bottom: 24px;
            font-weight: 600;
            font-size: 13px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 36px;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            text-align: center;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            font-size: 13px;
            letter-spacing: 0.5px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: white;
            color: #334155;
            border: 1.5px solid var(--border-light);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }

        .ticket-preview {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            padding: 32px;
            border-radius: 20px;
            text-align: center;
            margin: 32px 0;
            border: 1px solid var(--border-light);
        }

        .ticket-preview h2 {
            font-size: 24px;
            margin-bottom: 16px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .ticket-number {
            font-size: 52px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 16px 0;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            animation: ticketPulse 2s infinite;
        }

        .ticket-info {
            background: white;
            padding: 20px;
            border-radius: 16px;
            text-align: left;
            border: 1px solid var(--border-light);
            margin-top: 20px;
        }

        html[dir="rtl"] .ticket-info {
            text-align: right;
        }

        .ticket-info p {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
        }

        html[dir="rtl"] .ticket-info p {
            flex-direction: row-reverse;
        }

        .ticket-info p:last-child {
            border-bottom: none;
        }

        .wait-info {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 12px;
            margin: 16px 0;
            border-left: 4px solid var(--success);
        }

        html[dir="rtl"] .wait-info {
            border-left: none;
            border-right: 4px solid var(--success);
        }

        .wait-info p {
            margin: 4px 0;
            font-size: 14px;
        }

        .wait-info strong {
            color: var(--success);
        }

        .countdown-container {
            text-align: center;
            padding: 20px;
            background: #fef3c7;
            border-radius: 12px;
            margin: 16px 0;
            border: 2px solid #fcd34d;
            animation: fadeInDown 0.5s ease;
        }

        .countdown-text {
            font-size: 14px;
            color: #92400e;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .countdown-timer {
            font-size: 28px;
            font-weight: 800;
            color: var(--warning);
            font-family: 'Courier New', monospace;
        }

        .countdown-bar {
            width: 100%;
            height: 6px;
            background: #f3f4f6;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 12px;
        }

        .countdown-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--warning) 0%, #ec4899 100%);
            border-radius: 3px;
            transition: width 0.1s linear;
        }

        .feedback-container {
            background: linear-gradient(135deg, #fef3c7 0%, #fef08a 100%);
            padding: 20px;
            border-radius: 16px;
            margin: 20px 0;
            border: 1px solid #fcd34d;
            animation: slideIn 0.5s ease;
        }

        .feedback-title {
            font-size: 16px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 16px;
            text-align: center;
        }

        .stars-rating {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .star-btn {
            width: 48px;
            height: 48px;
            border: 2px solid #fcd34d;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .star-btn:hover {
            transform: scale(1.15);
        }

        .star-btn.active {
            background: linear-gradient(135deg, #fbbf24 0%, var(--warning) 100%);
            border-color: var(--warning);
            color: white;
        }

        .feedback-comment {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #fcd34d;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 13px;
            resize: vertical;
            min-height: 60px;
        }

        .feedback-submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #fbbf24 0%, var(--warning) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 12px;
            transition: all 0.3s;
        }

        .feedback-submit-btn:hover {
            transform: translateY(-2px);
        }

        .feedback-thank-you {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin-top: 12px;
            text-align: center;
            color: var(--success);
            font-weight: 600;
            display: none;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: none;
            font-weight: 600;
            font-size: 13px;
        }

        /* ✅ STYLES POUR LE MODAL DES COMMENTAIRES */
        .comment-icon-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            z-index: 998;
            display: none;
        }

        .comment-icon-btn:hover {
            transform: scale(1.1);
        }

        .comment-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            animation: pulse 2s infinite;
        }

        .comment-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1003;
            align-items: center;
            justify-content: center;
        }

        .comment-modal.active {
            display: flex;
        }

        .comment-modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: slideIn 0.3s ease;
        }

        .comment-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-light);
            padding-bottom: 15px;
        }

        .comment-modal-header h2 {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .comment-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.2s;
        }

        .comment-modal-close:hover {
            color: var(--text-dark);
        }

        .comment-item {
            background: var(--bg-light);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary);
            animation: slideIn 0.3s ease;
        }

        .comment-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .comment-item-service {
            font-weight: 700;
            color: var(--primary);
            font-size: 13px;
        }

        .comment-item-time {
            font-size: 12px;
            color: var(--text-light);
        }

        .comment-item-content {
            color: var(--text-dark);
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .comment-item-anonymous {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 8px;
            font-style: italic;
        }

        .comment-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        @media print {
            .language-selector,
            .progress-container,
            .button-group,
            .qr-container,
            .language-toggle,
            #step1,
            #step2,
            #step3,
            .countdown-container,
            .feedback-container,
            .comment-icon-btn,
            .comment-modal {
                display: none !important;
            }

            #step4 {
                display: block !important;
            }

            .ticket-number {
                color: black !important;
                -webkit-text-fill-color: black;
                background: none;
                border: 2px solid #000;
                padding: 16px;
                border-radius: 12px;
            }
        }

        @media (max-width: 640px) {
            .card {
                padding: 28px 20px;
            }

            h1 {
                font-size: 26px;
            }

            .progress-step-circle {
                width: 38px;
                height: 38px;
                font-size: 14px;
            }

            .progress-step-label {
                font-size: 10px;
            }

            .progress-steps::before,
            .progress-steps::after {
                top: 19px;
            }

            input,
            select,
            textarea {
                padding: 14px 16px;
            }

            .ticket-number {
                font-size: 36px;
            }

            .comment-icon-btn {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .comment-modal-content {
                border-radius: 16px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <!-- STEPPER -->
            <div class="progress-container">
                <div class="progress-steps" id="progressSteps">
                    <div class="progress-step active" id="progressStep1">
                        <div class="progress-step-circle">1</div>
                        <div class="progress-step-label" id="stepLabel1"><?php echo $t['step1']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep2">
                        <div class="progress-step-circle">2</div>
                        <div class="progress-step-label" id="stepLabel2"><?php echo $t['step2']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep3">
                        <div class="progress-step-circle">3</div>
                        <div class="progress-step-label" id="stepLabel3"><?php echo $t['step3']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep4">
                        <div class="progress-step-circle">✓</div>
                        <div class="progress-step-label" id="stepLabel4"><?php echo $t['step4']; ?></div>
                    </div>
                </div>
            </div>

            <!-- STEP 1 -->
            <div class="step active" id="step1">
                <h1 id="welcomeText"><?php echo $t['welcome']; ?></h1>
                <p class="subtitle" id="selectLangText"><?php echo $t['select_language']; ?></p>
                <div class="qr-container">
                    <p id="qrText"><?php echo $t['scan_qr']; ?></p>
                    <div id="qrcode"></div>
                </div>
                <div class="language-toggle">
                    <button class="lang-toggle-btn <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang="ar">🇸🇦 <?php echo $t['arabic']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">🇫🇷 <?php echo $t['french']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">🇬🇧 <?php echo $t['english']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'it' ? 'active' : ''; ?>" data-lang="it">🇮🇹 <?php echo $t['italian']; ?></button>
                </div>
                <div class="button-group">
                    <button class="btn btn-primary" id="nextStep1Btn"><?php echo $t['next']; ?> →</button>
                </div>
            </div>

            <!-- STEP 2 -->
            <div class="step" id="step2">
                <h1 id="serviceTitle"><?php echo $t['select_service']; ?></h1>
                <p class="subtitle" id="domainSubtitle"><?php echo $t['domain']; ?></p>
                <?php if ($db_error): ?>
                    <div class="db-error">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>
                <div class="service-buttons" id="serviceContainer">
                    <?php if (count($domaines) > 0): ?>
                        <?php foreach ($domaines as $domaine): ?>
                            <button class="service-btn" data-code="<?php echo htmlspecialchars($domaine['code']); ?>" data-name="<?php echo htmlspecialchars($domaine['nom']); ?>">
                                📌 <?php echo htmlspecialchars($domaine['nom']); ?>
                                <?php if (!empty($domaine['description'])): ?>
                                    <small><?php echo htmlspecialchars($domaine['description']); ?></small>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-services">❌ Aucun service disponible</div>
                    <?php endif; ?>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" id="backStep2Btn">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="nextStep2Btn"><?php echo $t['next']; ?> →</button>
                </div>
            </div>

            <!-- STEP 3 -->
            <div class="step" id="step3">
                <h1 id="formTitle"><?php echo $t['form_info']; ?></h1>
                <p class="subtitle" id="formSubtitle"></p>
                <div class="error-message" id="errorMessage"></div>
                <div class="form-group">
                    <label id="nameLabel"><?php echo $t['full_name']; ?></label>
                    <input type="text" id="fullName" placeholder="Jean Dupont">
                </div>
                <div class="form-group">
                    <label id="phoneLabel"><?php echo $t['phone']; ?></label>
                    <input type="tel" id="phone" placeholder="+212 6 12 34 56 78">
                </div>
                <div class="form-group">
                    <label id="statusLabel"><?php echo $t['status']; ?></label>
                    <select id="status">
                        <option value="">-- <?php echo $t['status']; ?> --</option>
                        <option value="standard"><?php echo $t['standard']; ?></option>
                        <option value="pregnant"><?php echo $t['pregnant']; ?></option>
                        <option value="disability"><?php echo $t['disability']; ?></option>
                    </select>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" id="backStep3Btn">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="submitBtn">📋 <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>

            <!-- STEP 4 -->
            <div class="step" id="step4">
                <h1 id="ticketConfirmTitle"><?php echo $t['ticket_confirmed']; ?></h1>
                <p class="subtitle" id="ticketNumberLabel"><?php echo $t['ticket_number']; ?></p>

                <div class="ticket-preview" id="ticketPreview">
                    <h2 id="ticketNumberTitle"><?php echo $t['ticket_number']; ?></h2>
                    <div class="ticket-number" id="ticketNumber">----</div>
                    <div class="ticket-info">
                        <p><strong id="fullNameLabelTicket"><?php echo $t['full_name']; ?>:</strong> <span id="ticketName">-</span></p>
                        <p><strong id="phoneLabelTicket"><?php echo $t['phone']; ?>:</strong> <span id="ticketPhone">-</span></p>
                        <p><strong id="statusLabelTicket"><?php echo $t['status']; ?>:</strong> <span id="ticketStatus">-</span></p>
                        <p><strong id="domainLabelTicket"><?php echo $t['domain']; ?>:</strong> <span id="ticketService">-</span></p>
                        <p><strong id="dateLabelTicket"><?php echo $t['date_time']; ?>:</strong> <span id="ticketDate"></span></p>
                    </div>

                    <div class="wait-info" id="waitInfo" style="display: none;">
                        <p><strong id="peopleBeforeLabel"></strong> <span id="peopleCount">0</span> <strong id="peopleBeforeText"></strong></p>
                        <p><strong id="estimatedWaitLabel"></strong> <span id="estimatedTime">0</span> <strong id="minutesLabel"></strong></p>
                    </div>
                </div>

                <div class="countdown-container" id="countdownContainer">
                    <div class="countdown-text" id="autoRemoveText"></div>
                    <div class="countdown-timer" id="countdownTimer">60</div>
                    <div class="countdown-bar">
                        <div class="countdown-bar-fill" id="countdownBarFill" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="feedback-container" id="feedbackContainer">
                    <div class="feedback-title" id="feedbackTitle"></div>
                    <div class="stars-rating" id="starsRating">
                        <button class="star-btn" data-star="1">⭐</button>
                        <button class="star-btn" data-star="2">⭐</button>
                        <button class="star-btn" data-star="3">⭐</button>
                        <button class="star-btn" data-star="4">⭐</button>
                        <button class="star-btn" data-star="5">⭐</button>
                    </div>
                    <label id="commentLabel"><?php echo $t['share_comment']; ?></label>
                    <textarea class="feedback-comment" id="feedbackComment" placeholder="<?php echo $t['comment_placeholder']; ?>"></textarea>
                    <button class="feedback-submit-btn" id="feedbackSubmitBtn">Envoyer l'avis</button>
                    <div class="feedback-thank-you" id="feedbackThankYou"></div>
                </div>

                <div class="button-group">
                    <button class="btn btn-secondary" id="downloadBtn">⬇️ <?php echo $t['download_ticket']; ?></button>
                    <button class="btn btn-primary" id="printBtn">🖨️ <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ BOUTON COMMENTAIRES ADMIN -->
    <button class="comment-icon-btn" id="commentIconBtn" title="Commentaires">
        <i class="fas fa-comments"></i>
        <span class="comment-badge" id="commentBadge" style="display: none;">0</span>
    </button>

    <!-- ✅ MODAL COMMENTAIRES -->
    <div class="comment-modal" id="commentModal">
        <div class="comment-modal-content">
            <div class="comment-modal-header">
                <h2>💬 Commentaires Clients</h2>
                <button class="comment-modal-close" id="commentModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="commentsList">
                <div class="comment-empty">Aucun commentaire pour le moment...</div>
            </div>
        </div>
    </div>

    <div class="language-menu-backdrop" id="languageMenuBackdrop"></div>
    <div class="language-selector">
        <button class="language-toggle-btn-float" id="floatLangBtn">🌐</button>
        <div class="language-dropdown-menu" id="languageDropdownMenu">
            <button class="language-menu-item <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang="ar">العربية</button>
            <button class="language-menu-item <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">Français</button>
            <button class="language-menu-item <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">English</button>
            <button class="language-menu-item <?php echo $lang === 'it' ? 'active' : ''; ?>" data-lang="it">Italiano</button>
        </div>
    </div>

    <script>
        const translationsJS = {
            fr: {
                step1: "ACCUEIL", step2: "SERVICE", step3: "INFOS", step4: "TICKET",
                welcome: "Bienvenue à Smart Queue Management",
                select_language: "Sélectionnez votre langue",
                scan_qr: "Scannez ce code QR pour continuer sur mobile",
                select_service: "Sélectionnez un service",
                domain: "Domaine",
                next: "Suivant",
                back: "Retour",
                full_name: "Nom Complet",
                phone: "Téléphone",
                status: "État",
                standard: "Standard",
                pregnant: "Femme enceinte",
                disability: "PMR/Handicap",
                print_ticket: "Imprimer Ticket",
                download_ticket: "Télécharger le Ticket",
                ticket_number: "Numéro de Ticket",
                form_info: "Veuillez saisir vos informations",
                ticket_confirmed: "Ticket généré avec succès",
                new_ticket: "Nouveau ticket",
                select_service_first: "Veuillez sélectionner un service",
                processing: "Traitement...",
                printing: "Impression...",
                error_empty: "Tous les champs sont obligatoires",
                date_time: "Date/Heure",
                people_before: "personnes avant vous",
                estimated_wait: "Temps d'attente estimé",
                minutes: "minutes",
                feedback_title: "Votre avis compte",
                thank_you_feedback: "Merci pour votre avis !",
                autoremove_in: "Réinitialisation automatique dans",
                seconds: "secondes",
                voice_your_number: "Votre numéro est le",
                share_comment: "Partager un commentaire (optionnel)",
                comment_placeholder: "Votre avis sur notre service..."
            },
            ar: {
                step1: "الرئيسية", step2: "الخدمة", step3: "المعلومات", step4: "التذكرة",
                welcome: "مرحبا بك في نظام إدارة الطوابير الذكية",
                select_language: "اختر لغتك",
                scan_qr: "امسح رمز QR هذا للمتابعة على الهاتف المحمول",
                select_service: "اختر خدمة",
                domain: "المجال",
                next: "التالي",
                back: "العودة",
                full_name: "الاسم الكامل",
                phone: "الهاتف",
                status: "الحالة",
                standard: "عادي",
                pregnant: "حامل",
                disability: "الإعاقة",
                print_ticket: "طباعة التذكرة",
                download_ticket: "تحميل التذكرة",
                ticket_number: "رقم التذكرة",
                form_info: "يرجى إدخال معلوماتك",
                ticket_confirmed: "تم إنشاء التذكرة بنجاح",
                new_ticket: "تذكرة جديدة",
                select_service_first: "الرجاء اختيار خدمة",
                processing: "جاري المعالجة...",
                printing: "جاري الطباعة...",
                error_empty: "جميع الحقول مطلوبة",
                date_time: "التاريخ/الوقت",
                people_before: "أشخاص قبلك",
                estimated_wait: "الوقت المتوقع للانتظار",
                minutes: "دقيقة",
                feedback_title: "رأيك يهمنا",
                thank_you_feedback: "شكرا على رأيك !",
                autoremove_in: "إعادة تعيين تلقائية في",
                seconds: "ثانية",
                voice_your_number: "رقمك هو",
                share_comment: "مشاركة تعليق (اختياري)",
                comment_placeholder: "رأيك في خدمتنا..."
            },
            en: {
                step1: "HOME", step2: "SERVICE", step3: "INFO", step4: "TICKET",
                welcome: "Welcome to Smart Queue Management",
                select_language: "Select your language",
                scan_qr: "Scan this QR code to continue on mobile",
                select_service: "Select a service",
                domain: "Domain",
                next: "Next",
                back: "Back",
                full_name: "Full name",
                phone: "Phone",
                status: "Status",
                standard: "Standard",
                pregnant: "Pregnant",
                disability: "Disability",
                print_ticket: "Print ticket",
                download_ticket: "Download Ticket",
                ticket_number: "Ticket number",
                form_info: "Please enter your information",
                ticket_confirmed: "Ticket generated successfully",
                new_ticket: "New ticket",
                select_service_first: "Please select a service",
                processing: "Processing...",
                printing: "Printing...",
                error_empty: "All fields are required",
                date_time: "Date/Time",
                people_before: "people before you",
                estimated_wait: "Estimated wait time",
                minutes: "minutes",
                feedback_title: "Your feedback matters",
                thank_you_feedback: "Thank you for your feedback!",
                autoremove_in: "Automatic reset in",
                seconds: "seconds",
                voice_your_number: "Your number is",
                share_comment: "Share a comment (optional)",
                comment_placeholder: "Your opinion on our service..."
            },
            it: {
                step1: "HOME", step2: "SERVIZIO", step3: "INFO", step4: "BIGLIETTO",
                welcome: "Benvenuto in Smart Queue Management",
                select_language: "Seleziona la tua lingua",
                scan_qr: "Scansiona questo codice QR per continuare sul mobile",
                select_service: "Seleziona un servizio",
                domain: "Dominio",
                next: "Avanti",
                back: "Indietro",
                full_name: "Nome completo",
                phone: "Telefono",
                status: "Stato",
                standard: "Standard",
                pregnant: "In attesa",
                disability: "Disabilità",
                print_ticket: "Stampa biglietto",
                download_ticket: "Scarica il biglietto",
                ticket_number: "Numero del biglietto",
                form_info: "Inserisci le tue informazioni",
                ticket_confirmed: "Biglietto generato con successo",
                new_ticket: "Nuovo biglietto",
                select_service_first: "Seleziona un servizio",
                processing: "Elaborazione...",
                printing: "Stampa in corso...",
                error_empty: "Tutti i campi sono obbligatori",
                date_time: "Data/Ora",
                people_before: "persone prima di te",
                estimated_wait: "Tempo di attesa stimato",
                minutes: "minuti",
                feedback_title: "Il tuo feedback è importante",
                thank_you_feedback: "Grazie per il tuo feedback!",
                autoremove_in: "Ripristino automatico tra",
                seconds: "secondi",
                voice_your_number: "Il tuo numero è",
                share_comment: "Condividi un commento (facoltativo)",
                comment_placeholder: "Il tuo parere sul nostro servizio..."
            }
        };

        let currentStep = 1;
        let selectedService = '';
        let selectedServiceCode = '';
        let currentLanguage = '<?php echo $lang; ?>';
        let isRTL = <?php echo $isRTL ? 'true' : 'false'; ?>;
        let countdownInterval = null;
        let countdownTime = 60;  // ✅ 60 SECONDES
        let selectedFeedbackRating = 0;
        let currentTicketNumber = '';

        // ✅ FONCTION 1 : LocalStorage
        function loadUserDataFromStorage() {
            const savedName = localStorage.getItem('userFullName');
            const savedPhone = localStorage.getItem('userPhone');
            if (savedName) document.getElementById('fullName').value = savedName;
            if (savedPhone) document.getElementById('phone').value = savedPhone;
        }

        function saveUserDataToStorage(name, phone) {
            localStorage.setItem('userFullName', name);
            localStorage.setItem('userPhone', phone);
        }

        // ✅ FONCTION 3 : Text-to-Speech
        function speakTicketNumber(ticketNumber) {
            if (!('speechSynthesis' in window)) return;
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            const textToSpeak = `${t.voice_your_number} ${ticketNumber}`;
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(textToSpeak);
            switch(currentLanguage) {
                case 'ar': utterance.lang = 'ar-SA'; break;
                case 'en': utterance.lang = 'en-US'; break;
                case 'it': utterance.lang = 'it-IT'; break;
                default: utterance.lang = 'fr-FR';
            }
            utterance.rate = 0.9;
            speechSynthesis.speak(utterance);
        }

        // ✅ FONCTION 3 : Confetti
        function triggerConfetti() {
            if (typeof confetti === 'function') {
                confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
                setTimeout(() => {
                    confetti({ particleCount: 50, spread: 100, origin: { y: 0.6 } });
                }, 300);
            }
        }

        // ✅ FONCTION 4 : Download PNG
        async function downloadTicketAsPNG() {
            const btn = document.getElementById('downloadBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ Génération...';
            try {
                const ticketElement = document.getElementById('ticketPreview');
                const canvas = await html2canvas(ticketElement, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    logging: false
                });
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = `ticket-${currentTicketNumber}-${Date.now()}.png`;
                link.click();
                btn.textContent = '✅ Téléchargé!';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 2000);
            } catch (error) {
                btn.textContent = '❌ Erreur';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        }

        // ✅ FONCTION 2 : Countdown (60 SECONDES)
        function startCountdown(seconds = 60) {
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            countdownTime = seconds;
            const totalTime = seconds;
            document.getElementById('autoRemoveText').textContent = t.autoremove_in;
            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                const timerEl = document.getElementById('countdownTimer');
                const barEl = document.getElementById('countdownBarFill');
                if (timerEl) timerEl.textContent = countdownTime;
                const percentage = (countdownTime / totalTime) * 100;
                if (barEl) barEl.style.width = percentage + '%';
                countdownTime--;
                if (countdownTime < 0) {
                    clearInterval(countdownInterval);
                    resetForm();
                }
            }, 1000);
        }

        // ✅ NOUVELLE FONCTION: Charger et afficher les commentaires
        function loadComments() {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_comments'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const commentsList = document.getElementById('commentsList');
                    const badge = document.getElementById('commentBadge');
                    const comments = data.comments || [];
                    
                    // Mettre à jour le badge
                    if (comments.length > 0) {
                        badge.textContent = comments.length;
                        badge.style.display = 'flex';
                        document.getElementById('commentIconBtn').style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                        document.getElementById('commentIconBtn').style.display = 'none';
                    }
                    
                    // Afficher les commentaires
                    if (comments.length === 0) {
                        commentsList.innerHTML = '<div class="comment-empty">Aucun commentaire pour le moment...</div>';
                    } else {
                        commentsList.innerHTML = comments.map(comment => `
                            <div class="comment-item">
                                <div class="comment-item-header">
                                    <span class="comment-item-service">📌 ${comment.service}</span>
                                    <span class="comment-item-time">${new Date(comment.created_at).toLocaleString()}</span>
                                </div>
                                <div class="comment-item-content">"${comment.contenu}"</div>
                                <div class="comment-item-anonymous">— Anonyme</div>
                            </div>
                        `).join('');
                    }
                }
            })
            .catch(console.error);
        }

        // ✅ FONCTION 6 : Feedback
        function initFeedbackStars() {
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            document.getElementById('feedbackTitle').textContent = t.feedback_title;
            document.getElementById('commentLabel').textContent = t.share_comment;
            document.getElementById('feedbackComment').placeholder = t.comment_placeholder;
            
            document.querySelectorAll('.star-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    selectedFeedbackRating = parseInt(this.getAttribute('data-star'));
                    document.querySelectorAll('.star-btn').forEach((b, idx) => {
                        if (idx < selectedFeedbackRating) b.classList.add('active');
                        else b.classList.remove('active');
                    });
                });
            });
            document.getElementById('feedbackSubmitBtn').addEventListener('click', submitFeedback);
        }

        function submitFeedback() {
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            if (selectedFeedbackRating === 0) {
                alert(t.error_empty);
                return;
            }
            const comment = document.getElementById('feedbackComment').value;
            const fd = new FormData();
            fd.append('action', 'save_feedback');
            fd.append('ticket_number', currentTicketNumber);
            fd.append('rating', selectedFeedbackRating);
            fd.append('comment', comment);
            fd.append('service', selectedService);
            
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('feedbackContainer').style.display = 'none';
                        const thankYou = document.getElementById('feedbackThankYou');
                        thankYou.textContent = t.thank_you_feedback;
                        thankYou.style.display = 'block';
                        
                        // ✅ Recharger les commentaires après soumission
                        setTimeout(loadComments, 1000);
                    }
                });
        }

        // Progress
        function updateProgress(step) {
            const percent = ((step - 1) / 3) * 100;
            const styleId = 'progressBarStyle';
            let styleEl = document.getElementById(styleId);
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }
            if (isRTL) {
                styleEl.textContent = `.progress-steps::after { width: ${percent}%; right: 0; left: auto; }`;
            } else {
                styleEl.textContent = `.progress-steps::after { width: ${percent}%; left: 0; right: auto; }`;
            }
            for (let i = 1; i <= 4; i++) {
                const stepEl = document.getElementById('progressStep' + i);
                stepEl.classList.remove('active', 'completed');
                if (i < step) stepEl.classList.add('completed');
                else if (i === step) stepEl.classList.add('active');
            }
        }

        // Language
        function changeLanguage(lang) {
            if (lang === currentLanguage) return;
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=set_language&language=' + encodeURIComponent(lang)
            }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
        }

        function toggleLanguageMenu() {
            const menu = document.getElementById('languageDropdownMenu');
            const backdrop = document.getElementById('languageMenuBackdrop');
            menu.classList.toggle('active');
            backdrop.classList.toggle('active');
        }

        function closeLanguageMenu() {
            document.getElementById('languageDropdownMenu').classList.remove('active');
            document.getElementById('languageMenuBackdrop').classList.remove('active');
        }

        // QR Code
        function generateQRCode() {
            const el = document.getElementById('qrcode');
            if (el) {
                el.innerHTML = '';
                new QRCode(el, {
                    text: window.location.href.split('?')[0],
                    width: 180,
                    height: 180,
                    colorDark: '#5a67d8',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }

        // Navigation
        function goToStep(n) {
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            if (n === 3 && !selectedService) {
                alert(t.select_service_first);
                return;
            }
            if (n === 3) loadUserDataFromStorage();
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + n).classList.add('active');
            currentStep = n;
            updateProgress(n);
        }

        // Service selection
        function initServiceButtons() {
            document.querySelectorAll('.service-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    selectedService = this.getAttribute('data-name');
                    selectedServiceCode = this.getAttribute('data-code');
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=set_service&service=' + encodeURIComponent(selectedService) + '&service_code=' + encodeURIComponent(selectedServiceCode)
                    });
                });
            });
        }

        // Form submission
        function submitForm() {
            const name = document.getElementById('fullName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const status = document.getElementById('status').value;
            const errEl = document.getElementById('errorMessage');
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            
            if (!name || !phone || !status || !selectedService) {
                errEl.textContent = t.error_empty;
                errEl.style.display = 'block';
                return;
            }
            errEl.style.display = 'none';
            
            saveUserDataToStorage(name, phone);
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.innerHTML = '⏳ ' + t.processing;
            
            const fd = new FormData();
            fd.append('action', 'save_ticket');
            fd.append('name', name);
            fd.append('phone', phone);
            fd.append('status', status);
            fd.append('service', selectedService);
            fd.append('service_code', selectedServiceCode);
            
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        currentTicketNumber = data.ticket_number;
                        document.getElementById('ticketNumber').innerText = data.ticket_number;
                        document.getElementById('ticketName').innerText = data.name;
                        document.getElementById('ticketPhone').innerText = data.phone;
                        let statusText = data.status === 'standard' ? t.standard : (data.status === 'pregnant' ? t.pregnant : t.disability);
                        document.getElementById('ticketStatus').innerText = statusText;
                        document.getElementById('ticketService').innerText = data.service;
                        document.getElementById('ticketDate').innerText = new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString();
                        
                        if (data.wait_count > 0) {
                            document.getElementById('waitInfo').style.display = 'block';
                            document.getElementById('peopleCount').innerText = data.wait_count;
                            document.getElementById('estimatedTime').innerText = data.estimated_wait;
                            document.getElementById('peopleBeforeLabel').innerText = 'Il y a';
                            document.getElementById('peopleBeforeText').innerText = t.people_before;
                            document.getElementById('estimatedWaitLabel').innerText = t.estimated_wait + ':';
                            document.getElementById('minutesLabel').innerText = t.minutes;
                        }
                        
                        goToStep(4);
                        setTimeout(() => {
                            triggerConfetti();
                            speakTicketNumber(data.ticket_number);
                        }, 500);
                        startCountdown(60);  // ✅ 60 SECONDES
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.innerHTML = '📋 ' + t.print_ticket;
                });
        }

        // Print
        function printTicket() {
            const btn = document.getElementById('printBtn');
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            const orig = btn.innerHTML;
            btn.innerHTML = '⏳ ' + t.printing;
            btn.disabled = true;
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }, 1500);
            }, 100);
        }

        // Reset
        function resetForm() {
            if (countdownInterval) clearInterval(countdownInterval);
            if (window.speechSynthesis) speechSynthesis.cancel();
            document.getElementById('fullName').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('status').value = '';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('ticketNumber').innerHTML = '----';
            document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
            selectedService = '';
            selectedServiceCode = '';
            selectedFeedbackRating = 0;
            document.querySelectorAll('.star-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('feedbackComment').value = '';
            document.getElementById('feedbackContainer').style.display = 'block';
            document.getElementById('feedbackThankYou').style.display = 'none';
            document.getElementById('waitInfo').style.display = 'none';
            goToStep(1);
            generateQRCode();
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            initServiceButtons();
            generateQRCode();
            updateProgress(1);
            initFeedbackStars();
            loadComments();  // ✅ Charger les commentaires au démarrage
            
            document.getElementById('nextStep1Btn')?.addEventListener('click', () => goToStep(2));
            document.getElementById('backStep2Btn')?.addEventListener('click', () => goToStep(1));
            document.getElementById('nextStep2Btn')?.addEventListener('click', () => goToStep(3));
            document.getElementById('backStep3Btn')?.addEventListener('click', () => goToStep(2));
            document.getElementById('submitBtn')?.addEventListener('click', submitForm);
            document.getElementById('printBtn')?.addEventListener('click', printTicket);
            document.getElementById('downloadBtn')?.addEventListener('click', downloadTicketAsPNG);
            
            // ✅ Modal commentaires
            document.getElementById('commentIconBtn')?.addEventListener('click', () => {
                document.getElementById('commentModal').classList.add('active');
            });
            document.getElementById('commentModalClose')?.addEventListener('click', () => {
                document.getElementById('commentModal').classList.remove('active');
            });
            document.getElementById('commentModal')?.addEventListener('click', (e) => {
                if (e.target.id === 'commentModal') {
                    document.getElementById('commentModal').classList.remove('active');
                }
            });
            
            document.querySelectorAll('.lang-toggle-btn, .language-menu-item').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const lang = this.getAttribute('data-lang');
                    if (lang) changeLanguage(lang);
                    closeLanguageMenu();
                });
            });
            
            document.getElementById('floatLangBtn')?.addEventListener('click', toggleLanguageMenu);
            document.getElementById('languageMenuBackdrop')?.addEventListener('click', closeLanguageMenu);
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLanguageMenu(); });
            document.addEventListener('keypress', e => { if (e.key === 'Enter' && currentStep === 3) submitForm(); });
            
            // ✅ Recharger les commentaires toutes les 10 secondes
            setInterval(loadComments, 10000);
        });
    </script>
</body>
</html>