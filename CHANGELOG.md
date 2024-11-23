# Changelog

Changelog de SPIP 4.4

## Unreleased

### Security

- Mise à jour de l’écran de sécurité via composer
- Mise à jour de l’écran de sécurité en version 1.6.3

### Added

- spip/prive#35 Chaînes de langue supplémentaire pour les listes d'articles
- #5560 Balise `#LAYOUT_PRIVE`
- !5633 Balise `#TRAD{module:cle, #ARRAY{param, val, ..}, #ARRAY{option, val..}}`
- #5933 Les actions `ajouter_lien` et `supprimer_lien` peuvent gérer un qualificatif
- #5973 Marqueur de cache `cache_bot_invalide` permettant d’invalider le cache même si un bot est à l’origine de la requête
- #5766 Pipeline `ajouter_menus_args`, en complément au pipeline `ajouter_menus`, qui transmet les arguments de `definir_barre_boutons()`
- !6064 Polyfill pour PHP 8.3 & 8.4
- !6051 Purger les variables de `var_nullify` du contexte dans `traiter_appels_inclusions_ajax`
- !6044 balise #PARAM pour récupérer les paramètres du container de services (Cf [UPGRADE_5.0.md](UPGRADE_5.0.md#Constantes_PHP))
- !6034 paramètre pour le filtre `|affdate{'Y-m-d H:i:s}`
- spip/medias#4958 Fonction `_image_extensions_logos()` et pipeline `image_extensions_logos`
- Les dossiers `prive/` et `squelettes-dist/` s'installent avec composer
- #5938 Permettre à `objet_info()` de retourner directement l’information `table_objet_sql` (le nom de la table sql)

### Changed

- #5979 Revert du calcul automatique des chaînes de langue du menu Créer du bandeau de l’espace privé

### Fixed

- !5633 Possibilité de calculer dynamiquement les paramètres des chaînes de langue
- !5633 Possibilité de calculer dynamiquement les paramètre des filtres des chaînes de langue
- #6021 Eviter une fatale sur `inc_recherche_to_array_dist()` dans certains contextes d'appel
- #6013 Si authentification LDAP, vérifier les mots de passe même si `$_SERVER['REMOTE_USER']` est déclarée vide
- #6012 Passer l'`id_parent_ancien` aux pipelines `pre_edition` et `post_edition` depuis `article_instituer()`
- #5722 Requêter les fichiers distants avec `STREAM_CRYPTO_METHOD_TLS_CLIENT`
- #5919 Remplacer les balises `tt` obsolètes par `code`
- !6047 Correction de certains envois de fichiers (notamment audio) via `spip_livrer_fichier`
- #3919 Réparer l'ajout de la configuration LDAP lors de l'installation
- #3928 Les emails des auteurs sont masqués par défaut
- !6024 Éviter notice dans `init_http` en cas de "seriously malformed URLs"
- #5983 (retour partiel sur #5667) Générer des contenus éditoriaux aussi compatibles xhtml (sur `br` et `img`)
- !6016 Afficher aussi dans l’espace privé le tableau des requêtes du mode `var_profile=1`
- #5979 Modifier les chaînes de langues utilisées pour les objets déclarés dans le menu Créer (article, rubrique, auteur)

### Deprecated

- #5560 Balise `#LARGEUR_ECRAN` pour les squelettes du privé à remplacer par `#LAYOUT_PRIVE`
- !5633 Classe interne `Idiome` depréciée, utiliser plutot `phraser_preparer_idiomes()` et la balise interne `#TRAD_IDIOME`
- #2536 À partir de SPIP 5, l'appel des chaînes de langues en squelette sera sensible à la casse de la déclaration, il n'y aura plus de conversion automatique en minuscule
- !5633 Fonction interne `phraser_boucle_placeholder()` à remplacer par `phraser_placeholder_memoriser_ou_reinjecter()`
- !5633 Fonction interne `public_generer_boucle_placeholder()` à remplacer par `public_placeholder_generer()`
- #6014 Les fichiers de langue peuplant une `$GLOBALS` sont dépréciés ; renvoyer directement un tableau
- #4903 Constante obsolète `_DIR_IMG_PACK`
- #5993 Globales `$traiter_math`, `$tex_server`, fonctions `produire_image_math()`, `traiter_math()`, utiliser le plugin `mathjax` à la place
- #5992 Modifier la globale `$formats_logos` est déprécié : utiliser le pipeline `image_extensions_logos`
- #5992 Appeler la globale `$formats_logos` est déprécié, utiliser la fonction `_images_extensions_logos()`

### Removed

- #5505 #5988 Fonctions `verif_butineur()`, `editer_texte_recolle()` et environnement `_texte_trop_long` des formulaires (Inutilisé — servait pour IE !)

