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

require_once __DIR__ . '/vendor/autoload_runtime.php';

return static function (array $context): SpipHttpKernel {
	return SpipHttpKernel::boot();
};
