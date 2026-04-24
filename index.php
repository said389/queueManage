<?php
include 'includes/Setup.php';

main(); // Leave file scope

shutdown();

function main() {
    global $gvPath;
    
    // Get the requested page
    $requestUri = str_replace( $gvPath, '', $_SERVER['REQUEST_URI'] );
    // Remove query string
    $requestUri = preg_replace( '/^([^?]*)(\?.*)?$/', '$1', $requestUri );
    
    // Remove optional first slash
    if ( strpos( $requestUri, '/') === 0 && strlen( $requestUri ) >= 2 ) {
        $requestUri = substr( $requestUri, 1 );
    }
    
    // Remove optional trailing slashes
    $requestUri = preg_replace( '#^(.*)/+$#', '$1',  $requestUri );

    gfDebug( "RequestUri: $requestUri" );
    
    // ✅ Route directe pour l'Espace Client (accessible sans authentification)
    if ( $requestUri === 'client-space' || $requestUri === 'client-space/') {
        // Utiliser le chemin correct avec dirname(__FILE__)
        $clientSpacePath = dirname(__FILE__) . '/includes/pages/ClientSpace.php';
        if ( file_exists( $clientSpacePath ) ) {
            include $clientSpacePath;
        } else {
            die( 'Erreur: Le fichier ClientSpace.php n\'a pas été trouvé à ' . $clientSpacePath );
        }
        return;
    }
    
    Session::start();
    $userLevel = $_SESSION['userLevel'];
    
    $target = PageRouter::getClassOrRedirect( $requestUri );
    if ( is_object( $target ) ) {
        $target->output();
        return;
    } else {
        $page = new $target;
    }
    
    if ( !$page->canUse( $userLevel ) ) {
        $redirect = new RedirectOutput( $gvPath . "/application/loginPage" );
        $redirect->output();
        return;
    }
    
    $page->afterPermissionCheck();
    
    $output = true;
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        // execute() returns true, false or an Output object
        // True means: get the output of the page and show it
        // False means: something has already been sent as output, do nothing
        // Output object means: output the object that is returned
        $output = $page->execute();
    }
    
    if ( $output ) { // True or object
        if ( !is_object( $output ) ) {
            $output = $page->getOutput();
        }
        $output->output();
    }
    
}

function shutdown() {

    if ( Database::hasBeenUsed() ) {
        Database::commit();
    }

    // Jobs must not write to database!
    $queue = JobQueue::getInstance();
    $jobs = $queue->executeJobs();

    gfDebug( "Request ended.\n" );
}
