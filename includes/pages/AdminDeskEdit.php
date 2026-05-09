<?php

/**
 * AdminDeskEdit - Gestion des compteurs
 *
 * @author sergio
 */
class AdminDeskEdit extends Page {
    private $message = "";
         
    private $desk_id = 0;
    private $desk_number = 0;
    private $desk_ip_address = "";
    private $pairing = 0;
    
    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }
    
    public function afterPermissionCheck() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->desk_id = gfPostVar('desk_id', 0);
            $this->desk_number = gfPostVar('desk_number', 0);
            $this->desk_ip_address = gfPostVar('desk_ip_address', '');
            $this->pairing = gfPostVar('pairing', 0);
        } else {
            $this->desk_id = gfGetVar('desk_id', 0);
            if ($this->desk_id) {
                $desk = Desk::fromDatabaseById($this->desk_id);
                if ($desk !== null) {
                    $this->desk_number = $desk->getNumber();
                    $this->desk_ip_address = $desk->getIpAddress();
                } else {
                    $this->desk_id = 0;
                }
            }
            $this->pairing = gfGetVar('pairing', 0);
            if ($this->pairing) {
                $this->desk_ip_address = $_SERVER['REMOTE_ADDR'];
            }
        }
    }

    public function execute() {
        global $gvPath;
        
        $this->desk_number = trim($this->desk_number);
        $this->desk_ip_address = trim($this->desk_ip_address);
        
        if ($this->desk_number === '' && $this->desk_ip_address === '') {
            $this->message = "Erreur : tous les champs sont obligatoires.";
            return true;
        }
        
        if (preg_match('/^[1-9][0-9]*$/', $this->desk_number) !== 1) {
            $this->message = "Erreur : le numéro du compteur n'est pas valide.";
            return true;
        }
        
        if (!filter_var($this->desk_ip_address, FILTER_VALIDATE_IP)) {
            $this->message = "Erreur : l'adresse IP n'est pas valide.";
            return true;
        }
        
        $desk = Desk::fromDatabaseByNumber($this->desk_number);
        if ($desk && ($this->desk_id === 0 || $this->desk_id !== (int) $desk->getId())) {
            $this->message = "Erreur : le numéro du compteur n'est pas disponible.";
            return true;
        }
        unset($desk);
        
        $desk = Desk::fromDatabaseByIpAddress($this->desk_ip_address);
        $device = Device::fromDatabaseByIpAddress($this->desk_ip_address);
        if ($device || ($desk && ($this->desk_id === 0 || $this->desk_id !== (int) $desk->getId()))) {
            $this->message = "Erreur : l'adresse IP est déjà assignée.";
            return true;
        }
        unset($desk);
        
        if ($this->desk_id === 0) {
            $desk = Desk::newRecord();
        } else {
            $desk = Desk::fromDatabaseById($this->desk_id);
        }

        if ($desk->isOpen()) {
            $this->message = "Erreur : le compteur est ouvert. Fermer la session avant de continuer.";
            return true;
        }

        $desk->setNumber($this->desk_number);
        $desk->setIpAddress($this->desk_ip_address);
        
        if ($desk->save()) {
            gfSetDelayedMsg('Opération effectuée correctement', 'Ok');
            $redirect = new RedirectOutput("$gvPath/application/adminDeskList");
            return $redirect;
        } else {
            $this->message = "Impossible de sauvegarder les modifications. Réessayez plus tard.";
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
        return $this->desk_id ? 'Modifier un compteur' : 'Ajouter un compteur';
    }
    
    public function getLayout() {
        global $gvPath;
        
        $messageHtml = '';
        if (!empty($this->message)) {
            $messageHtml = '<div class="modal-message error" style="display:block; margin-bottom:20px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($this->message) . '</div>';
        }
        
        $pageTitle = $this->getPageTitle();
        $submitText = $this->desk_id ? 'Modifier' : 'Enregistrer';
        $isEdit = $this->desk_id > 0;
        $pairingHidden = $this->pairing ? '<input type="hidden" name="pairing" value="1" />' : '<input type="hidden" name="pairing" value="0" />';

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
        .form-group input[type="number"] {
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
        
        .form-group input[type="number"] {
            -moz-appearance: textfield;
        }
        
        .form-group input[type="number"]::-webkit-inner-spin-button,
        .form-group input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .ip-hint {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f0f0f4;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            color: #6C63FF;
        }
        
        .info-banner {
            background: #f8f9fc;
            border-left: 3px solid #6C63FF;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-banner i {
            color: #6C63FF;
            font-size: 18px;
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
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #1a1a2e;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
            transform: translateY(-1px);
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
                <div class="modal-icon"><i class="fas fa-desktop"></i></div>
                <div>
                    <h2>{$pageTitle}</h2>
                    <p>Remplissez les informations du compteur</p>
                </div>
            </div>
            <a href="{$gvPath}/application/adminDeskList" class="modal-close">
                <i class="fas fa-times"></i>
            </a>
        </div>
        
        {$messageHtml}
        
        <form action="{$gvPath}/application/adminDeskEdit" method="post">
            <input type="hidden" name="desk_id" value="{$this->desk_id}" />
            {$pairingHidden}
            
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> NUMÉRO DU COMPTEUR</label>
                    <input type="number" 
                           name="desk_number" 
                           id="desk_number" 
                           placeholder="ex: 1, 2, 3..."
                           min="1" 
                           max="99"
                           value="{$this->desk_number}" 
                           required />
                    <div class="info-banner" style="margin-top: 8px; margin-bottom: 0; padding: 8px 12px;">
                        <i class="fas fa-info-circle"></i>
                        <span>Numéro unique entre <strong>1 et 99</strong></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-network-wired"></i> ADRESSE IP</label>
                    <input type="text" 
                           name="desk_ip_address" 
                           id="desk_ip_address" 
                           placeholder="ex: 192.168.1.10"
                           value="{$this->desk_ip_address}" 
                           autocomplete="off"
                           required />
                    <div class="info-banner" style="margin-top: 8px; margin-bottom: 0; padding: 8px 12px;">
                        <i class="fas fa-lightbulb"></i>
                        <span>Adresse IP <span class="ip-hint">IPv4</span> statique du compteur</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <a href="{$gvPath}/application/adminDeskList" class="btn btn-secondary">
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
    // Focus sur le premier champ
    const firstInput = document.getElementById('desk_number');
    if (firstInput) firstInput.focus();
    
    // Validation dynamique de l'adresse IP
    const ipInput = document.getElementById('desk_ip_address');
    if (ipInput) {
        ipInput.addEventListener('input', function() {
            const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            
            if (this.value && !ipRegex.test(this.value)) {
                this.style.borderColor = '#e74c3c';
                this.style.backgroundColor = '#fff0f0';
            } else {
                this.style.borderColor = '#eee';
                this.style.backgroundColor = 'white';
            }
        });
    }
    
    // Empêcher les valeurs négatives pour le numéro
    const numberInput = document.getElementById('desk_number');
    if (numberInput) {
        numberInput.addEventListener('input', function() {
            if (this.value < 1) this.value = 1;
            if (this.value > 99) this.value = 99;
        });
    }
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
    
    private function getDesignCSS() {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"></head><body>';
    }
}
?>