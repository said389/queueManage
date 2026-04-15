<?php

class AdminDeskList extends Page {

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle($this->getPageTitle());

        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }

    /** ✅ LAYOUT PRINCIPAL */
    private function getLayout() {
        global $gvPath;
        $tableBody = $this->getTableBody();

        return <<<HTML
<div class="layout">
    <!-- Sidebar Toggle pour mobile -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h2>FastQueue</h2>
            </div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Opérateurs</span>
            </a>
            <a href="$gvPath/application/adminDeskList" class="nav-item active">
                <i class="fas fa-desktop"></i>
                <span>Compteurs</span>
            </a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item">
                <i class="fas fa-folder-tree"></i>
                <span>Domaines thématiques</span>
            </a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Appareils</span>
            </a>
            <a href="$gvPath/application/adminStats" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            <a href="$gvPath/application/adminSettings" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Gestion des compteurs</h1>
                <p class="subtitle">Gérez les compteurs d'affichage de votre système</p>
            </div>

            <!-- Table des compteurs -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-desktop"></i>
                    <h3>Liste des compteurs</h3>
                </div>
                <div class="table-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Adresse IP</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            $tableBody
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Boutons Ajouter -->
            <div class="actions-section">
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Ajouter un compteur
                </button>
                <button class="btn btn-primary" onclick="openModalPairing()">
                    <i class="fas fa-computer"></i> Ajouter cet ordinateur
                </button>
            </div>
        </div>
    </main>
</div>

<!-- Overlay pour mobile -->
<div class="overlay" id="overlay"></div>

<!-- ===== MODAL ===== -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOnBackdrop(event)">
    <div class="modal" id="deskModal">
        <div class="modal-header">
            <div class="modal-title">
                <div class="modal-icon"><i class="fas fa-desktop" id="modalIcon"></i></div>
                <div>
                    <h2 id="modalTitle">Ajouter un compteur</h2>
                    <p id="modalSubtitle">Remplissez les informations du compteur</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="modalMessage" class="modal-message" style="display:none;"></div>

        <form id="deskForm" method="post" action="$gvPath/application/adminDeskEdit">
            <input type="hidden" name="desk_id" id="modal_desk_id" value="0" />
            <input type="hidden" name="pairing" id="modal_pairing" value="0" />

            <div class="modal-body">
                <div class="form-group">
                    <label for="modal_desk_number">
                        <i class="fas fa-hashtag"></i> Numéro du compteur
                    </label>
                    <input type="number" name="desk_number" id="modal_desk_number"
                           placeholder="ex: 1, 2, 3..." min="1" max="99" autocomplete="off" />
                </div>

                <div class="form-group">
                    <label for="modal_desk_ip_address">
                        <i class="fas fa-wifi"></i> Adresse IP
                    </label>
                    <input type="text" name="desk_ip_address" id="modal_desk_ip_address"
                           placeholder="ex: 192.168.1.100" autocomplete="off" />
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitLabel">Enregistrer</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ---- Sidebar mobile ---- */
const mobileToggle = document.getElementById('mobileToggle');
const sidebar      = document.getElementById('sidebar');
const overlay      = document.getElementById('overlay');

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });
}
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});

/* ---- Modal ---- */
function openModal(deskId, deskNumber, deskIp) {
    const isEdit = !!deskId;

    document.getElementById('modal_desk_id').value         = deskId     || 0;
    document.getElementById('modal_desk_number').value     = deskNumber || '';
    document.getElementById('modal_desk_ip_address').value = deskIp     || '';
    document.getElementById('modal_pairing').value         = 0;

    document.getElementById('modalTitle').textContent    = isEdit ? 'Modifier le compteur' : 'Ajouter un compteur';
    document.getElementById('modalSubtitle').textContent = isEdit ? 'Modifiez les informations ci-dessous' : 'Remplissez les informations du compteur';
    document.getElementById('submitLabel').textContent   = isEdit ? 'Modifier' : 'Enregistrer';

    hideMessage();
    document.getElementById('modalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';

    setTimeout(() => document.getElementById('modal_desk_number').focus(), 300);
}

function openModalPairing() {
    const isEdit = false;

    document.getElementById('modal_desk_id').value         = 0;
    document.getElementById('modal_desk_number').value     = '';
    document.getElementById('modal_desk_ip_address').value = '';
    document.getElementById('modal_pairing').value         = 1;

    document.getElementById('modalTitle').textContent    = 'Ajouter cet ordinateur';
    document.getElementById('modalSubtitle').textContent = 'Complétez avec le numéro du compteur';
    document.getElementById('submitLabel').textContent   = 'Enregistrer';

    hideMessage();
    document.getElementById('modalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';

    setTimeout(() => document.getElementById('modal_desk_number').focus(), 300);
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}

function closeModalOnBackdrop(e) {
    if (e.target === document.getElementById('modalBackdrop')) closeModal();
}

function showMessage(text, type) {
    const el = document.getElementById('modalMessage');
    el.textContent = text;
    el.className   = 'modal-message ' + type;
    el.style.display = 'block';
}
function hideMessage() {
    const el = document.getElementById('modalMessage');
    el.style.display = 'none';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>
HTML;
    }


    /** ✅ TABLE BODY */
    private function getTableBody() {
        global $gvPath;

        $desks = Desk::fromDatabaseCompleteList();

        if (!count($desks)) {
            return '<tr><td colspan="3" class="empty-row">Aucun compteur disponible</td></tr>';
        }

        $html = "";

        foreach ($desks as $desk) {

            $id  = $desk->getId();
            $num = htmlspecialchars($desk->getNumber());
            $ip  = htmlspecialchars($desk->getIpAddress());

            $html .= <<<HTML
<tr>
    <td>$num</td>
    <td>$ip</td>
    <td class="actions-cell">
        <button class="action-btn edit"
                onclick="openModal($id, '$num', '$ip')"
                title="Modifier">
            <i class="fas fa-edit"></i>
        </button>
        <a href="$gvPath/ajax/removeRecord?desk_id=$id" class="action-btn delete" title="Supprimer"
           onclick="return confirm('Confirmez-vous la suppression?');">
            <i class="fas fa-trash"></i>
        </a>
    </td>
</tr>
HTML;
        }

        return $html;
    }


    public function getPageTitle() {
        return "Gestion des compteurs";
    }


    /** ✅ CSS COMPLET */
    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f5f7fb;
        color: #1a1a2e;
        overflow-x: hidden;
    }

    /* Layout */
    .layout {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 1000;
        transition: transform 0.3s ease;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 30px 25px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .logo i {
        font-size: 28px;
        color: #6C63FF;
    }

    .logo h2 {
        font-size: 22px;
        font-weight: 700;
    }

    .admin-badge {
        background: rgba(108, 99, 255, 0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #6C63FF;
        display: inline-block;
    }

    .sidebar-nav {
        flex: 1;
        padding: 20px 0;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 25px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    }

    .nav-item i {
        width: 20px;
        font-size: 18px;
    }

    .nav-item:hover {
        background: rgba(108, 99, 255, 0.1);
        color: #fff;
    }

    .nav-item.active {
        background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1));
        color: #fff;
        border-right: 3px solid #6C63FF;
    }

    .sidebar-footer {
        padding: 20px 0;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .logout {
        color: #ff6b6b;
    }

    .logout:hover {
        background: rgba(255, 107, 107, 0.1);
    }

    /* Mobile Toggle */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #6C63FF;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 12px;
        color: white;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Overlay */
    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    .overlay.show {
        display: block;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 280px;
        min-height: 100vh;
        background: #f5f7fb;
    }

    .content-wrapper {
        padding: 30px 40px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
    }

    .subtitle {
        color: #666;
        font-size: 14px;
    }

    /* Cards */
    .card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .card-header i {
        color: #6C63FF;
        font-size: 20px;
    }

    .card-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
    }

    .stats-table {
        width: 100%;
        border-collapse: collapse;
    }

    .stats-table thead {
        background: #f8f9fa;
    }

    .stats-table th {
        padding: 12px 15px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        border-bottom: 2px solid #eee;
    }

    .stats-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .stats-table tbody tr:hover {
        background: #f8f9fa;
    }

    .empty-row {
        text-align: center;
        color: #999;
        padding: 20px !important;
    }

    /* Actions */
    .actions-cell {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .action-btn.edit {
        background: #6C63FF;
        color: white;
    }

    .action-btn.edit:hover {
        background: #5149E8;
        transform: scale(1.05);
    }

    .action-btn.delete {
        background: #ff6b6b;
        color: white;
    }

    .action-btn.delete:hover {
        background: #ee5a52;
        transform: scale(1.05);
    }

    /* Buttons */
    .actions-section {
        margin-top: 25px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #6C63FF;
        color: white;
    }

    .btn-primary:hover {
        background: #5149E8;
    }

    .btn-secondary {
        background: #e9ecef;
        color: #1a1a2e;
    }

    .btn-secondary:hover {
        background: #dee2e6;
    }

    /* ============ MODAL ============ */
    .modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(10, 10, 30, 0.65);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none;
        transition: opacity 0.25s ease;
        padding: 20px;
    }
    .modal-backdrop.show {
        opacity: 1; pointer-events: all;
    }
    .modal {
        background: #fff;
        border-radius: 20px;
        width: 100%; max-width: 560px;
        box-shadow: 0 24px 60px rgba(108,99,255,0.2), 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(20px) scale(0.97);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-backdrop.show .modal {
        transform: translateY(0) scale(1);
    }

    /* Modal Header */
    .modal-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 24px 28px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .modal-title { display: flex; align-items: center; gap: 16px; }
    .modal-icon {
        width: 48px; height: 48px;
        background: rgba(108,99,255,0.25);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: #6C63FF; font-size: 20px;
        flex-shrink: 0;
    }
    .modal-title h2 {
        font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 2px;
    }
    .modal-title p { font-size: 12px; color: rgba(255,255,255,0.55); margin: 0; }
    .modal-close {
        background: rgba(255,255,255,0.1); border: none;
        width: 36px; height: 36px; border-radius: 10px;
        color: rgba(255,255,255,0.7); font-size: 16px; cursor: pointer;
        transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .modal-close:hover { background: rgba(255,107,107,0.3); color: #ff6b6b; }

    /* Modal Message */
    .modal-message {
        margin: 16px 28px 0;
        padding: 12px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 500;
    }
    .modal-message.error { background: #fff0f0; color: #c0392b; border-left: 3px solid #e74c3c; }
    .modal-message.success { background: #f0fff4; color: #1e7e34; border-left: 3px solid #28a745; }

    /* Modal Body */
    .modal-body { padding: 24px 28px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group + .form-group { margin-top: 16px; }

    .form-group label {
        font-size: 12px; font-weight: 600;
        color: #1a1a2e; text-transform: uppercase; letter-spacing: 0.5px;
        display: flex; align-items: center; gap: 6px;
    }
    .form-group label i { color: #6C63FF; font-size: 11px; }

    .form-group input[type="text"],
    .form-group input[type="number"] {
        width: 100%; padding: 11px 14px;
        border: 2px solid #eee; border-radius: 10px;
        font-size: 14px; color: #1a1a2e;
        font-family: inherit;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        outline: none;
    }
    .form-group input:focus {
        border-color: #6C63FF;
        box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
    }
    .form-group input::placeholder { color: #aaa; }

    /* Modal Footer */
    .modal-footer {
        padding: 16px 28px 24px;
        display: flex; align-items: center; justify-content: flex-end; gap: 12px;
        border-top: 1px solid #f0f0f0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .mobile-toggle {
            display: flex;
        }

        .main-content {
            margin-left: 0;
        }

        .content-wrapper {
            padding: 20px;
        }

        .page-header h1 {
            font-size: 22px;
        }

        .card {
            padding: 15px;
        }

        .stats-table th,
        .stats-table td {
            padding: 10px;
            font-size: 12px;
        }

        .actions-section {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .modal { max-width: 100%; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 14px 20px 20px; }
    }

</style>
CSS;
    }
}