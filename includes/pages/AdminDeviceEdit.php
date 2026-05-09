<?php

/**
 * AdminDeviceEdit - Gestion des appareils
 *
 * @author sergio
 */
class AdminDeviceEdit extends Page {

    private $message = "";
    private $dev_id = 0;
    private $dev_ip_address = '';
    private $dev_desk_number = "";
    private $dev_td_code = null;
    
    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }
    
    public function afterPermissionCheck() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->dev_id = gfPostVar('dev_id', 0);
            $this->dev_ip_address = gfPostVar('dev_ip_address', '');
            $this->dev_desk_number = gfPostVar('dev_desk_number', 0);
            $this->dev_td_code = gfPostVar('dev_td_code', '0');
        } else {
            $this->dev_id = gfGetVar('dev_id', 0);
            if ($this->dev_id) {
                $device = Device::fromDatabaseById($this->dev_id);
                if ($device !== null) {
                    $this->dev_ip_address = $device->getIpAddress();
                    $this->dev_desk_number = $device->getDeskNumber();
                    $this->dev_td_code = $device->getTdCode();
                } else {
                    $this->dev_id = 0;
                }
            }
        }

        if ($this->dev_td_code == '0' || $this->dev_desk_number != 0) {
            $this->dev_td_code = null;
        }
    }

    public function execute() {
        global $gvPath;
        
        $this->dev_ip_address = trim($this->dev_ip_address);
        $this->dev_desk_number = trim($this->dev_desk_number);
        
        if ($this->dev_ip_address === '' || $this->dev_desk_number === '') {
            $this->message = "Erreur : tous les champs sont obligatoires.";
            return true;
        }
        
        if (preg_match('/^(0|[1-9][0-9]*)$/', $this->dev_desk_number) !== 1) {
            $this->message = "Erreur : le numéro de guichet n'est pas valide.";
            return true;
        }
        
        if (!filter_var($this->dev_ip_address, FILTER_VALIDATE_IP)) {
            $this->message = "Erreur : l'adresse IP n'est pas valide.";
            return true;
        }
        
        if ((int) $this->dev_desk_number !== 0) {
            $desk = Desk::fromDatabaseByNumber($this->dev_desk_number);
            if (!$desk) {
                $this->message = "Erreur : le guichet spécifié n'existe pas.";
                return true;
            }
            unset($desk);
        }

        if ($this->dev_td_code) {
            $td = TopicalDomain::fromDatabaseByCode($this->dev_td_code);
            if (!$td || !$td->getActive()) {
                $this->message = "Erreur : le domaine thématique sélectionné n'est pas disponible.";
                return true;
            }
        }
        
        $device = Device::fromDatabaseByIpAddress($this->dev_ip_address);
        $desk = Desk::fromDatabaseByIpAddress($this->dev_ip_address);
        if ($desk || ($device && ($this->dev_id === 0 || $this->dev_id !== (int) $device->getId()))) {
            $this->message = "Erreur : cette adresse IP est déjà attribuée.";
            return true;
        }
        unset($device);
        
        if ($this->dev_id === 0) {
            $device = Device::newRecord();
        } else {
            $device = Device::fromDatabaseById($this->dev_id);
        }
        $device->setIpAddress($this->dev_ip_address);
        $device->setDeskNumber($this->dev_desk_number);
        $device->setTdCode($this->dev_td_code);
        
        if ($device->save()) {
            gfSetDelayedMsg('Opération effectuée avec succès', 'Succès');
            $redirect = new RedirectOutput("$gvPath/application/adminDeviceList");
            return $redirect;
        } else {
            $this->message = "Impossible d'enregistrer les modifications. Veuillez réessayer.";
            return true;
        }
    }

    public function getOutput() {
        global $gvPath;
        
        $output = new WebPageOutput();
        $output->setHtmlPageTitle($this->getPageTitle());
        $output->setHtmlBodyHeader($this->getDesignCSS());
        $output->setHtmlBodyContent($this->getLayout());
        return $output;
    }
    
    private function getPageTitle() {
        return $this->dev_id ? 'Modifier un appareil' : 'Ajouter un appareil';
    }
    
    public function getLayout() {
        global $gvPath;
        
        $messageHtml = '';
        if (!empty($this->message)) {
            $isError = true;
            $messageHtml = '<div class="modal-message error" style="display:block; margin-bottom:20px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($this->message) . '</div>';
        }
        
        $pageTitle = $this->getPageTitle();
        $submitText = $this->dev_id ? 'Modifier' : 'Enregistrer';
        $isEdit = $this->dev_id > 0;
        
        $combobox = $this->getCombobox();
        $comboboxTd = $this->getComboboxTd();

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastQueue - {$pageTitle}</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(10,10,30,0.65);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 24px 60px rgba(108,99,255,0.2);
            overflow: hidden;
            animation: modalIn 0.3s ease;
        }
        
        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .modal-icon {
            width: 48px;
            height: 48px;
            background: rgba(108,99,255,0.25);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6C63FF;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .modal-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }
        
        .modal-title p {
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin: 0;
        }
        
        .modal-close {
            background: rgba(255,255,255,0.1);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            color: rgba(255,255,255,0.7);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-decoration: none;
        }
        
        .modal-close:hover {
            background: rgba(255,107,107,0.3);
            color: #ff6b6b;
        }
        
        .modal-message {
            margin: 16px 28px 0;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-message.error {
            background: #fff0f0;
            color: #c0392b;
            border-left: 3px solid #e74c3c;
        }
        
        .modal-message.success {
            background: #f0fff4;
            color: #1e7e34;
            border-left: 3px solid #28a745;
        }
        
        .modal-body {
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group label i {
            color: #6C63FF;
            font-size: 11px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 14px;
            color: #1a1a2e;
            font-family: inherit;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #6C63FF;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper select {
            width: 100%;
            padding: 11px 40px 11px 14px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 14px;
            color: #1a1a2e;
            font-family: inherit;
            background: white;
            appearance: none;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .select-wrapper select:focus {
            border-color: #6C63FF;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
        }
        
        .select-arrow {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 12px;
            pointer-events: none;
        }
        
        .modal-footer {
            padding: 16px 28px 24px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: inherit;
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
        
        @media (max-width: 600px) {
            body {
                padding: 20px 15px;
            }
            .modal-header {
                padding: 20px 24px;
            }
            .modal-body {
                padding: 20px 24px;
            }
            .modal-footer {
                padding: 14px 24px 20px;
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <div class="modal-icon"><i class="fas fa-mobile-alt"></i></div>
                <div>
                    <h2>{$pageTitle}</h2>
                    <p>Remplissez les informations de l'appareil</p>
                </div>
            </div>
            <a href="{$gvPath}/application/adminDeviceList" class="modal-close">
                <i class="fas fa-times"></i>
            </a>
        </div>
        
        {$messageHtml}
        
        <form action="{$gvPath}/application/adminDeviceEdit" method="post">
            <input type="hidden" name="dev_id" value="{$this->dev_id}" />
            
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-network-wired"></i> ADRESSE IP</label>
                    <input type="text" 
                           name="dev_ip_address" 
                           id="dev_ip_address" 
                           placeholder="ex: 192.168.1.10"
                           value="{$this->dev_ip_address}" 
                           autocomplete="off" />
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-desktop"></i> FONCTION</label>
                    <div class="select-wrapper">
                        <select name="dev_desk_number" id="dev_desk_number">
                            {$combobox}
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                
                <div class="form-group" id="tdGroup">
                    <label><i class="fas fa-folder-tree"></i> DOMAINE THÉMATIQUE</label>
                    <div class="select-wrapper">
                        <select name="dev_td_code" id="dev_td_code">
                            {$comboboxTd}
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <a href="{$gvPath}/application/adminDeviceList" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {$submitText}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Gestion de l'affichage du domaine thématique
    function handleFunctionChange() {
        const select = document.getElementById('dev_desk_number');
        const tdGroup = document.getElementById('tdGroup');
        const tdSelect = document.getElementById('dev_td_code');
        
        if (select.value !== '0') {
            tdGroup.style.display = 'none';
            tdSelect.value = '0';
        } else {
            tdGroup.style.display = 'flex';
        }
    }
    
    // Attacher l'événement
    const funcSelect = document.getElementById('dev_desk_number');
    if (funcSelect) {
        funcSelect.addEventListener('change', handleFunctionChange);
        handleFunctionChange();
    }
    
    // Focus sur le premier champ
    document.getElementById('dev_ip_address')?.focus();
</script>
</body>
</html>
HTML;
    }
    
    public function getPageHeader() {
        return '';
    }
    
    public function getPageContent() {
        return $this->getLayout();
    }
    
    private function getCombobox() {
        $ret = '<option value="0">Affichage salle</option>';
        foreach (Desk::getUsedDeskNumbers() as $num) {
            $selected = ($this->dev_desk_number == $num) ? ' selected' : '';
            $ret .= "\n<option value=\"{$num}\"{$selected}>Affichage compteur {$num}</option>";
        }
        return $ret;
    }
    
    private function getComboboxTd() {
        $ret = '<option value="0">Tous</option>';
        foreach (TopicalDomain::fromDatabaseCompleteList() as $td) {
            $code = htmlspecialchars($td->getCode());
            $selected = ($this->dev_td_code === $code) ? ' selected' : '';
            $ret .= "\n<option value=\"{$code}\"{$selected}>{$code}</option>";
        }
        return $ret;
    }
    
    private function getDesignCSS() {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"></head><body>';
    }
}
?>