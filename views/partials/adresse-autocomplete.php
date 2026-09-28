<?php
// fichier : views/partials/adresse-autocomplete.php — composant transverse (CDC §34)
declare(strict_types=1);

/**
 * Auto-complétion d'adresse — fournisseur : OpenStreetMap / Nominatim
 * (choix technique documenté : cohérent avec Leaflet déjà utilisé, sans clé).
 * Ne rend QUE la zone de recherche : les champs du formulaire restent des
 * inputs normaux (noms passés dans $champs) et sont remplis par app.js
 * lors de la sélection d'une suggestion (adresse, code postal, ville,
 * pays, latitude, longitude).
 *
 * $champs = [clé logique => nom de l'input dans le formulaire]
 * clés supportées : adresse, code_postal, ville, pays, latitude, longitude.
 */
 $champs = $champs ?? [];
?>
<?php if ($champs !== []): ?>
<div class="addr-autocomplete">
    <label class="form-label" for="addr_search">Rechercher une adresse (auto-complétion)</label>
    <div class="addr-search-wrap">
        <input class="input addr-search" type="search" id="addr_search"
               placeholder="Commencez à saisir une adresse… (3 caractères minimum)"
               autocomplete="off"
               data-champs='<?= e(json_encode($champs, JSON_UNESCAPED_UNICODE)) ?>'>
        <svg class="addr-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
            <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <ul class="addr-results" role="listbox"></ul>
    </div>
    <p class="form-text">
        Sélectionnez une suggestion pour remplir adresse, code postal, ville, pays et coordonnées.
        Fournisseur : © OpenStreetMap / Nominatim.
    </p>
</div>
<?php endif; ?>
<!-- AE-EOF : le fichier partials/adresse-autocomplete.php doit se terminer exactement ici -->