<?php

/**
 * Paramètres des liens documentaires SPIP.
 */
return [
	/**
	 * URL de la documentation officielle SPIP.
	 *
	 * Remplace la globale `$home_server`.
	 */
	'home_server' => 'https://www.spip.net',
	/**
	 * URL de glossaire utilisée pour les raccourcis `[?X]`.
	 * Le placeholder `%s` est remplacé par le terme.
	 *
	 * Remplace la globale `$url_glossaire_externe`.
	 */
	'url_glossaire' => 'https://@lang@.wikipedia.org/wiki/%s',
];
