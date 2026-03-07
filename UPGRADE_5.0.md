# UPGRADE de SPIP 4.x à 5.0

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

### Casse des items de langues

Avant SPIP 5.0, les items de langues en squelettes étaient automatiquement passées en minuscules. Ainsi `<:toto:>` était équivalent à `<:TOTO:>`.

À partir de SPIP 5.0, les deux ne seront plus équivalent. Il convient de vérifier ses squelettes.

Il est _fortement conseillé_ de n'utiliser que des items de langue en minuscule.

### Suppression de l'inclusion en squelette de fichier PHP

L'inclusion en squelette de fichier PHP via `<INCLURE>` est supprimé

#### Avant

```
<INCLURE(fichier.php)>
```
ou

```
<INCLURE{fond=fichier.php}>
```
#### Après

```
<INCLURE{fond=fichier_de_squelette}>
```

### Suppression de la balise fermante `</INCLURE>`

La balise fermant `</INCLURE>` est supprimée.

#### Avant

````
<INCLURE{fond=xxx}...></INCLURE>
````

après


````
<INCLURE{fond=xxx}... />
````

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

## Test HTML5

Les tests pour envoyer un code différent selon que l'on est où pas en HTML5 sont dépréciés et seront supprimés en SPIP 6.

### Avant

En squelette
```spip
[(#HTML5|oui) <code_specifique_html5>]
```

En php

```php
if (html5_permis()) {
    <code_specifique_html5>
}
```

### Après

Utiliser directement du code HTML5.

## Fonction `mail_embarquer_pieces_jointes()`

La création d'une fonction `mail_embarquer_pieces_jointes()` est dépréciée.

### Avant

```php
function mail_embarquer_pieces_jointes(....) {
    ....
}
```

### Après

Utiliser le plugin `facteur`.

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

## Le menu « Squelettes » de l'espace privé devient « Apparence »

Le menu « Squelettes » (identifiant `menu_squelette`) de l'espace privé est renommé « Apparence » (identifiant `menu_apparence`).

Bien qu'une rétrocompatibilité soit assurée, il convient de corriger dès maintenant les appels.

### Avant

```
<menu nom="..." titre="..." parent="menu_squelette" icone="..." action="..." />
```

### Après

```
<menu nom="..." titre="..." parent="menu_apparence" icone="..." action="..." />
```

## Les menus préfixés par `bando_` doivent être préfixés par `menu_`

Si un menu a pour parent un menu dont l'identifiant commençe par `bando_`, celui-ci continue d'être automatiquement converti en menu commençant par `menu_`. Mais cette conversion est dépréciée et sera supprimée en 6.0. Il convient de corriger les appels.


### Avant

```
<menu nom="..." titre="..." parent="bando_xxx" icone="..." action="..." />
```

### Après

```
<menu nom="..." titre="..." parent="menu_xxx" icone="..." action="..." />
```

# Suppressions des éléments dépréciés

Les éléments suivants ont été supprimés et doivent être adaptés si ce n’est pas encore le cas.

## Unserialization dans `table_valeur` et dans la boucle `(DATA)` source `tableau`

Le filtre `|table_valeur` tentait d'appliquer la fonction PHP `unserialize()` si une chaîne lui était passée.

De même pour une boucle `(DATA)`, la source `{source tableau,}` passait par `unserialize()` si elle n'était pas déjà un tableau.

Cette fonctionnalité est supprimée. Passez directement un tableau à `|table_valeur` et à `{source tableau,}`.

ATTENTION : ne jamais unserializer une chaîne provenant de `#ENV` ou de toute source non contrôlée. Cela peut conduire à des failles de sécurité.



## Recherche d'un fichier `ecrire/mes_options.php`.

La recherhe d'un fichier `mes_options` dans `ecrire` n'est plus supporté. Le fichier `mes_options` doit forcément se trouver dans `config`.

## Constantes PHP

Certaines constantes sont dépréciées en SPIP4.4 et supprimée en SPIP5

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

### `_ROOT_RACINE`

#### Avant

```php
$path = _ROOT_RACINE;
```

#### Après


```php
use function \SpipLeague\Component\Kernel\app;
$path = app()->getRootDir();
```

### `_ROOT_RESTREINT`

#### Avant

```php
$path = _ROOT_RESTREINT;
```

#### Après


```php
use function \SpipLeague\Component\Kernel\app;
$path = app()->getCoreDir();
```

### `_ROOT_CWD`


#### Avant

```php
$cwd = _ROOT_CWD;
```

#### Après

```php
use function \SpipLeague\Component\Kernel\app;

$cwd = app()->getCwd();
// ou
$cwd = \SpipLeague\Component\Kernel\app()->getCwd();
```


### `_DIR_LOG`


#### Avant

```php
_DIR_LOG
```

#### Après

```php
use function \SpipLeague\Component\Kernel\app;
app()->getLogDir()
```

### `_ESPACE_PRIVE`

#### Avant

```php
if (defined('_ESPACE_PRIVE')) {
    ...
} else {
    ...
}
```

En squelette :

```spip
[(#CONST{_ESPACE_PRIVE}|?{oui,non})]
```

#### Après

```php
use function \SpipLeague\Component\Kernel\app;
if (app()->isBackOffice()) {
    ...
} else {
    ...
}
```
En squelette :

```spip
[(#NULL|test_espace_prive|?{oui,non})]
```


### `_ROOT_IMG_PACK` & `_DIR_IMG_PACK`

#### Avant

```php
$absolute = _ROOT_IMG_PACK;
$relative = _DIR_IMG_PACK;
```

#### Après

```php
use function \SpipLeague\Component\Kernel\app;
$absolute = app()->getPrivateTemplateDir() . _NOM_IMG_PACK;
$relative = app()->relative()->getPrivateTemplateDir() . _NOM_IMG_PACK;
```

Préferez cependant `find_in_theme()` ou `chemin_image()` (qui recherchent dans des dossiers différents).

### `_IS_CLI`

#### Avant

```php
if (_IS_CLI) {
  // ...
}

```

#### Après

```php
if (PHP_SAPI === 'cli') {
  // ...
}

```

## Fonctions et variables Javascript

SPIP5 amorce une émancipation du code javascript hors du giron de jQuery, et se tourne vers l'adoption d'usages plus modernes via les ESM. Ce chantier débute avec le fichier `config.js` dédié au stockage de la configation de SPIP et de ses plugins, et le fichier `ajaxCallBack.js`, en charge des rechargements dynamiques.

Les fonctions concernées par la migration sont les suivantes :
`ajaxReload`,`onAjaxLoad`,`triggerAjaxLoad`,`positionner`,`animateLoading`,`endLoading`,`animateRemove`,`animateAppend`,`parametre_url`,`followLink`,`ajaxClick`

Il est recommandé de passer par une directive `import` lorsque l'usage d'une balise `<script type="module">` est possible.
```javascript
import { parametre_url, animateLoading, onAjaxLoad } from "ajaxCallback.js";
```
Les balises `<script type="module">` qui contiennent une directive `import` s'exécutent de manière asynchrone et différée, une fois que toutes les ressources javascript de la page ont été résolues. Auparavant, il fallait s'assurer de différer l'exécution du code en l'encapulant dans une fonction `$(document).ready(function(){...})` ou `$(function(){...})`.

### Avant
```html
<script>
  // déclaration directe d'une propriété de l'objet window (non recommandé)
  var ajax_image_searching = "⌛";

  // code à exécuter au chargement complet
  $(document).ready(function(){
    // lecture depuis l'objet jQuery
    console.log(jQuery.spip.load_handlers);
    // animation d'un bloc
    jQuery('.mon_bloc').animateLoading();
  });
</script>
```

### Après
```html
<!-- Pour les modules ESM -->
<script type="module">
  import { default as spip } from "config.js";
  import { animateLoading, onAjaxLoad } from "ajaxCallback.js";
  // lecture
  console.log(spip.load_handlers);
  // ecriture
  spip.ajax_image_searching = "⌛";
  // animation d'un bloc
  const monbloc = document.querySelector('.mon_bloc');
  if (monbloc) {
    animateLoading(monbloc);
  }
</script>

<!-- Pour le code non ESM, on peut consulter l'objet spipConfig -->
<script>
  // code à exécuter au chargement complet
  document.addEventListener("DOMContentLoaded", () => {
    // lecture
    console.log(spipConfig.load_handlers);
    // ecriture
    spipConfig.ajax_image_searching = "⌛";
  });
</script>
```
Un fichier `retrocompat.js` tâche d'assurer la compatibilité avec la syntaxe historique.
Ajouter le paramètre `?var_mode=debug_js` dans l'url permet de tracer les usages dépréciés dans la console.

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

## Paramètre des fonctions de traductions `_T()` et `_L()`

Le 3è paramètre (options) des fonctions `_T` et `_L` ne peut
plus être une chaîne (nom d’une classe CSS) et doit toujours
être un tableau.

### Avant

```php
_T('cle', [], 'classe_css');
_L('texte', [], 'classe_css');
```

### Apres

```php
_T('cle', [], ['class' => 'classe_css']);
_L('texte', [], ['class' => 'classe_css']);
```

## Surlignage des mots de recherches

Le surlignage des mots de recherche est déplacé dans [le nouveau plugin Surligne](https://plugins.spip.net/surligne)

## Détection automatique de la valeur du 5eme argument de `calculer_parties()`.

Le 5eme argument de la fonction `calculer_parties()` utilisée pour compiler certains critères doit désormais être passé explicitement.

### Avant

```php

$boucle->total_parties = $pas;
calculer_parties($boucles, $idb, $partie, $mode);
```

### Après

```php

$boucle->total_parties = $pas;
calculer_parties($boucles, $idb, $partie, $mode, $pas);
```

## Fonction `formulaire_recherche()`


La fonction `formulaire_recherche()` est supprimée. Utiliser la balise `#FORMULAIRE_RECHERCHE_ECRIRE`.


## Support de NETPMB

NETPBM n'est plus disponible pour la génération des vignettes. Adapter la configuration le cas échéant (Menu « Configuration » -> « Fonctions avancées ».

## Fichiers base/serial.php et base/auxiliaires.php

- les appels à ces fichiers, via `include_spip('base/serial');` par exemple, seront sans effet.
  Ils sont à remplacer par l'appel à la fonction `lister_tables_objets_sql();`.

## Classes PHP à la racine de l'espace de nom

Certaines classes PHP du compilateur de SPIP disposaient d'un alias à la racine de l'espace de nom. Ces alias sont supprimés.
Il convient de faire explicitement appel à `use` en début des fichiers qui utilisent ces classes.

Ainsi selon les besoins :
```php
use Spip\Compilateur\Noeud\Boucle;
use Spip\Compilateur\Noeud\Champ;
use Spip\Compilateur\Noeud\Contexte;
use Spip\Compilateur\Noeud\Critere;
use Spip\Compilateur\Noeud\Polyglotte;
use Spip\Compilateur\Noeud\Texte;
```

## Second argument de `spip_sanitize_from_request()`

La fonction `spip_sanitize_from_request($valeur, $cle)` nettoie une valeur, si elle provient de l'environnement. La clé est passée en second argument.
Par le passé, cette fonction pouvait recevoir un tableau ou un
simple astérique (`'*'`) comme second argument. Ce n'est plus le cas.

Pour sanitizer un _table de valeur_ issues de `_request()`,  utiliser `spip_sanitize_env_from_request()`.

### Avant

```php
$env = spip_sanitize_from_request($env, ['key1', 'key2']);//Sanitization partielle
$env = spip_sanitize_from_request($env, '*');//Sanitization de tout env
```

### Après

```php
$env = spip_sanitize_env_from_request($env, ['key1', 'key2']); // Sanitization partielle
$env = spip_sanitize_env_from_request($env); // Sanitization complète

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

## Support typo de la balise `<math>`

Les traitements typographiques ne gèrent plus la balise `<math>` nativement.

Le plugin MathJax procure cette fonctionnalité : https://plugins.spip.net/mathjax.html
en récupérant certaines fonctions comme `traiter_math()`.

En parallèle une fonction non utilisée est supprimée,
`mathml2unicode` et le fichier de définition de charset associé.


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

### Fonctions `generer_{x}_entite` et `generer_{x}_{objet}`

De même que  `generer_url_ecrire_objet` les fonctions `generer_*_entite` dépréciées sont supprimées. Les fonctions remplaçantes ont les mêmes arguments.

| Supprimé | Remplacé par |
| - | - |
| `generer_url_entite` | `generer_objet_url` |
| `generer_url_entite_absolue` | `generer_objet_url_absolue` |
| `generer_url_ecrire_objet` | `generer_objet_url_ecrire` |
| `generer_url_ecrire_entite_edit` | `generer_objet_url_ecrire_edit` |
| `generer_introduction_entite` | `generer_objet_introduction` |
| `generer_info_entite` | `generer_objet_info` |
| `generer_lien_entite` | `generer_objet_lien` |

Les fonctions personnalisées plus spécifiques `generer_{x}_{objet}`, tel que `generer_introduction_article` sont supprimées.
Utiliser `generer_{objet}_{x}` tel que `generer_article_introduction`

De même, les fonctions génériques `generer_{x}_entite` sont supprimées. Utiliser `generer_objet_{x}`

Note: Ces diverses fonctions peuvent être appelées depuis `generer_objet_info`,
qui cherchera une fonction spécifique, si elle existe, pour répondre.

| Supprimé | Remplacé par |
| - | - |
| `generer_{x}_{objet}` | `generer_{objet}_{x}` |
| `generer_{x}_entite` | `generer_objet_{x}` |

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


### Filtre `regledetrois`

La fonction ou filtre `regledetrois` est supprimée.
Elle ne servait plus dans le core et n’a pas vraiment d’utilité.

#### Avant

```html
[(#VAL{6}|regledetrois{4,3})] retourne 8
```

#### Après

```html
[(#VAL{6}|div{3}|mult{4})] retourne 8
```

### Fonctions `debut_cadre_xx()`, `fin_cadre_xx()`, `debut_boite_xx()`, `fin_boite_xx()` et apparentées

Les fonctions de mise en page de l'espace privé suivantes étaient depréciées et sont maintenant supprimées :

- `debut_cadre()` / `fin_cadre()`
- `debut_cadre_relief()` / `fin_cadre_relief()`
- `debut_cadre_enfonce()` / `fin_cadre_enfonce()`
- `debut_cadre_sous_rub()` / `fin_cadre_sous_rub()`
- `debut_cadre_couleur()` / `fin_cadre_couleur()`
- `debut_cadre_trait_couleur()` / `fin_cadre_trait_couleur()`
- `debut_boite_alerte()` / `fin_boite_alerte()`
- `debut_boite_info()` / `fin_boite_info()`
- `cadre_depliable()`
- `bloc_des_raccourcis()`

Leurs usages doivent être remplacés par `boite_ouvrir()` / `boite_fermer()`.

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

### Constante `_SPIP_LOCK_MODE`

La constante `_SPIP_LOCK_MODE` réglée a `2` permettait une gestion spécifique des locks de fichiers dans le cadre d'un SPIP installé sur point de montage NFS.

Cette fonctionnalité n'est plus disponible, utilisez le plugin `nfslock` pour en bénéficier de nouveau.

### Avant

```php
define('_SPIP_LOCK_MODE', 2);
```

### Après

Utiliser le plugin `nfslock`


# Autres suppressions

## Champ PGP sur les auteurs

Le champ 'pgp' sur les auteurs n’est plus créé / défini : il n’était plus utilisé.

Dans le cadre d’une migration d’un site existant vers SPIP 5,
si vous utilisiez auparavant ce champ `pgp` sur la table `spip_auteurs`
pour stocker des données, vous pouvez utiliser le plugin Champs Extras Interfaces
pour le gérer à nouveau. C’est un champ "Bloc de texte" (textearea) standard, que vous
pouvez activer comme cela depuis l’interface d’administration :

> Configuration > Champs Extras > Liste des champs présents non gérés > Auteurs > Pgp > Gérer ce champ
