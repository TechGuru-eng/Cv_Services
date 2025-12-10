# Optimisation SEO Internationale – HireMe CV Services

**Domaine** : https://moncvpro.hiremeguide.com  
**Cibles** : Afrique, USA, Canada, UK, Europe  
**Structure** : Mobile-first, Single Page + Pages légales

---

## 1. Méta-données et Balises (Par page)

### A. Accueil (`/`)
*Cible : Point d'entrée global.*

*   **Title (57 car.)** : CV Pro & Rédaction de CV En Ligne | USA, Canada, Europe
*   **Description (155 car.)** : Boostez votre carrière avec un CV professionnel optimisé ATS. Service de rédaction expert pour l'international (USA, Canada, UK, Afrique). Commande 100% en ligne.
*   **Keywords** : redaction cv professionnel, online resume service, cv writing service usa, cv canada, lettre de motivation
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/" />`

### B. Packs & Tarifs (`/packages`)
*Cible : Conversion et détail des offres.*

*   **Title (58 car.)** : Tarifs CV Pro – Packs Étudiant, Expert & International
*   **Description (158 car.)** : De 8000 à 35000 FCFA. Packs CV, Lettre de motivation et LinkedIn. Solutions adaptées pour étudiants, pros et cadres internationaux. Livraison rapide 48h.
*   **Keywords** : resume pricing, tarifs redaction cv, cheap resume writing, cv professionnel prix, linkedin optimization cost
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/packages" />`

### C. Formulaire de commande (`/order`)
*Cible : Transactionnelle.*

*   **Title (55 car.)** : Commander votre CV Professionnel – HireMe Services
*   **Description (150 car.)** : Remplissez le formulaire pour commander votre CV pro. Paiement sécurisé, upload facile. Code promo HIREME60 accepté. Lancez votre carrière maintenant.
*   **Keywords** : order resume online, commander cv, cv service order, resume writing form
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/order" />`

### D. Code Promo (`/promo`)
*Cible : Acquisition trafic "coupon".*

*   **Title (60 car.)** : Code Promo HireMe CV : -60% Réduction (HIREME60) | Offre
*   **Description (145 car.)** : Économisez 60% sur votre rédaction de CV avec le code HIREME60. Offre limitée pour booster votre employabilité en Afrique et à l'international.
*   **Keywords** : promo code resume service, reduction cv, hireme60, coupon redaction cv, discount resume writer
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/promo" />`

### E. Conditions générales (`/terms` ou `conditions-generales.html`)
*Cible : Légal.*

*   **Title (45 car.)** : Conditions Générales (CGV) – HireMe CV
*   **Description (130 car.)** : Conditions de vente et d'utilisation des services HireMe. Politique de livraison, paiement et révision.
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/conditions-generales.html" />`

### F. Politique de confidentialité (`/privacy` ou `politique-confidentialite.html`)
*Cible : Confiance RGPD.*

*   **Title (50 car.)** : Politique de Confidentialité – Données & RGPD
*   **Description (140 car.)** : Protection de vos données personnelles et CV. Conformité RGPD pour l'Europe et l'International. Vos informations restent confidentielles.
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/politique-confidentialite.html" />`

### G. Contact (`/contact`)
*Cible : Support.*

*   **Title (50 car.)** : Contactez HireMe CV Services – Email & WhatsApp
*   **Description (145 car.)** : Une question ? Contactez notre support expert par email ou WhatsApp. Assistance réactive pour votre commande de CV et lettre de motivation.
*   **Canonical** : `<link rel="canonical" href="https://moncvpro.hiremeguide.com/contact" />`

---

## 2. Open Graph (Facebook / LinkedIn) & Twitter Cards

À insérer dans le `<head>` de la page d'accueil (et adapter dynamiquement si multipage).

```html
<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://moncvpro.hiremeguide.com/">
<meta property="og:title" content="CV Pro & Rédaction de CV En Ligne | USA, Canada, Europe">
<meta property="og:description" content="Boostez votre carrière avec un CV professionnel optimisé ATS. Expert pour l'international (USA, Canada, UK, Afrique). -60% avec le code HIREME60.">
<meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/og-image-hireme.jpg">
<meta property="og:locale" content="fr_FR">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://moncvpro.hiremeguide.com/">
<meta name="twitter:title" content="HireMe CV Services – Votre carrière mérite un expert">
<meta name="twitter:description" content="CV et Lettres de motivation rédigés par des experts. Promo 15 jours: HIREME60 (-60%). Livraison 48h.">
<meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/twitter-card-hireme.jpg">
```

---

## 3. Données Structurées JSON-LD

À placer juste avant la fermeture `</body>` ou dans le `<head>`. Ce script définit l'organisation et le produit principal.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "ProfessionalService",
      "@id": "https://moncvpro.hiremeguide.com/#organization",
      "name": "HireMe CV Services",
      "url": "https://moncvpro.hiremeguide.com",
      "logo": "https://moncvpro.hiremeguide.com/assets/logo.png",
      "image": "https://moncvpro.hiremeguide.com/assets/hero.jpg",
      "description": "Service de rédaction de CV professionnels, lettres de motivation et optimisation LinkedIn pour l'Afrique, l'Europe et l'Amérique du Nord.",
      "priceRange": "$$",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "CM",
        "addressRegion": "Littoral",
        "addressLocality": "Douala"
      },
      "telephone": "+237699000000",
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday"
        ],
        "opens": "08:00",
        "closes": "20:00"
      },
      "sameAs": [
        "https://www.facebook.com/hiremecv",
        "https://www.linkedin.com/company/hiremecv"
      ]
    },
    {
      "@type": "Offer",
      "name": "Pack Pro CV + Lettre",
      "price": "15000",
      "priceCurrency": "XAF",
      "url": "https://moncvpro.hiremeguide.com/packages",
      "category": "Resume Writing",
      "availability": "https://schema.org/InStock",
      "validFrom": "2024-01-01",
      "seller": {
        "@id": "https://moncvpro.hiremeguide.com/#organization"
      }
    }
  ]
}
</script>
```

---

## 4. Stratégie de Mots-clés

### Mots-clés Primaires (High Volume / Core)
1.  Rédaction CV Professionnel
2.  CV writing service
3.  Resume service USA
4.  CV Canadien format
5.  Lettre de motivation
6.  CV en ligne
7.  Optimisation LinkedIn
8.  Refaire son CV
9.  Correction CV
10. HireMe CV Services
11. Professional resume writer
12. CV pour ATS
13. Modèle CV 2024
14. Service CV Afrique
15. Aide recherche emploi

### Mots-clés Secondaires (Contextuels)
1.  CV pour étudiant
2.  Traduction CV anglais
3.  CV format européen
4.  Emploi au Canada pour étrangers
5.  Immigration travail USA
6.  Recrutement international
7.  Passer les filtres ATS
8.  Mise en page CV moderne
9.  CV design simple
10. Profil LinkedIn attractif

### Longue Traîne (Intention spécifique)
1.  "Comment faire un CV pour le Canada depuis l'Afrique"
2.  "Meilleur service rédaction CV Douala"
3.  "Prix rédaction lettre motivation expert"
4.  "Professional resume writing services for international students"
5.  "Adapter son CV pour le marché américain"

---

## 5. Checklist Technique & Performance

### Core Web Vitals (Mobile-First)
*   [x] **Images** : Format WebP, attributs `width`/`height` explicites, `loading="lazy"` pour les images sous la ligne de flottaison.
*   [x] **CSS/JS** : Minification activée, chargement asynchrone ou différé des scripts non critiques.
*   [x] **Contraste** : Vérifier le ratio 4.5:1 (déjà fait via le thème Tailwind).
*   [x] **HTTPS** : Force HTTPS via redirection serveur (HSTS recommandé).

### Internationalisation
*   **Langue** : Balise `<html lang="fr">` présente.
*   **Hreflang** : Si une version anglaise est créée plus tard, ajouter `<link rel="alternate" hreflang="en" href="..." />`. Pour l'instant, le ciblage géographique se fera via la GSC et le contenu.

### Structure
*   **Heading H1** : Unique par page.
    *   Accueil : "Boostez votre carrière avec un CV Professionnel"
*   **Alt Text** : Toutes les images (même décoratives) doivent avoir une balise alt (vide si décorative).

---

## 6. Guide Configuration Google Search Console (GSC)

1.  **Création Propriété** :
    *   Ajouter la propriété **Domaine** (`hiremeguide.com`) ou **Préfixe d'URL** (`https://moncvpro.hiremeguide.com`).
    *   Validation via enregistrement DNS (TXT) ou fichier HTML.

2.  **Sitemaps** :
    *   Aller dans **Indexation > Sitemaps**.
    *   Soumettre : `https://moncvpro.hiremeguide.com/sitemap.xml`
    *   Vérifier le statut "Réussite".

3.  **Ciblage International** (Si propriété Préfixe d'URL) :
    *   Dans *Anciens outils et rapports*, définir le pays cible sur "Non défini" (pour viser l'international global) ou cibler un pays spécifique si la stratégie change.

4.  **Inspection** :
    *   Utiliser l'outil d'inspection sur l'URL d'accueil.
    *   Demander l'indexation manuelle après la mise en ligne initiale.

5.  **Suivi** :
    *   Surveiller le rapport "Performances" pour les requêtes "USA", "Canada", "CV".
    *   Surveiller les "Core Web Vitals" (Signaux Web Essentiels) mobile.
