# UPGRADE de SPIP 4.x à 5.0

Cette page présente les points clés de la migration vers SPIP 5.0. La documentation complète et détaillée est désormais disponible dans le répertoire `packages/spip/docs/`.

## Documentation complète

- **[Index de la documentation SPIP 5.0](https://git.spip.net/spip/docs/-/tree/main/docs/fr/5.0/index.md)**
- **[Guide de migration détaillé](https://git.spip.net/spip/docs/-/tree/main/docs/fr/5.0/upgrade/index.md)**
- **[Nouvelles fonctionnalités (Symfony, Events, Cron...)](https://git.spip.net/spip/docs/-/tree/main/docs/fr/5.0/features/index.md)**

---

## Résumé des changements majeurs

### Prérequis techniques
- **PHP 8.2** minimum requis.
- Migration de base de données possible depuis SPIP 3.2+ (SPIP 4.1+ conseillé).

### Modernisation de l'architecture
SPIP 5.0 intègre des composants Symfony et adopte de nouveaux standards :
- **Kernel & Environnement :** Support des fichiers `.env`.
- **Injection de dépendances :** Utilisation d'un conteneur de services (`config/services.php`).
- **Event Dispatcher :** Remplacement progressif des pipelines par des événements typés et des attributs `#[AsPipelineListener]`.
- **Tâches Cron :** Déclaration simplifiée via l'attribut `#[AsCronTask]`.

### Changements de rupture (Breaking Changes)
- **Plugins-dist :** Déplacés dans `plugins-dist/spip/`.
- **Squelettes :** Suppression de l'inclusion de fichiers PHP, suppression de `</INCLURE>`.
- **Javascript :** Passage aux modules ESM, émancipation de jQuery.
- **API PHP :** Nouveau système de log (PSR-3), suppression de nombreuses fonctions et constantes historiques au profit du Kernel.

### Espace privé
- Menu « Squelettes » renommé en **« Apparence »**.
- Page `admin_tech` renommée en **`admin_bdd`**.
- Simplification des fonctions de boîtes et cadres (`boite_ouvrir` / `boite_fermer`).

---

Pour chaque sujet, veuillez vous référer aux guides détaillés dans le dépot [spip/docs](https://git.spip.net/spip/docs/-/tree/main/docs/fr/5.0).
