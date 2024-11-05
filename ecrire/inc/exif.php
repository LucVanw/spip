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
 * Fonctions utilitaires liées aux données EXIF accompagnant certaines images JPEG ou TIFF.
 * On se limite aux seuls fichiers supportés à ce jour — octobre 2024 — par le module EXIF de PHP
 * même si l'on peut trouver des données EXIF dans d'autres formats, notamment le PNG — cf. https://bugs.php.net/bug.php?id=76279)
 *
 * @package SPIP\Core\Exif
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne l'EXIF d'orientation d'une image JPEG ou TIFF, si elle en possède bien un.
 *
 * @param string $fichier
 * @return int|null
 */
function exif_obtenir_orientation(string $fichier): ?int {
	include_spip('inc/filtres_images_lib_mini');
	$orientation = null;

	if (
		in_array(_image_extension_normalisee(pathinfo($fichier, PATHINFO_EXTENSION)), ['jpg', 'tiff'])
		&& function_exists('exif_read_data')
		&& ($exif = @exif_read_data($fichier))
		&& isset($exif['Orientation'])
	) {
		$orientation = $exif['Orientation'];
	}

	return $orientation;
}

/**
 * Détermine si un EXIF d'orientation correspond à une image en mode portrait.
 *
 * (cf. https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/#eh-exif-orientation).
 *
 * @param int|null $orientation
 * @return bool
 */
function exif_determiner_si_portrait(?int $orientation): bool {
	return in_array($orientation, [5, 6, 7, 8]);
}

/**
 * Détermine l'axe de la symétrie à appliquer sur une image porteuse d'un EXIF d'orientation.
 *
 * En l'état, on renvoie toujours 1, puisque l'image générée par SPIP étant toujours en mode paysage,
 * l'axe de la symétrie à appliquer est toujours le même, à savoir horizontal
 * (on n'utilise cependant pas la constante IMG_FLIP_HORIZONTAL fournie par la librairie GD).
 *
 * @param int $orientation
 * @return int|null
 */
function exif_determiner_axe_symetrie(int $orientation): ?int {
	$axe = null;

	if (in_array($orientation, [2, 4, 5, 7])) {
		$axe = 1;
	}

	return $axe;
}

/**
 * Détermine l'angle de la rotation à appliquer sur une image porteuse d'un EXIF d'orientation.
 *
 * @param int $orientation
 * @return int|null
 */
function exif_determiner_angle_rotation(int $orientation): ?int {
	$angle = null;

	if (in_array($orientation, [3, 4, 5, 6, 7, 8])) {
		$angles = [
			3 => 180,
			4 => 180,
			5 => 90,
			6 => -90,
			7 => -90,
			8 => 90,
		];

		$angle = $angles[$orientation];
	}

	return $angle;
}

/**
 * Gérer la migration des hauteur et largeur des images en mode portrait porteuses d'un EXIF d'orientation
 *
 * @param int $timeout
 * @return void
 */
function exif_orientation_migrer_en_base(int $timeout): void {
	// Si l'extension EXIF n'est pas disponible, on ne fait rien.
	if (function_exists('exif_read_data')) {
		// On désactive les revisions… (cf. logo_migrer_en_base())
		$liste_objets_versionnes = $GLOBALS['meta']['objets_versions'] ?? '';
		unset($GLOBALS['meta']['objets_versions']);
		// …et le signalement des editions (cf. logo_migrer_en_base()).
		$articles_modif = $GLOBALS['meta']['articles_modif'] ?? '';
		$GLOBALS['meta']['articles_modif'] = 'non';

		// On récupère l'id du dernier document traité par la fonction de mise à jour, sinon 1.
		$dernier_id = $GLOBALS['meta']['medias_upgrade_images_exif_orientation_dernier_id'] ?? 1;

		// On récupère tous les documents concernés à partir de l'id du dernier document traité.
		$images = sql_allfetsel(
			[
				'id_document',
				'fichier',
				'largeur',
				'hauteur',
			],
			'spip_documents',
			[
				'id_document > ' . $dernier_id,
				sql_in('extension', ['jpg', 'tiff']),
			]
		);

		spip_logger('maj_dimensions_images_exif')->info('Il reste ' . count($images) . ' images à traiter');

		foreach ($images as $image) {
			// Si l'on a bien un EXIF d'orientation, on procède à la mise à jour des largeur et hauteur.
			if (
				($exif = @exif_read_data(_DIR_IMG . $image['fichier']))
				&& isset($exif['Orientation'])
				&& ($orientation = $exif['Orientation'])
				&& exif_determiner_si_portrait($orientation)
			) {
				// On ne peut se contenter d'inverser les dimensions déjà enregistrées (au cas où la migration serait rejouée).
				[$largeur, $hauteur] = spip_getimagesize(_DIR_IMG . $image['fichier']);

				$resultat = sql_updateq(
					'spip_documents',
					[
						'largeur' => $largeur,
						'hauteur' => $hauteur,
					],
					'id_document = ' . $image['id_document'],
				);

				$resultat ?: spip_logger('maj_dimensions_images_exif')->error('Le document ' . $image['id_document'] . 'n\'a pas pu être mis à jour.');
			}

			// On garde une trace du dernier document traité.
			ecrire_meta('medias_upgrade_images_exif_orientation_dernier_id', $image['id_document']);

			// En cas de timeout, la fonction est rappelée autoamagiquement par maj_while()
			// (cf. logo_migrer_en_base()).
			if ($timeout && time() > $timeout) {
				effacer_meta('drapeau_edition');
				return;
			}
		}

		// Réactiver les révisions…
		if ($liste_objets_versionnes) {
			$GLOBALS['meta']['objets_versions'] = $liste_objets_versionnes;
		}
		// …et le signalement des éditions.
		$GLOBALS['meta']['articles_modif'] = $articles_modif;

		effacer_meta('drapeau_edition');
	}
}
