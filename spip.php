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

use function SpipLeague\Component\Kernel\app;

require_once __DIR__ . '/vendor/autoload.php';

include_once app()->getcoreDir() . 'inc_version.php';
/** @todo Replace by $app = boot() call  */

# au travail...
include app()->getcoreDir() . 'public.php';
/** @todo Replace by $app->run() */
