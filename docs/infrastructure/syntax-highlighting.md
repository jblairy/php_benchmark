# Syntax Highlighting avec Highlight.js

## Vue d'ensemble

Le projet utilise [Highlight.js](https://highlightjs.org/) pour la coloration syntaxique du code PHP. Highlight.js est une librairie JavaScript légère et fiable, configurée spécifiquement pour le code PHP avec le thème GitHub Dark.

## Architecture

```
assets/
├── controllers/
│   └── syntax_highlighter_controller.js    # Contrôleur Stimulus
└── styles/
    └── components/
        └── _code-highlight.scss            # Styles pour les blocs de code

importmap.php                               # Configuration Highlight.js
```

## Installation

Highlight.js est installé via Symfony AssetMapper :

```bash
# Installation de Highlight.js
php bin/console importmap:require highlight.js

# Installation des thèmes (optionnel, nous utilisons CDN)
php bin/console importmap:require highlight.js/styles/github-dark.css
```

## Comment ça fonctionne

### 1. Mécanisme de chargement

Le contrôleur Stimulus :
- **Import direct** : `import hljs from 'highlight.js'`
- **Configuration unique** : Initialisée une seule fois
- **Thèmes dynamiques** : Chargés via CDN selon le besoin

### 2. Processus de highlighting

1. **Détection** : Le contrôleur détecte les éléments avec `data-controller="syntax-highlighter"`
2. **Préparation** : Le code est placé dans un élément `<code>` si nécessaire
3. **Coloration** : Highlight.js applique la coloration syntaxique
4. **Thème** : Le CSS du thème est chargé dynamiquement

## Utilisation

### 1. Coloration automatique dans les templates Twig

```twig
{# Usage standard #}
<pre data-controller="syntax-highlighter">{{ code }}</pre>

{# Avec élément code (recommandé) #}
<pre data-controller="syntax-highlighter"><code>{{ code }}</code></pre>
```

Le contrôleur est configuré pour :
- **Langage** : PHP uniquement
- **Thème** : GitHub Dark (intégré dans les styles)

### 2. Configuration

- **Langage** : PHP uniquement
- **Thème** : GitHub Dark (chargé via `_highlight-theme.scss`)
- **Styles** : Personnalisés dans `_code-highlight.scss`

## Styles CSS

Les styles sont définis dans `assets/styles/components/_code-highlight.scss` :

```scss
pre[data-controller="syntax-highlighter"],
pre.hljs {
    border-radius: 8px;
    padding: 1rem;
    overflow-x: auto;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 14px;
    line-height: 1.6;
}
```

## Performance

### Optimisations implémentées

1. **Configuration unique** : Highlight.js est configuré une seule fois
2. **Thèmes à la demande** : Chargés uniquement si utilisés
3. **CDN** : Les thèmes CSS sont servis via CDN
4. **Détection auto** : Si le langage n'est pas spécifié, Highlight.js le détecte

### Métriques

- Taille : ~30KB (core) + ~2KB par thème
- Performance : <10ms par bloc de code
- Compatible avec tous les navigateurs modernes

## Cas d'usage actuels

1. **Benchmark cards** (`templates/components/BenchmarkCard.html.twig`)
   - Code PHP des benchmarks
   - Thème `github-dark` pour cohérence visuelle

## Dépannage

### Le code n'est pas colorisé

1. **Vérifier la console** : Chercher des erreurs JavaScript
2. **Vérifier l'attribut** : `data-controller="syntax-highlighter"`
3. **Langage valide** : Vérifier que le langage existe dans Highlight.js

### Thème non appliqué

1. **Vérifier le nom** : Le thème doit être dans la liste supportée
2. **Console réseau** : Vérifier que le CSS est bien chargé
3. **Cache navigateur** : Forcer le rechargement (Ctrl+Shift+R)

## Architecture technique

### Pourquoi Highlight.js ?

- **Simplicité** : API simple et directe
- **Fiabilité** : Projet mature et stable depuis 2006
- **Performance** : Léger et rapide
- **Compatibilité** : Fonctionne parfaitement avec AssetMapper
- **Auto-détection** : Détecte automatiquement le langage

### Flux de données

1. **Template Twig** → Génère le HTML avec data attributes
2. **Stimulus** → Détecte et initialise le contrôleur
3. **Highlight.js** → Applique la coloration syntaxique
4. **CSS** → Styles du thème appliqués

## Évolutions futures

- [ ] **Copy button** : Bouton pour copier le code
- [ ] **Line numbers** : Plugin pour numéroter les lignes
- [ ] **Language badge** : Afficher le langage détecté
- [ ] **More themes** : Ajouter plus de thèmes populaires
- [ ] **Custom theme** : Créer un thème personnalisé pour le projet