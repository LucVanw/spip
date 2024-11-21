# UPGRADE de SPIP 4.2 à 5.0

## Préliminaires

SPIP 5.0 nécessite PHP 8.2 minimum.

### Migration BDD

SPIP 5.0 ne contient pas les mises à jour de BDD antérieures à SPIP 4.0.
Pour rappel, SPIP 4.x ne contient pas les mises à jour antérieures SPIP 2.0.

Dit autrement, il faut a minima partir d’un SPIP 3.2 (ou supérieur donc) pour que la migration en SPIP 5.0 de la base de données s’effectue correctement.
Pour des questions de compatibilité de SPIP 5.0 avec PHP 8.2 minimum, il est conseillé (mais pas obligatoire) de partir au moins d’un SPIP 4.1.

### Déplacement plugins-dist

Les plugins-dist (plugins fournis avec la distribution SPIP et toujours activés) sont maintenant installés via l’outil Composer (ou présents dans l’archive SPIP téléchargée).

Ils sont maintenant placés dans le répertoire `plugins-dist/spip/` avec leur préfixe comme nom de répertoire. Ils étaient auparavant directement à la racine de `plugins-dist/`.

En conséquence, et en fonction de votre méthode de migration vers SPIP 5.0,
il faudra supprimer les anciens `plugins-dist/` de SPIP 4.2 à la racine s’ils sont présents.

Ainsi, par exemple, le plugin Textwheel était auparavant dans `plugins-dist/textwheel` et se retrouve après mise à jour en SPIP 5.0 dans `plugins-dist/spip/tw` (où `tw` est le préfixe du plugin)

### Webmestres

La constante `_ID_WEBMESTRES` (dépréciée en SPIP 2.1) n’est plus utilisée et n’a plus d’effet.

Si vous déclariez cette constante (dans `config/mes_options.php` par exemple), il convient de l’enlever et de déclarer les autrices et auteurs webmestres en conséquence depuis l’interface privée de SPIP en tant que webmestre (ou via le champ `webmestre` de la table `spip_auteurs` directement dans la base de données).



## Squelettes

### Parties optionnelles des balises

Il devient possible d’utiliser des crochets dans les parties optionnelles des balises. Ainsi :

- `[avant] (#VAL{texte})]` écrit `avant] texte`
- `[(#VAL{texte}) [après]` écrit `texte [après`
- `[avant] (#VAL{texte}) [après]` écrit `avant] texte [après`

En conséquence, certaines écritures de squelettes deviennent comprises comme des parties optionnelles, lorsque `(#BALISE` est utilisé alors que la parenthèse ne désigne pas une partie optionnelle.

Ce cas peut se trouver par exemple dans des écriture pour du CSS. Il convient de lever l’ambiguité

#### Avant

```css
a[href] { background-image: url(#CHEMIN_IMAGE{img.svg}); }
```

#### Après

```css
a[href] { background-image: url\(#CHEMIN_IMAGE{img.svg}); }
```

ou

```css
a[href] { background-image: url("#CHEMIN_IMAGE{img.svg}"); }
```

# Nouvelles dépréciations



Les éléments suivants sont dépréciés et seront supprimés dans une future version.

## Syntaxes de squelettes

### Critère `{collecte}` (déprécié en 5.0)

Utiliser `{collate}`

#### Avant

```spip
<BOUCLE_a(ARTICLES) {par titre} {collecte utf8_spanish_ci} {"<br />"}>...
```

#### Après

```spip
<BOUCLE_a(ARTICLES) {par titre} {collate utf8_spanish_ci} {"<br />"}>...
```

### Critères `{fusion}` (déprécié en 5.0)

Utiliser `{groupby}`

#### Avant

```spip
<BOUCLE_extrait(ARTICLES){fusion id_rubrique}>...
```

#### Après

```spip
<BOUCLE_extrait(ARTICLES){groupby id_rubrique}>...
```

### Critères `{fusion_supprimer}` (déprécié en 5.0)

Utiliser `{groupby_supprimer}`

#### Avant

```spip
<BOUCLE_facette_annee(ARTICLES){id_mot=27}{fusion_supprimer}{fusion YEAR(date)}>
    [(#DATE|annee)]<br />
</BOUCLE_facette_annee>
```

#### Après

```spip
<BOUCLE_facette_annee(ARTICLES){id_mot=27}{groupby_supprimer}{groupby YEAR(date)}>
    [(#DATE|annee)]<br />
</BOUCLE_facette_annee>
```

## Fonctions PHP

Dans certains cas parfois utilisées en filtres de squelettes également.

### Fonction `spip_log`

La fonction très courante `spip_log` est dépréciée au profit de la fonction `spip_logger`,
qui retourne une instance de [`Psr/Log/LoggerInterface`](https://www.php-fig.org/psr/psr-3/#3-psrlogloggerinterface).

Les constantes de niveau de log associées, comme `_LOG_ERREUR` sont aussi dépréciées.

La constante (configuration) `_LOG_FILTRE_GRAVITE` prend pour valeur un `Psr\Log\LogLevel::*`

#### Avant

```php
spip_log('message defaut (info)');
spip_log('message d’erreur', _LOG_ERREUR);

# ensemble des hiérarchies de log
spip_log('message debug', _LOG_DEBUG);
spip_log('message info', _LOG_INFO);
spip_log('message info importante', _LOG_INFO_IMPORTANTE);
spip_log('message avertissement', _LOG_AVERTISSEMENT);
spip_log('message erreur', _LOG_ERREUR);
spip_log('message critique', _LOG_CRITIQUE);
spip_log('message alerte', _LOG_ALERTE_ROUGE);
spip_log('message hs', _LOG_HS);

# écriture dans un autre fichier de log
spip_log('message A', 'saisies');
spip_log('message B', 'saisies' . _LOG_ERREUR);
spip_log('message C', 'saisies.' . _LOG_DEBUG);

# niveau de log variable
$level = _LOG_ERREUR;
spip_log('message level variable', $level);

# niveau de gravité de logs écrits (mes_options.php)
define(`_LOG_FILTRE_GRAVITE`, _LOG_DEBUG);
```

#### Après

```php
spip_logger()->info('message defaut (info)');
spip_logger()->error('message d’erreur');

# ensemble des hiérarchies de log
$logger = spip_logger();
$logger->debug('message debug');
$logger->info('message info');
$logger->notice('message info importante');
$logger->warning('message avertissement');
$logger->error('message erreur');
$logger->critical('message critique');
$logger->alert('message alerte');
$logger->emergency('message hs');

# écriture dans un autre fichier de log
spip_logger('saisies')->info('message A');

$logger = spip_logger('saisies');
$logger->error('message B');
$logger->debug('message C');

# niveau de log variable
$level = Psr\Log\LogLevel::ERROR;
spip_logger()->log($level, 'message level variable');

# niveau de gravité de logs écrits (mes_options.php)
define(`_LOG_FILTRE_GRAVITE`, Psr\Log\LogLevel::DEBUG);
```

### Fonctions `extraire_multi` et `extraire_idiome`

Le 3è paramètre `$options` déprécié si booléen.
Ce paramètre `$options` doit être un `array`.

#### Avant

```php
$multi = extraire_multi($texte, 'en', true);
$idiome = extraire_idiome($texte, 'en', true);
```

#### Après

```php
$multi = extraire_multi($texte, 'en', ['echappe_span' => true]);
$idiome = extraire_idiome($texte, 'en', ['echappe_span' => true]);
```

### Fonction `spip_setcookie`

La fonction `spip_setcookie()` reprend les arguments de la fonction php [`setcookie`](https://www.php.net/manual/fr/function.setcookie.php).

Les constantes `_COOKIE_SECURE` et `_COOKIE_SECURE_LIST` sont dépréciées au profit des options
`secure` (activée par défaut en HTTPS) et `httponly` de la fonction

#### Exemple

```php
spip_setcookie('mon_cookie', 'ma valeur', time() + 3600, httponly: true);
```

### Fonction `spip_sha256` (dépréciée en 5.0)

Utiliser la fonction native `hash`

#### Avant

```php
$hash = spip_sha256('mon contenu');
```
#### Après

```php
$hash = hash('sha256', 'mon contenu');
```

### Fonction `abs_url` (dépréciée en 5.0)

Utiliser `url_absolue` ou `liens_absolus` selon.

#### Avant

```php
$texte = abs_url($texte);
$url = abs_url($url);
```

```spip
[(#TEXTE|abs_url)]
[(#URL_ARTICLE|abs_url)]
```

#### Après

```php
$texte = liens_absolus($texte);
$url = url_absolue($url);
```

```spip
[(#TEXTE|liens_absolus)]
[(#URL_ARTICLE|url_absolue)]
```

# Renommage / changement de configurations

## Nettoyage des paramètres d’URI

Une configuration (certainement très peu surchargée) a été modifiée et renommée :

- Introduction de la constante `_CONTEXTE_IGNORE_LISTE_VARIABLES`.
- Suppression de la constante `_CONTEXTE_IGNORE_VARIABLES`.

#### Avant

```php
define('_CONTEXTE_IGNORE_VARIABLES', '/(^var_|^PHPSESSID$|^fbclid$|^utm_)/');
```

#### Après

```php
define('_CONTEXTE_IGNORE_LISTE_VARIABLES', ['^var_', '^PHPSESSID$', '^fbclid$', '^utm_']);
```


## Renommage de la page privé `?exec=admin_tech` en `?exec=admin_bdd`

La page `?exec=admin_tech` devient `?exec=admin_bdd` et se concentre uniquement sur ce qui est gestion des bases de données (ajout, réparation, suppression, effacement).

Par conséquent, il faut adapter :
 - les liens vers la page
 - les éventuelles pipelines qui la modifie (en se demandant dans ce cas la pertinence de créer une nouvelle page plutôt que d'insérer du contenu dans cette page)

## Modification du comportement du formulaire d'édition des liaisons

Le formulaire d'association des liens à un objet est éditable à partir du moment où l'on a l'autorisation de modifier l'objet, le test d'affichage ou non des boutons d'association se faisant désormais dans le squelette du formulaire.

Pour les personnes ayant surchargé les squelettes de ce formulaire ou ayant des pipelines se branchant dessus, il convient d'effectuer les adaptations nécessaires à la modification du paramètre `editable`.


# Suppressions des éléments dépréciés

Les éléments suivants ont été supprimés et doivent être adaptés si ce n’est pas encore le cas.


## Constantes PHP

### `_DIR_RESTREINT_ABS`

La constante `_DIR_RESTREINT_ABS` est dépréciée en SPIP4.4 et supprimée en SPIP5 au profit de paramètres gérés dans un [conteneur de services](https://symfony.com/doc/current/service_container.html).

[Voir aussi la documentation de développement](https://symfony.com/doc/current/components/dependency_injection.html)

#### Avant

```php
function lambda() {
  $chaine = _DIR_RESTREINT_ABS . 'suite';
}
```

Dans un fichier squelette :

```html
[(#CONST{_DIR_RESTREINT_ABS})]
```

#### Après

```php
function lambda() {
    // suivant le contexte (URL vers l'espace privé / répertoire physique "ecrire/")
    $chaine = \SpipLeague\Component\Kernel\param('spip.routes.back_office') . 'suite';
    // ou
    $chaine = \SpipLeague\Component\Kernel\param('spip.dirs.core') . 'suite';
}
```

Dans un squelette :

```html
[(#PARAM{spip.routes.back_office})]
```

## Fichiers de langue

Les fichiers de langue peuplant une variable globale sont supprimés. Retourner directement le tableau PHP.
Note: Cette syntaxe est valide à partir de SPIP 4.1

### Avant

```php
<?php
if (!defined('_ECRIRE_INC_VERSION')) {
  return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(
  'mots_description' => 'Mots et Groupes de mots',
  'mots_slogan' => 'Gestion des mots et groupes de mots dans SPIP'
);
```

### Après

```php
<?php
return [
  'mots_description' => 'Mots et Groupes de mots',
  'mots_slogan' => 'Gestion des mots et groupes de mots dans SPIP'
];
```

Les éléments suivants sont dépréciés et seront supprimés dans une future version.

## Surlignage des mots de recherches

Le surlignage des mots de recherche est déplacé dans [le nouveau plugin Surligne](https://plugins.spip.net/surligne)


## Fonction `formulaire_recherche()`


La fonction `formulaire_recherche()` est supprimée. Utiliser la balise `#FORMULAIRE_RECHERCHE_ECRIRE`.


## Support de NETPMB

NETPBM n'est plus disponible pour la génération des vignettes. Adapter la configuration le cas échéant (Menu « Configuration » -> « Fonctions avancées ».

## Fichiers base/serial.php et base/auxiliaires.php

- les appels à ces fichiers, via `include_spip('base/serial');` par exemple, seront sans effet.
  Ils sont à remplacer par l'appel à la fonction `lister_tables_objets_sql();`.

## Syntaxes de squelettes

### Filtres de `#LOGO_` (dépréciés en 2.1)

Les syntaxes des `#LOGO_xx` avec de faux filtres `|left` `|right` `|center` `|bottom`, `|top`, `|lien` `|fichier` ne sont plus prises en compte.

#### Avant

```spip
- [(#LOGO_ARTICLE|#URL_ARTICLE)]
- [(#LOGO_ARTICLE|fichier)]
- [(#LOGO_ARTICLE|lien)]
- [(#LOGO_ARTICLE|left)]
```

#### Après

```spip
- [(#LOGO_ARTICLE{#URL_ARTICLE})]
- [(#LOGO_ARTICLE**)]
- [(#LOGO_ARTICLE*)]
- [(#LOGO_ARTICLE{left})]
```

Note: les positionnements `left`, `right`, `center`, `bottom`, `top` ajustent simplement une classe CSS.

### Filtre de `#FORMULAIRE_RECHERCHE|parametre` (déprécié en 2.1)

#### Avant

```spip
[(#FORMULAIRE_RECHERCHE|param)]
```

#### Après

```spip
[(#FORMULAIRE_RECHERCHE{param})]
```

### Balise `#EXPOSER` (déprécié en 1.8.2)

#### Avant

```spip
[(#EXPOSER|on,off)]
```

#### Après

```spip
[(#EXPOSE{on,off})]
```

### Balise `#EMBED_DOCUMENT` (déprécié en 2.0)

#### Avant

```spip
[(#EMBED_DOCUMENT|autostart=true)]
```

#### Après

```spip
[(#MODELE{emb, autostart=true})]
```

### Filtre `icone`

Le filtre `icone` est supprimé. Utiliser `icone_verticale` (ou `icone_horizontale`)

#### Avant

```spip
[(#URL_ECRIRE{article_edit,id_article=#ID_ARTICLE}
  |icone{Éditer,article-24.png,#LANG_RIGHT,edit,ajax})]
```

#### Après

```spip
[(#URL_ECRIRE{article_edit,id_article=#ID_ARTICLE}
  |icone_verticale{Éditer,article,edit,right ajax})]
```

### Filtre `foreach`

Le filtre déprécié `foreach` est supprimé. Utiliser une boucle `DATA`.

#### Avant

```spip
[(#LISTE{a,b,c,d}|foreach)]
```

#### Après

```spip
<BOUCLE_liste(DATA){source tableau, #LISTE{a,b,c,d}}>
- #CLE => #VALEUR <br />
</BOUCLE_liste>
```

### Boucle `POUR`

La boucle `POUR` dépréciée est supprimée. Utiliser une boucle `DATA`.

#### Avant

```spip
<BOUCLE_liste(POUR){tableau #LISTE{un,deux,trois}}>
- #CLE : #VALEUR <br />
</BOUCLE_liste>
```

#### Après

```spip
<BOUCLE_liste(DATA){source tableau, #LISTE{un,deux,trois}}>
- #CLE : #VALEUR <br />
</BOUCLE_liste>
```

## Filtre `|image_typo`

Le filtre `|image_typo` n'est plus livré avec SPIP. Installez le plugin [Image Typo](https://plugins.spip.net/image_typo) pour l'obtenir.

## Balise `#LARGEUR_ECRAN`

La balise `#LARGEUR_ECRAN` pour les squelettes de l'espace privé doit être remplacée par `#LAYOUT_PRIVE`.

#### Avant

```spip
#LARGEUR_ECRAN{pleine_largeur}
```

### Après


```spip
#LAYOUT_PRIVE{pleine_largeur}
```

## Fonctions PHP

Dans certains cas parfois utilisées en filtres de squelettes également.

### Fonction `http_status`

La fonction dépréciée `http_status` est supprimée.
Utiliser la fonction PHP native `http_response_code`

#### Avant

```php
http_status(404);
```

#### Après

```php
http_response_code(404);
```

### Fonction `generer_url_ecrire_objet`

La fonction dépréciée `generer_url_ecrire_objet` est supprimée.
Utiliser `generer_objet_url_ecrire`.

#### Avant

```php
$url = generer_url_ecrire_objet(3, 'article');
```

```spip
[(#ID_ARTICLE|generer_url_ecrire_objet{article})]
```

#### Après

```php
$url = generer_objet_url_ecrire(3, 'article');
```

```spip
[(#ID_ARTICLE|generer_objet_url_ecrire{article})]
```

### Fonctions `generer_{x}_entite`

De même que  `generer_url_ecrire_objet` les fonctions dépréciées

- `generer_lien_entite`
- `generer_introduction_entite`
- `generer_info_entite`

Sont supprimées et remplacées (renommées) par

- `generer_objet_lien`
- `generer_objet_introduction`
- `generer_objet_info`

### Fonction de liste `inc_lister_objets_dist`

La fonction dépréciée depuis SPIP 3.1 est supprimée au profit d’inclusions directe de squelettes

#### Avant

```php
$lister_objets = charger_fonction('lister_objets', 'inc');
$html = $lister_objets('breves', [
  'id_rubrique' => $id_rubrique,
]);
```

#### Après

```php
$html = recuperer_fond('prive/objets/liste/breves', [
  'id_rubrique' => $id_rubrique,
],[
  'ajax' => true
]);
```

### Fonctions `json_export` et `var2js`

Les fonctions `json_export` et `var2js`, équivalentes historiques à `json_encode`, sont supprimées.

#### Avant

```php
include_spip('inc/json');
$json = json_export($data);
// ou
$json = var2js($data);
```

#### Après

```php
$json = json_encode($data);
```

## Variables globales

### `auteur_session`

La globale `auteur_session` est supprimée, utilisez `visiteur_session` à la place.

#### Avant

```php
$GLOBALS['auteur_session'];
```

#### Après

```php
$GLOBALS['visiteur_session'];
```

### `$formats_logos`

La globale `$formats_logos` est supprimé, utilisez `_image_extensions_logos()` à la place (en lecture) et le pipeline `image_extensions_logos` en écriture.

#### Avant


```php
$xx = $GLOBALS['formats_logos']; // (Lecture)
$GLOBALS['formats_logos'] = …;// (Écriture)
```
#### Après

```php
$xx = _image_extensions_logos(['objet' => string, 'id_objet' => int]); // (Lecture)

/**
 * (Écriture)
 * Modification des formats de logos autorisés
 * @param array $flux
 * @return array $flux
**/
function <prefix_plugin>_image_extension_logos(array $flux): array {
	$flux['data'] = …;
	return $flux;
}
```
