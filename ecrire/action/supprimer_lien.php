<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Action pour dissocier un lien entre 2 objets
 *
 * @package SPIP\Core\Liens\API
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Action pour dissocier 2 objets entre eux avec en option un qualificatif
 *
 * L'argument attendu est de la forme :
 * - `objet1-id1-objet2-id2` (type d'objet, identifiant)
 * - `objet1-id1-objet2-id2-qualif-valeur_qualif` pour définir une qualification en même temps
 * La table de liaison est celle de l'objet passé en premier argument
 *
 * @example
 * ```
 * // dissocier le mot 7 de la rubrique 3 (table de liaison : mots_liens)
 * `mot-7-rubrique-3`
 * // dissocier le mot 7 qui a la qualification rôle = gestion de la rubrique 3 (table de liaison : mots_liens)
 * `mot-7-rubrique-3-role-gestion`
 * // dissocier le contact 2 qui a la qualification fonction = volontaire de l'orga 10  (table de liaison : spip_contacts)
 * `contact-2-organisation-10-fonction-volontaire`
 * ```
 *
 * @uses objet_dissocier()
 *
 * @param null|string $arg
 *     Clé des arguments. En absence utilise l'argument
 *     de l'action sécurisée.
 * @return void
 */
function action_supprimer_lien_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$args = explode('-', (string) $arg, 6);

	include_spip('action/editer_liens');
	if (count($args) === 6) {
		[$objet_source, $ids, $objet_lie, $idl, $qualif, $valeur_qualif] = $args;
		objet_dissocier([$objet_source => $ids], [$objet_lie => $idl], [$qualif => $valeur_qualif ]);
	} else {
		[$objet_source, $ids, $objet_lie, $idl] = $args;
		objet_dissocier([$objet_source => $ids], [$objet_lie => $idl]);
	}
}
