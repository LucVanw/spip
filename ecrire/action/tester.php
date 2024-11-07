<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 */

/**
 * Gestion de l'action testant une librairie graphique
 *
 * @package SPIP\Core\Configurer
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tester les capacités du serveur à utiliser une librairie graphique
 *
 * L'argument transmis dans la clé `arg` est le type de librairie parmi
 * gd2 ou autre librairie prise en charge par plugin
 *
 * L'action crée une vignette en utilisant la librairie indiquée puis
 * redirige sur l'image ainsi créée (sinon sur une image d'echec).
 */
function action_tester_dist() {
	$arg = _request('arg');

	// verifier les formats acceptes par GD
	if ($arg === 'gd2') {
		$gd_formats = [];

		if (function_exists('imagetypes')) {
			if (imagetypes() & IMG_GIF) {
				$gd_formats[] = 'gif';
			}
			if (imagetypes() & IMG_JPG) {
				$gd_formats[] = 'jpg';
			}
			if (imagetypes() & IMG_PNG) {
				$gd_formats[] = 'png';
			}
			if (imagetypes() & IMG_WEBP) {
				$gd_formats[] = 'webp';
			}
		}

		$gd_formats = implode(',', $gd_formats);
		ecrire_meta('gd_formats_read', $gd_formats);
		ecrire_meta('gd_formats', $gd_formats);
	}

	// et maintenant envoyer la vignette de tests
	if (
		$arg === 'gd2'
		|| (!preg_match(',\W,', $arg) && charger_fonction($arg, 'filtres/image_process', true))
	) {
		include_spip('inc/filtres');
		include_spip('inc/filtres_images_mini');
		$taille_preview = 150;
		$image = _image_valeurs_trans(_DIR_RACINE . 'prive/' . _NOM_IMG_PACK . 'test_image.jpg', "reduire-$taille_preview-$taille_preview", 'jpg');

		$image['fichier_dest'] = _DIR_VAR . "test_$arg";

		if (
			($preview = _image_creer_vignette($image, $taille_preview, $taille_preview, $arg, true))
			&& $preview['width'] * $preview['height'] > 0
		) {
			redirige_par_entete($preview['fichier']);
		}
	}

	# image echec
	redirige_par_entete(chemin_image('echec-reducteur-xx.svg'));
}
