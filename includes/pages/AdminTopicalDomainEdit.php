<?php

/**
 * Description of AdminTopicalDomainEdit
 *
 * @author sergio
 */
class AdminTopicalDomainEdit extends Page {
    private $message = "";
    
    // Submitted values to show again in the form
    private $td_id = 0;
    private $td_code = "";
    private $td_name = "";
    private $td_description = "";
    private $td_icon = 0;
    private $td_color = 0;
    private $td_active = 1;
    
    public function canUse( $userLevel ) {
        return $userLevel === Page::SYSADMIN_USER;
    }
    
    public function __construct() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $this->td_id = gfPostVar( 'td_id', 0 );
            // Keep edited information if present
            $this->td_code = gfPostVar( 'td_code', '' );
            $this->td_name = gfPostVar( 'td_name', '' );
            $this->td_description = gfPostVar( 'td_description', '' );
            $this->td_icon = gfPostVar( 'td_icon', 0 );
            $this->td_color = gfPostVar( 'td_color', 0 );
            $this->td_active = gfPostVar( 'td_active', 0 ) ? 1 : 0;
        } else {
            $this->td_id = gfGetVar( 'td_id', 0 );
            if ( $this->td_id ) {
                $td = TopicalDomain::fromDatabaseById( $this->td_id );
                if ( $td !== null ) {
                    $this->td_code = $td->getCode();
                    $this->td_name = $td->getName();
                    $this->td_description = $td->getDescription();
                    $this->td_icon = (int) $td->getIcon();
                    $this->td_color = (int) $td->getColor();
                    $this->td_active = (int) $td->getActive();
                } else {
                    $this->td_id = 0;
                }
            }
        }
    }
    
    public function execute() {
        
        // Trim data
        $this->td_name = trim( $this->td_name );
        $this->td_description = trim( $this->td_description );
        
        // Data validation
        if ( $this->td_name === '' ) {
            $this->message = "Erreur: le champ nom est obligatoire.";
            return true;
        }
        
        // Sanitize td_name
        if ( preg_match( '/^[0-9a-zàèéìòêôûäöüç \']+$/i', $this->td_name ) !== 1 ) {
            $this->message = "Erreur: le nom contient des caractères non valides.";
            return true;
        }
        
        // Sanitize td_description
        if ( preg_match( '/^[0-9a-zàèéìòêôûäöüç \'.,();:"]*$/i', $this->td_description ) !== 1 ) {
            $this->message = "Erreur: la description contient des caractères non valides.";
            return true;
        }
        
        if ( $this->td_id === 0 ) {
            $td = TopicalDomain::newRecord();
            $td->setActive( 1 );
        } else {
            $td = TopicalDomain::fromDatabaseById( $this->td_id );
        }
        $td->setCode( $this->td_code );
        $td->setName( $this->td_name );
        $td->setDescription( $this->td_description );
        $td->setIcon( $this->td_icon );
        $td->setColor( $this->td_color );
        $td->setActive( $this->td_active );
            
        if ( $td->save() ) {
            gfSetDelayedMsg( 'Opération effectuée correctement', 'Ok');
            global $gvPath;
            $redirect = new RedirectOutput( "$gvPath/application/adminTopicalDomainList" );
            return $redirect;
        } else {
            $this->message = "Impossible de sauvegarder les modifications. Réessayez plus tard.";
            return true;
        }
        
    }
    
    public function getOutput() {
        global $gvPath;
        
        $output = new WebPageOutput();
        $output->linkStyleSheet( "$gvPath/assets/css/style.css");
        $output->setHtmlPageTitle( $this->getPageTitle() );
        $output->setHtmlBodyHeader( $this->getPageHeader() );
        $output->setHtmlBodyContent( $this->getPageContent() );
        
        return $output;
    }
    
    private function getPageTitle() {
        if ( $this->td_id ) {
            return 'Modifier le domaine thématique';
        }
        return 'Nouveau domaine thématique';
    }
    
    public function getPageContent() {
        global $gvPath;
        
        $message = $this->message ? "<div class=\"errorMessage\">$this->message</div>" : "";
        $codeCombobox = $this->getComboBoxForCode();
        $iconCombobox = $this->getComboBoxForIcon();
        $colorCombobox = $this->getComboBoxForColor();
        $activeChecked = $this->td_active ? 'checked' : '';
        
        $ret = <<<EOS
$message
<form action="$gvPath/application/adminTopicalDomainEdit" method="post">
	<table>
		<tr>
			<td>Code:</td>
			<td>
				$codeCombobox
			</td>
		</tr>
		<tr>
			<td>Nom:</td>
			<td><input type="text" name="td_name" id="td_name" size="40" value="$this->td_name" /></td>
		</tr>
		<tr>
			<td>Description:</td>
			<td>
				<textarea rows="5" cols="40" name="td_description" id="td_description">$this->td_description</textarea>
			</td>
		</tr>
		<tr>
			<td>Icône:</td>
			<td>
				$iconCombobox
			</td>
		</tr>
		<tr>
			<td>Couleur:</td>
			<td>
				$colorCombobox
			</td>
		</tr>
		<tr>
			<td>Actif:</td>
			<td>
				<input type="checkbox" name="td_active" id="td_active" value="1" $activeChecked />
			</td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" value="Sauvegarder" /></td>
		</tr>
	</table>
	<input type="hidden" name="td_id" value="$this->td_id" />
</form>
<p><a href="$gvPath/application/adminTopicalDomainList">Retour</a></p>
EOS;
        return $ret;
    }
    
    private function getComboBoxForCode() {
        $ret = '<select name="td_code" id="td_code">';
        $availableCodes = TopicalDomain::getAvailableCodes();
        if ( $this->td_code ) {
            // Let be sure to always list the actual code
            if ( !in_array( $this->td_code, $availableCodes ) ) {
                $availableCodes[] = $this->td_code;
                sort($availableCodes);
            }
        }
        foreach ( $availableCodes as $code ) {
            $selected = $this->td_code === $code ? ' selected' : '';
            $ret .= "\n<option value=\"$code\"$selected>$code</option>";
        }
        $ret .= "\n</select>";
        return $ret;
    }
    
    private function getComboBoxForIcon() {
        $ret = '<select name="td_icon" id="td_icon">';
        foreach ( TopicalDomain::$ICONS as $index => $icon ) {
            $selected = $this->td_icon === $index ? ' selected' : '';
            if ( $index === 0 ) {
                $text = "Aucune icône";
            } else {
                $text = $icon[0];
            }
            $ret .= "\n<option value=\"$index\"$selected>$text</option>";
        }
        $ret .= "\n</select>";
        return $ret;
    }
    
    private function getComboBoxForColor() {
        $ret = '<select name="td_color" id="td_color">';
        foreach ( TopicalDomain::$COLORS as $index => $color ) {
            $selected = $this->td_color === $index ? ' selected' : '';
            if ( $index === 0 ) {
                $text = "Aucune couleur";
            } else {
                $text = $color[0];
            }
            $ret .= "\n<option value=\"$index\"$selected>$text</option>";
        }
        $ret .= "\n</select>";
        return $ret;
    }
    
    public function getPageHeader() {
        return "<h1>{$this->getPageTitle()}</h1>";
    }
}