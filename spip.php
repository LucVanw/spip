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

use SpipLeague\Component\HttpKernel\SpipHttpKernel;
use function SpipLeague\Component\Kernel\service;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/vendor/autoload.php';

/** @var SpipHttpKernel $httpKernel */
$httpKernel = service('http_kernel');
$request = Request::createFromGlobals();
$response = $httpKernel->handle($request);
$response->send();
$httpKernel->terminate($request, $response);
