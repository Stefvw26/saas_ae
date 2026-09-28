<?php
// fichier : views/account/password.php — v0.29 (sheet)
declare(strict_types=1);

 $user      = $user ?? [];
 $politique = $politique ?? ['longueur' => 12, 'majuscules' => true, 'chiffres' => true, 'speciaux' => true];
?>
<div class="page-form">
    <h1 class="page-title">Mon compte</h1>
    <p class="page-subtitle">Changement de mot de passe pour <strong><?= e($user['login'] ?? '') ?></strong>.</p>

    <form method="post" action="<?= url('/mon-compte') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card form-sheet">
            <section class="sheet-section">
                <div class="sheet-title">Mot de passe</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="mot_de_passe_actuel">Mot de passe actuel</label>
                        <input class="input" type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel"
                               autocomplete="current-password" required>
                    </div>
                    <div class="field">
                        <label class="form-label" for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                        <div class="pw-field">
                            <input class="input" type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe"
                                   autocomplete="new-password" required minlength="<?= (int)$politique['longueur'] ?>">
                            <button type="button" class="pw-btn" data-toggle-password tabindex="-1" title="Afficher / masquer" aria-label="Afficher ou masquer le mot de passe">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <button type="button" class="pw-btn pw-btn-gen" data-generate-password tabindex="-1"
                                    data-length="<?= (int)$politique['longueur'] ?>"
                                    data-upper="<?= !empty($politique['majuscules']) ? '1' : '0' ?>"
                                    data-digits="<?= !empty($politique['chiffres']) ? '1' : '0' ?>"
                                    data-special="<?= !empty($politique['speciaux']) ? '1' : '0' ?>"
                                    title="Générer un mot de passe conforme" aria-label="Générer un mot de passe">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11A8 8 0 1 0 12.7 20a8 8 0 0 1 0-16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <p class="form-text">
                            <?= (int)$politique['longueur'] ?> caractères minimum
                            <?php if (!empty($politique['majuscules'])): ?>· majuscule<?php endif; ?>
                            <?php if (!empty($politique['chiffres'])): ?>· chiffre<?php endif; ?>
                            <?php if (!empty($politique['speciaux'])): ?>· caractère spécial<?php endif; ?>.
                        </p>
                    </div>
                    <div class="field">
                        <label class="form-label" for="confirmation_mot_de_passe">Confirmation</label>
                        <input class="input" type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe"
                               autocomplete="new-password" required minlength="<?= (int)$politique['longueur'] ?>">
                    </div>
                </div>
            </section>

            <div class="form-footer">
                <div class="footer-actions">
                    <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- AE-EOF : le fichier account/password.php doit se terminer exactement ici -->