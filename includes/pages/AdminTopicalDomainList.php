<?php

class AdminTopicalDomainList extends Page {

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

    private function getLayout() {
        global $gvPath;
        $table = $this->getTableBody();

        return <<<HTML
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-chevron-left"></i></button>
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-tasks"></i><h2 class="logo-text">FastQueue</h2></div>
            <span class="admin-badge">Administrateur</span>
        </div>
        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item" title="Tableau de bord"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Tableau de bord</span></a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item" title="Opérateurs"><i class="fas fa-users"></i><span class="nav-text">Opérateurs</span></a>
            <a href="$gvPath/application/adminDeskList" class="nav-item" title="Compteurs"><i class="fas fa-desktop"></i><span class="nav-text">Compteurs</span></a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item active" title="Domaines"><i class="fas fa-folder-tree"></i><span class="nav-text">Domaines thématiques</span></a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item" title="Appareils"><i class="fas fa-mobile-alt"></i><span class="nav-text">Appareils</span></a>
            <a href="$gvPath/application/adminStats" class="nav-item" title="Statistiques"><i class="fas fa-chart-line"></i><span class="nav-text">Statistiques</span></a>
            <a href="$gvPath/application/adminSettings" class="nav-item" title="Paramètres"><i class="fas fa-cog"></i><span class="nav-text">Paramètres</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout" title="Déconnexion"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Déconnexion</span></a>
        </div>
    </aside>

    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Gestion des domaines thématiques</h1>
                <p class="subtitle">Gérez les domaines thématiques de votre système</p>
            </div>

            <div class="toast" id="toast"><i class="toast-icon" id="toastIcon"></i><span id="toastMsg"></span></div>

            <div class="card">
                <div class="card-header"><i class="fas fa-folder-tree"></i><h3>Liste des domaines thématiques</h3></div>
                <div class="table-container">
                    <table class="stats-table">
                        <thead><tr><th>Code</th><th>Nom</th><th>Description</th><th>Actif?</th><th>Actions</th></tr></thead>
                        <tbody id="tdTableBody">$table</tbody>
                    </table>
                </div>
            </div>

            <div class="actions-section">
                <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Ajouter un domaine</button>
            </div>
        </div>
    </main>
</div>

<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOnBackdrop(event)">
    <div class="modal" id="tdModal">
        <div class="modal-header">
            <div class="modal-title">
                <div class="modal-icon"><i class="fas fa-folder-tree" id="modalIcon"></i></div>
                <div><h2 id="modalTitle">Ajouter un domaine thématique</h2><p id="modalSubtitle">Remplissez les informations du domaine</p></div>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="modalMessage" class="modal-message" style="display:none;"></div>
        <form id="tdForm" method="post" action="$gvPath/application/adminTopicalDomainEdit">
            <input type="hidden" name="td_id" id="modal_td_id" value="0" />
            <div class="modal-body">
                <div class="form-group">
                    <label for="modal_td_code"><i class="fas fa-barcode"></i> Code</label>
                    <div class="select-wrapper">
                        <select name="td_code" id="modal_td_code">{$this->getComboBoxOptions()}</select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_td_name"><i class="fas fa-heading"></i> Nom</label>
                    <input type="text" name="td_name" id="modal_td_name" placeholder="Nom du domaine" autocomplete="off" />
                </div>
                <div class="form-group">
                    <label for="modal_td_description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="td_description" id="modal_td_description" rows="3" placeholder="Description du domaine"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_td_icon"><i class="fas fa-icons"></i> Icône</label>
                        <div class="select-wrapper">
                            <select name="td_icon" id="modal_td_icon">{$this->getComboBoxIconOptions()}</select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="modal_td_color"><i class="fas fa-palette"></i> Couleur</label>
                        <div class="select-wrapper">
                            <select name="td_color" id="modal_td_color">{$this->getComboBoxColorOptions()}</select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group form-group-check">
                    <label class="check-label">
                        <div class="custom-check">
                            <input type="checkbox" name="td_active" id="modal_td_active" value="1" />
                            <span class="check-box"></span>
                        </div>
                        <span>Domaine actif</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="submitLabel">Enregistrer</span></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="deleteBackdrop" onclick="closeDeleteOnBackdrop(event)">
    <div class="modal modal-sm" id="deleteModal">
        <div class="modal-header modal-header-danger">
            <div class="modal-title">
                <div class="modal-icon modal-icon-danger"><i class="fas fa-folder-minus"></i></div>
                <div><h2>Supprimer le domaine</h2><p>Cette action est irréversible</p></div>
            </div>
            <button class="modal-close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="delete-confirm-text">Confirmez-vous la suppression du domaine <strong id="deleteTargetName"></strong> ?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()"><i class="fas fa-times"></i> Annuler</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
    </div>
</div>

<script>
const gvPath = "$gvPath";
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
let sidebarIsExpanded = true;

function collapseSidebar() {
    sidebar.classList.add('collapsed');
    mainContent.classList.add('expanded');
    sidebarIsExpanded = false;
    updateToggleIcon();
    localStorage.setItem('sidebarState', 'collapsed');
}

function expandSidebar() {
    sidebar.classList.remove('collapsed');
    mainContent.classList.remove('expanded');
    sidebarIsExpanded = true;
    updateToggleIcon();
    localStorage.setItem('sidebarState', 'expanded');
}

function toggleSidebar() {
    sidebarIsExpanded ? collapseSidebar() : expandSidebar();
}

function updateToggleIcon() {
    const icon = sidebarToggle.querySelector('i');
    if (sidebarIsExpanded) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
    } else {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    }
}

sidebarToggle.addEventListener('click', function(e) {
    e.preventDefault();
    toggleSidebar();
    this.classList.add('animate');
    setTimeout(() => this.classList.remove('animate'), 300);
});

function restoreSidebarState() {
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed') collapseSidebar();
    else expandSidebar();
}

restoreSidebarState();

window.addEventListener('load', () => {
    if (window.innerWidth <= 768) collapseSidebar();
});

window.addEventListener('resize', () => {
    if (window.innerWidth <= 768 && sidebarIsExpanded) collapseSidebar();
    else if (window.innerWidth > 768 && !sidebarIsExpanded) expandSidebar();
});

function openModal(tdId, tdCode, tdName, tdDescription, tdIcon, tdColor, isActive) {
    const isEdit = !!tdId;
    document.getElementById('modal_td_id').value = tdId || 0;
    document.getElementById('modal_td_code').value = tdCode || '';
    document.getElementById('modal_td_name').value = tdName || '';
    document.getElementById('modal_td_description').value = tdDescription || '';
    document.getElementById('modal_td_icon').value = tdIcon || '0';
    document.getElementById('modal_td_color').value = tdColor || '0';
    document.getElementById('modal_td_active').checked = !!isActive;
    document.getElementById('modalTitle').textContent = isEdit ? 'Modifier le domaine thématique' : 'Ajouter un domaine thématique';
    document.getElementById('modalSubtitle').textContent = isEdit ? 'Modifiez les informations ci-dessous' : 'Remplissez les informations du domaine';
    document.getElementById('submitLabel').textContent = isEdit ? 'Modifier' : 'Enregistrer';
    hideMessage();
    document.getElementById('modalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('modal_td_name').focus(), 300);
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}

function closeModalOnBackdrop(e) {
    if (e.target === document.getElementById('modalBackdrop')) closeModal();
}

function showMessage(t, type) {
    const el = document.getElementById('modalMessage');
    el.textContent = t;
    el.className = 'modal-message ' + type;
    el.style.display = 'block';
}

function hideMessage() {
    document.getElementById('modalMessage').style.display = 'none';
}

let pendingDeleteId = null, pendingDeleteRow = null;

function confirmDelete(tdId, label, rowEl) {
    pendingDeleteId = tdId;
    pendingDeleteRow = rowEl;
    document.getElementById('deleteTargetName').textContent = label;
    document.getElementById('deleteBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteBackdrop').classList.remove('show');
    document.body.style.overflow = '';
    pendingDeleteId = null;
    pendingDeleteRow = null;
}

function closeDeleteOnBackdrop(e) {
    if (e.target === document.getElementById('deleteBackdrop')) closeDeleteModal();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
    fetch(gvPath + '/ajax/removeRecord?td_id=' + pendingDeleteId, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => { if (!r.ok) throw new Error(); return r.text(); })
        .then(() => {
            if (pendingDeleteRow) {
                pendingDeleteRow.style.transition = 'all 0.35s ease';
                pendingDeleteRow.style.opacity = '0';
                pendingDeleteRow.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    pendingDeleteRow.remove();
                    const tbody = document.getElementById('tdTableBody');
                    if (tbody.children.length === 0) tbody.innerHTML = '<tr><td colspan="5" class="empty-row">Aucun domaine thématique disponible</td></tr>';
                }, 350);
            }
            closeDeleteModal();
            showToast('Domaine supprimé avec succès', 'success');
        })
        .catch(() => { closeDeleteModal(); showToast('Erreur lors de la suppression', 'error'); })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer'; });
});

let toastTimer = null;

function showToast(msg, type) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').className = type === 'success' ? 'fas fa-check-circle toast-icon' : 'fas fa-exclamation-circle toast-icon';
    toast.className = 'toast show ' + type;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeDeleteModal(); } });
</script>
HTML;
    }

    private function getTableBody() {
        global $gvPath;
        $tds = TopicalDomain::fromDatabaseCompleteList(false);
        if (!count($tds)) return '<tr><td colspan="5" class="empty-row">Aucun domaine thématique disponible</td></tr>';

        $html = "";
        foreach ($tds as $td) {
            $id = $td->getId();
            $code = htmlspecialchars($td->getCode());
            $name = htmlspecialchars($td->getName());
            $desc = htmlspecialchars($td->getDescription());
            $icon = (int) $td->getIcon();
            $color = (int) $td->getColor();
            $isActive = (int) $td->getActive();
            $checked = $isActive ? "checked" : "";
            $inactiveClass = $isActive ? "" : "inactive";
            $label = "$name ($code)";

            $html .= <<<HTML
<tr id="td-row-$id" class="$inactiveClass">
    <td>$code</td>
    <td>$name</td>
    <td>$desc</td>
    <td><input type="checkbox" disabled $checked></td>
    <td class="actions-cell">
        <button class="action-btn edit" onclick="openModal($id, '$code', '$name', '$desc', $icon, $color, $isActive)" title="Modifier"><i class="fas fa-edit"></i></button>
        <button class="action-btn delete" onclick="confirmDelete($id, '$label', document.getElementById('td-row-$id'))" title="Supprimer"><i class="fas fa-trash"></i></button>
    </td>
</tr>
HTML;
        }
        return $html;
    }

    public function getPageTitle() {
        return "Gestion des domaines thématiques";
    }

    private function getComboBoxOptions() {
        $ret = '';
        foreach (TopicalDomain::getAvailableCodes() as $code) $ret .= "\n<option value=\"$code\">$code</option>";
        return $ret;
    }

    private function getComboBoxIconOptions() {
        $ret = '';
        foreach (TopicalDomain::$ICONS as $index => $icon) {
            $text = $index === 0 ? "Aucune icône" : $icon[0];
            $ret .= "\n<option value=\"$index\">$text</option>";
        }
        return $ret;
    }

    private function getComboBoxColorOptions() {
        $ret = '';
        foreach (TopicalDomain::$COLORS as $index => $color) {
            $text = $index === 0 ? "Aucune couleur" : $color[0];
            $ret .= "\n<option value=\"$index\">$text</option>";
        }
        return $ret;
    }

    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f7fb; color: #1a1a2e; overflow-x: hidden; }
    .layout { display: flex; min-height: 100vh; }
    
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
        transition: width 0.3s ease;
        overflow-y: auto;
    }

    .sidebar.collapsed {
        width: 80px;
    }

    .sidebar.collapsed .sidebar-header {
        padding: 20px 15px;
    }

    .sidebar.collapsed .logo {
        justify-content: center;
        margin-bottom: 20px;
    }

    .sidebar.collapsed .logo-text,
    .sidebar.collapsed .admin-badge,
    .sidebar.collapsed .nav-text {
        display: none;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 12px 15px;
    }

    .sidebar-toggle {
        position: absolute;
        top: 20px;
        right: -15px;
        z-index: 1001;
        background: linear-gradient(135deg, #6C63FF, #8B82FF);
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        color: white;
        font-size: 18px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-toggle:hover { transform: scale(1.1); }
    .sidebar-toggle.animate { animation: pulse 0.3s ease; }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }

    .sidebar-header {
        padding: 30px 25px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        position: relative;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .logo i { font-size: 28px; color: #6C63FF; flex-shrink: 0; }
    .logo-text { font-size: 22px; font-weight: 700; }
    .admin-badge { background: rgba(108, 99, 255, 0.2); padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #6C63FF; }

    .sidebar-nav { flex: 1; padding: 20px 0; }
    
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

    .nav-item i { width: 20px; font-size: 18px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(108, 99, 255, 0.1); color: #fff; padding-left: 30px; }
    .sidebar.collapsed .nav-item:hover { padding-left: 15px; background: rgba(108, 99, 255, 0.2); border-radius: 10px; }
    .nav-item.active { background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1)); color: #fff; border-right: 3px solid #6C63FF; }
    .sidebar.collapsed .nav-item.active { border-right: none; border-radius: 10px; }

    .sidebar-footer { padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.1); }
    .logout { color: #ff6b6b; }
    .logout:hover { background: rgba(255, 107, 107, 0.1); padding-left: 30px; }

    .main-content { flex: 1; margin-left: 280px; min-height: 100vh; background: #f5f7fb; transition: margin-left 0.3s ease; }
    .main-content.expanded { margin-left: 80px; }
    
    .content-wrapper { padding: 30px 40px; max-width: 1400px; margin: 0 auto; }
    .page-header { margin-bottom: 30px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; }
    .subtitle { color: #666; font-size: 14px; }

    .toast {
        position: fixed; top: 24px; right: 24px; z-index: 9999;
        display: flex; align-items: center; gap: 10px;
        padding: 14px 20px; border-radius: 12px;
        font-size: 14px; font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateX(120%); opacity: 0;
        transition: transform 0.35s, opacity 0.35s;
        min-width: 240px;
    }
    .toast.show { transform: translateX(0); opacity: 1; }
    .toast.success { background: #f0fff4; color: #1e7e34; border-left: 4px solid #28a745; }
    .toast.error { background: #fff0f0; color: #c0392b; border-left: 4px solid #e74c3c; }

    .card { background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .card-header i { color: #6C63FF; font-size: 20px; }
    .card-header h3 { font-size: 16px; font-weight: 600; color: #1a1a2e; }

    .table-container { overflow-x: auto; }
    .stats-table { width: 100%; border-collapse: collapse; }
    .stats-table thead { background: #f8f9fa; }
    .stats-table th { padding: 12px 15px; text-align: left; font-size: 13px; font-weight: 600; color: #1a1a2e; border-bottom: 2px solid #eee; }
    .stats-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .stats-table tbody tr:hover { background: #f8f9fa; }
    .stats-table tbody tr.inactive { opacity: 0.55; }
    .empty-row { text-align: center; color: #999; padding: 20px !important; }

    .actions-cell { display: flex; gap: 8px; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; border: none; cursor: pointer; }
    .action-btn.edit { background: #6C63FF; color: white; }
    .action-btn.edit:hover { background: #5149E8; transform: scale(1.05); }
    .action-btn.delete { background: #ff6b6b; color: white; }
    .action-btn.delete:hover { background: #ee5a52; transform: scale(1.05); }

    .actions-section { margin-top: 25px; }
    .btn { display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; border: none; cursor: pointer; font-family: inherit; }
    .btn-primary { background: #6C63FF; color: white; }
    .btn-primary:hover { background: #5149E8; }
    .btn-secondary { background: #e9ecef; color: #1a1a2e; }
    .btn-secondary:hover { background: #dee2e6; }
    .btn-danger { background: #ff6b6b; color: white; }
    .btn-danger:hover { background: #ee5a52; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }

    .select-wrapper { position: relative; }
    .select-wrapper select { width: 100%; padding: 11px 40px 11px 14px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; color: #1a1a2e; font-family: inherit; background: white; appearance: none; outline: none; cursor: pointer; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
    .select-wrapper select:focus { border-color: #6C63FF; box-shadow: 0 0 0 3px rgba(108,99,255,0.12); }
    .select-arrow { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #999; font-size: 12px; pointer-events: none; }

    .modal-backdrop { position: fixed; inset: 0; background: rgba(10,10,30,0.65); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.25s ease; padding: 20px; }
    .modal-backdrop.show { opacity: 1; pointer-events: all; }
    .modal { background: #fff; border-radius: 20px; width: 100%; max-width: 560px; box-shadow: 0 24px 60px rgba(108,99,255,0.2); transform: translateY(20px) scale(0.97); transition: transform 0.3s; overflow: hidden; max-height: 90vh; overflow-y: auto; }
    .modal-sm { max-width: 420px; }
    .modal-backdrop.show .modal { transform: translateY(0) scale(1); }

    .modal-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; }
    .modal-header-danger { background: linear-gradient(135deg, #2e1a1a 0%, #3e1616 100%); }
    .modal-title { display: flex; align-items: center; gap: 16px; }
    .modal-icon { width: 48px; height: 48px; background: rgba(108,99,255,0.25); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #6C63FF; font-size: 20px; flex-shrink: 0; }
    .modal-icon-danger { background: rgba(255,107,107,0.2); color: #ff6b6b; }
    .modal-title h2 { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 2px; }
    .modal-title p { font-size: 12px; color: rgba(255,255,255,0.55); margin: 0; }
    .modal-close { background: rgba(255,255,255,0.1); border: none; width: 36px; height: 36px; border-radius: 10px; color: rgba(255,255,255,0.7); font-size: 16px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .modal-close:hover { background: rgba(255,107,107,0.3); color: #ff6b6b; }

    .modal-message { margin: 16px 28px 0; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }
    .modal-message.error { background: #fff0f0; color: #c0392b; border-left: 3px solid #e74c3c; }
    .modal-message.success { background: #f0fff4; color: #1e7e34; border-left: 3px solid #28a745; }

    .modal-body { padding: 24px 28px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-top: 16px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #1a1a2e; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-group label i { color: #6C63FF; font-size: 11px; }
    .form-group input[type="text"],
    .form-group textarea { width: 100%; padding: 11px 14px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; color: #1a1a2e; font-family: inherit; transition: all 0.2s ease; outline: none; }
    .form-group input:focus,
    .form-group textarea:focus { border-color: #6C63FF; box-shadow: 0 0 0 3px rgba(108,99,255,0.12); }
    .form-group textarea { resize: vertical; min-height: 80px; }

    .form-group-check { margin-top: 16px; }
    .check-label { display: flex !important; flex-direction: row !important; align-items: center; gap: 10px; cursor: pointer; text-transform: none !important; letter-spacing: 0 !important; font-size: 14px !important; font-weight: 500 !important; }
    .custom-check { position: relative; flex-shrink: 0; }
    .custom-check input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .check-box { display: block; width: 20px; height: 20px; border: 2px solid #eee; border-radius: 6px; background: #f8f9fa; transition: all 0.2s ease; position: relative; }
    .custom-check input:checked + .check-box { background: #6C63FF; border-color: #6C63FF; }
    .custom-check input:checked + .check-box::after { content: ''; position: absolute; left: 5px; top: 2px; width: 6px; height: 10px; border: 2px solid white; border-top: none; border-left: none; transform: rotate(45deg); }

    .delete-confirm-text { font-size: 14px; color: #444; line-height: 1.6; }
    .delete-confirm-text strong { color: #1a1a2e; }

    .modal-footer { padding: 16px 28px 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid #f0f0f0; }

    @media (max-width: 768px) {
        .sidebar-toggle { display: none; }
        .main-content { margin-left: 80px; }
        .main-content.expanded { margin-left: 80px; }
        .content-wrapper { padding: 20px 15px; }
        .page-header h1 { font-size: 22px; }
        .card { padding: 15px; }
        .stats-table th, .stats-table td { padding: 10px; font-size: 12px; }
        .form-row { grid-template-columns: 1fr; }
        .modal { max-width: 100%; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 14px 20px 20px; }
        .toast { right: 12px; left: 12px; min-width: unset; }
    }

    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f0f0f0; }
    ::-webkit-scrollbar-thumb { background: #c0c0c0; border-radius: 4px; }
</style>
CSS;
    }
}
?>