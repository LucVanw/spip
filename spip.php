<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 * Pour plus de détails voir le fichier LICENSE ou l'aide en ligne.
 */

use Spip\HttpKernel\SpipBootstrapper;
use SpipLeague\Component\HttpKernel\Handler\LegacyHandler;
use SpipLeague\Component\HttpKernel\SpipHttpKernel;
use Symfony\Component\HttpFoundation\Request;
use function SpipLeague\Component\Kernel\app;

require_once __DIR__ . '/vendor/autoload.php';

$kernel = app();
$request = Request::createFromGlobals();

$httpKernel = new SpipHttpKernel(
    $kernel,
    new LegacyHandler($kernel->getCoreDir() . 'public.php'),
    new SpipBootstrapper(),
);

$response = $httpKernel->handle($request);
$response->send();
$httpKernel->terminate($request, $response);
