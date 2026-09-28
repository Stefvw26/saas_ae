<?php
// fichier : views/partials/telephone.php — composant transverse téléphone (CDC §35)
declare(strict_types=1);

 $nom    = $nom ?? 'telephone';
 $valeur = trim((string)($valeur ?? ''));
 $id     = $id ?? ('tel_' . $nom);

/* Découpe « +33 6 12 34 56 78 » => ['33', '6 12 34 56 78']. */
[$indicatifCourant, $numero] = telephone_splitter($valeur);

 $pays = pays_indicatifs();
 $flags = [];
foreach ($pays as $iso => $info) {
    $flags[$iso] = 'https://flagcdn.com/w20/' . $iso . '.png';
}
 $isoCourant = 'fr';
foreach ($pays as $iso => $info) {
    if ($info[1] === $indicatifCourant) {
        $isoCourant = $iso;
        break;
    }
}
?>
<div class="phone-field" data-phone="<?= e($nom) ?>">
    <img class="phone-flag" src="https://flagcdn.com/w20/<?= e($isoCourant) ?>.png" alt="" aria-hidden="true">
    <select class="input phone-indicatif" name="<?= e($nom) ?>_indicatif"
            aria-label="Indicatif pays"
            data-flags='<?= e(json_encode($flags, JSON_UNESCAPED_SLASHES)) ?>'>
        <?php foreach ($pays as $iso => [$nomPays, $indicatif]): ?>
            <option value="<?= e($indicatif) ?>" data-iso="<?= e($iso) ?>"<?= $indicatif === $indicatifCourant ? ' selected' : '' ?>>
                +<?= e($indicatif) ?> · <?= e($nomPays) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input class="input" type="tel" id="<?= e($id) ?>" name="<?= e($nom) ?>_numero"
           value="<?= e($numero) ?>" placeholder="6 12 34 56 78" autocomplete="tel-national">
    <input type="hidden" name="<?= e($nom) ?>" value="<?= e($valeur) ?>">
</div>
<!-- AE-EOF : le fichier partials/telephone.php doit se terminer exactement ici -->