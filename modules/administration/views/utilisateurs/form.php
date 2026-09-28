<?php
// fichier : modules/administration/views/utilisateurs/form.php — v0.29 (sheet + téléphone + droits dépliables)
declare(strict_types=1);

use App\Core\Session;
use App\Core\View;

 $utilisateur  = $utilisateur ?? null;
 $roles        = $roles ?? [];
 $agences      = $agences ?? [];
 $permissions  = $permissions ?? [];
 $droitsPerso  = $droitsPerso ?? [];
 $politiqueMdp = $politiqueMdp ?? ['longueur' => 12, 'majuscules' => true, 'chiffres' => true, 'speciaux' => true];
 $erreurs      = form_errors();
 $estCreation  = $utilisateur === null;
 $peutTousDroit = tous_droit();

 $chemin = $estCreation
    ? '/administration/utilisateurs'
    : '/administration/utilisateurs/' . (int)$utilisateur['id'];

 $civilite = old('civilite', (string)($utilisateur['civilite'] ?? ''));
 $nom      = old('nom', (string)($utilisateur['nom'] ?? ''));
 $prenom   = old('prenom', (string)($utilisateur['prenom'] ?? ''));
 $login    = old('login', (string)($utilisateur['login'] ?? ''));
 $mail     = old('mail', (string)($utilisateur['mail'] ?? ''));
 $tel      = old('telephone', (string)($utilisateur['telephone'] ?? ''));
 $naissance= old('date_de_naissance', (string)($utilisateur['date_de_naissance'] ?? ''));
 $couleur  = old('couleur', (string)($utilisateur['couleur'] ?? '#475569'));
 $role     = old('role', (string)($utilisateur['role'] ?? 'employe'));
 $agence   = old('agence', (string)($utilisateur['agence'] ?? ''));
 $cocheEnseignant = old('est_un_enseignant', (string)($utilisateur['est_un_enseignant'] ?? '0')) === '1';
 $cocheActif      = old('actif', (string)($utilisateur['actif'] ?? '1')) === '1';
 $cocheTousDroit  = old('tous_droit', (string)($utilisateur['tous_droit'] ?? '0')) === '1';
 $photoActuelle   = (string)($utilisateur['photographie'] ?? '');

 $afficherSuppression = !$estCreation
    && can('utilisateurs.supprimer')
    && (int)($utilisateur['id'] ?? 0) !== (int)Session::get('user_id', 0)
    && !((int)($utilisateur['tous_droit'] ?? 0) === 1 && !$peutTousDroit);

/* Droits personnalisés : regroupés par catégorie (dropdowns — directive v0.28). */
 $droitsCategories = [];
foreach ($permissions as $permission) {
    $droitsCategories[(string)$permission['module']][] = $permission;
}
ksort($droitsCategories);
?>
<div class="page-form">
    <h1 class="page-title"><?= $estCreation ? 'Créer un utilisateur' : 'Modifier « ' . e($login) . ' »' ?></h1>
    <p class="page-subtitle">
        Prénom, téléphone et email obligatoires. L'identifiant est généré automatiquement
        à partir du prénom et du nom (modifiable). L'agence est la référence de rattachement.
    </p>
    <a class="link-back" href="<?= url('/administration/utilisateurs') ?>">← Retour à la liste</a>

    <?php if ($erreurs !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="error-list">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url($chemin) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="card form-sheet">

            <section class="sheet-section">
                <div class="sheet-title">Informations personnelles</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="civilite">Civilité</label>
                        <select class="input" id="civilite" name="civilite">
                            <option value="" <?= $civilite === '' ? 'selected' : '' ?>>— Non précisé —</option>
                            <option value="Monsieur" <?= $civilite === 'Monsieur' ? 'selected' : '' ?>>Monsieur</option>
                            <option value="Madame" <?= $civilite === 'Madame' ? 'selected' : '' ?>>Madame</option>
                        </select>
                        <?php if (form_error('civilite')): ?><p class="form-error"><?= e(form_error('civilite')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="nom">Nom <span class="req">*</span></label>
                        <input class="input" type="text" id="nom" name="nom" maxlength="80" required value="<?= e($nom) ?>">
                        <?php if (form_error('nom')): ?><p class="form-error"><?= e(form_error('nom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="prenom">Prénom <span class="req">*</span></label>
                        <input class="input" type="text" id="prenom" name="prenom" maxlength="80" required value="<?= e($prenom) ?>">
                        <?php if (form_error('prenom')): ?><p class="form-error"><?= e(form_error('prenom')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="date_de_naissance">Date de naissance</label>
                        <input class="input" type="date" id="date_de_naissance" name="date_de_naissance" value="<?= e($naissance) ?>">
                    </div>
                    <div class="field">
                        <label class="form-label" for="telephone">Téléphone <span class="req">*</span></label>
                        <?php
                        View::partial('partials/telephone', [
                            'nom'    => 'telephone',
                            'valeur' => $tel,
                            'id'     => 'telephone',
                        ]);
                        ?>
                        <?php if (form_error('telephone')): ?><p class="form-error"><?= e(form_error('telephone')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="mail">Email <span class="req">*</span></label>
                        <input class="input" type="email" id="mail" name="mail" maxlength="190" required value="<?= e($mail) ?>">
                        <?php if (form_error('mail')): ?><p class="form-error"><?= e(form_error('mail')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="couleur">Couleur (avatar, pastilles)</label>
                        <input class="input input-color" type="color" id="couleur" name="couleur" value="<?= e($couleur) ?>">
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Compte</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="login">Identifiant <span class="req">*</span></label>
                        <input class="input" type="text" id="login" name="login" maxlength="80"
                               value="<?= e($login) ?>" placeholder="Généré automatiquement (prénom.nom)">
                        <p class="form-text">Généré automatiquement à partir du prénom et du nom — ajustable.</p>
                        <?php if (form_error('login')): ?><p class="form-error"><?= e(form_error('login')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="form-label" for="mot_de_passe">Mot de passe <?= $estCreation ? '<span class="req">*</span>' : '' ?></label>
                        <div class="pw-field">
                            <input class="input" type="password" id="mot_de_passe" name="mot_de_passe"
                                   autocomplete="new-password" <?= $estCreation ? 'required' : '' ?>
                                   minlength="<?= (int)$politiqueMdp['longueur'] ?>"
                                   data-longueur="<?= (int)$politiqueMdp['longueur'] ?>">
                            <button type="button" class="pw-btn" data-toggle-password tabindex="-1" title="Afficher / masquer" aria-label="Afficher ou masquer le mot de passe">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <button type="button" class="pw-btn pw-btn-gen" data-generate-password tabindex="-1"
                                    data-length="<?= (int)$politiqueMdp['longueur'] ?>"
                                    data-upper="<?= !empty($politiqueMdp['majuscules']) ? '1' : '0' ?>"
                                    data-digits="<?= !empty($politiqueMdp['chiffres']) ? '1' : '0' ?>"
                                    data-special="<?= !empty($politiqueMdp['speciaux']) ? '1' : '0' ?>"
                                    title="Générer un mot de passe conforme" aria-label="Générer un mot de passe">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11A8 8 0 1 0 12.7 20a8 8 0 0 1 0-16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <p class="form-text">
                            <?= (int)$politiqueMdp['longueur'] ?> caractères minimum
                            <?php if (!empty($politiqueMdp['majuscules'])): ?>· majuscule<?php endif; ?>
                            <?php if (!empty($politiqueMdp['chiffres'])): ?>· chiffre<?php endif; ?>
                            <?php if (!empty($politiqueMdp['speciaux'])): ?>· caractère spécial<?php endif; ?>
                            — politique configurable dans les paramètres.
                            <?= $estCreation ? '' : 'Laisser vide pour conserver le mot de passe actuel.' ?>
                        </p>
                        <?php if (form_error('mot_de_passe')): ?><p class="form-error"><?= e(form_error('mot_de_passe')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field-full">
                        <label class="form-label" for="photographie">Photographie</label>
                        <?php if ($photoActuelle !== ''): ?>
                            <div class="photo-current">
                                <img class="avatar avatar-xl" src="<?= url('/fichiers/utilisateurs/' . rawurlencode($photoActuelle)) ?>" alt="Photographie actuelle">
                                <label class="check-item">
                                    <input type="checkbox" name="retirer_photographie" value="1">
                                    <span>Retirer la photographie</span>
                                </label>
                            </div>
                        <?php endif; ?>
                        <input class="input input-file" type="file" id="photographie" name="photographie" accept="image/jpeg,image/png,image/webp">
                        <p class="form-text">JPG, PNG ou WebP — 2 Mo maximum.</p>
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-title">Rattachement et rôle</div>
                <div class="form-grid">
                    <div class="field">
                        <label class="form-label" for="role">Rôle <span class="req">*</span></label>
                        <select class="input" id="role" name="role" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r['code']) ?>" <?= $role === $r['code'] ? 'selected' : '' ?>><?= e($r['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('role')): ?><p class="form-error"><?= e(form_error('role')) ?></p><?php endif; ?>
                        <p class="form-text">Les rôles personnalisés créés dans « Rôles et droits » apparaissent ici.</p>
                    </div>
                    <div class="field">
                        <label class="form-label" for="agence">Agence (référence de rattachement)</label>
                        <select class="input" id="agence" name="agence">
                            <?php if ($peutTousDroit): ?>
                                <option value="" <?= $agence === '' ? 'selected' : '' ?>>Sans agence</option>
                            <?php endif; ?>
                            <?php foreach ($agences as $a): ?>
                                <option value="<?= (int)$a['id'] ?>" <?= $agence === (string)$a['id'] ? 'selected' : '' ?>><?= e($a['agence_nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (form_error('agence')): ?><p class="form-error"><?= e(form_error('agence')) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="check-grid">
                    <label class="check-item">
                        <input type="checkbox" name="est_un_enseignant" value="1" <?= $cocheEnseignant ? 'checked' : '' ?>>
                        <span>Est un enseignant</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" name="actif" value="1" <?= $cocheActif ? 'checked' : '' ?>>
                        <span>Compte actif</span>
                    </label>
                    <?php if ($peutTousDroit): ?>
                        <label class="check-item">
                            <input type="checkbox" name="tous_droit" value="1" <?= $cocheTousDroit ? 'checked' : '' ?>>
                            <span>Tous droits (bypass permissions)</span>
                        </label>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($peutTousDroit && $permissions !== []): ?>
                <section class="sheet-section">
                    <div class="sheet-title">Droits personnalisés</div>
                    <p class="form-text" style="margin-top:0">
                        Dépliez une catégorie pour ajuster ses permissions.
                        « Hérité du rôle » suit la matrice Rôles et droits ;
                        « Autorisé » / « Refusé » force la permission pour ce compte uniquement.
                    </p>
                    <?php $i = 0; foreach ($droitsCategories as $categorie => $permsCat): $i++; ?>
                        <div class="acc-group<?= $i === 1 ? ' open' : '' ?>">
                            <button type="button" class="acc-toggle" data-acc-group aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>">
                                <span><?= e(ucfirst((string)$categorie)) ?> <small>(<?= count($permsCat) ?>)</small></span>
                                <svg class="nav-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <div class="acc-body table-overflow">
                                <table class="table">
                                    <tbody>
                                    <?php foreach ($permsCat as $permission): ?>
                                        <tr>
                                            <td><?= e($permission['libelle']) ?><small class="perm-code"><?= e($permission['code']) ?></small></td>
                                            <td class="cell-actions">
                                                <?php $courant = $droitsPerso[$permission['code']] ?? null; ?>
                                                <select class="input input-sm" name="droit[<?= e($permission['code']) ?>]" aria-label="<?= e($permission['libelle']) ?>">
                                                    <option value="" <?= $courant === null ? 'selected' : '' ?>>Hérité du rôle</option>
                                                    <option value="1" <?= $courant === true || $courant === '1' ? 'selected' : '' ?>>Autorisé</option>
                                                    <option value="0" <?= $courant === false || $courant === '0' ? 'selected' : '' ?>>Refusé</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <div class="form-footer">
                <div class="footer-actions">
                    <button class="btn btn-primary" type="submit"><?= $estCreation ? 'Créer l\'utilisateur' : 'Enregistrer' ?></button>
                    <a class="btn btn-ghost" href="<?= url('/administration/utilisateurs') ?>">Annuler</a>
                </div>
            </div>
        </div>
    </form>

    <?php /* Commentaires conversationnels (CDC §14). */ ?>
    <?php if (!$estCreation && isset($commentaires)): ?>
        <?php
        View::partial('partials/commentaires', [
            'objetType'          => $objetType ?? 'utilisateur',
            'objetId'            => $objetId ?? 0,
            'commentaires'       => $commentaires,
            'avecKm'             => $avecKm ?? false,
            'optionsPartenaires' => $optionsPartenaires ?? [],
        ]);
        ?>
    <?php endif; ?>

    <?php if ($afficherSuppression): ?>
        <form class="card form-stack" method="post" action="<?= url('/administration/utilisateurs/' . (int)$utilisateur['id'] . '/supprimer') ?>"
              data-confirm="Supprimer (archiver) le compte « <?= e($login) ?> » ?">
            <?= csrf_field() ?>
            <div class="card-body form-actions">
                <button class="btn btn-danger" type="submit">Supprimer (archiver) cet utilisateur</button>
                <span class="form-text">Suppression logique — le compte est retiré des listes, l'historique est conservé.</span>
            </div>
        </form>
    <?php endif; ?>
</div>
<!-- AE-EOF : le fichier utilisateurs/form.php doit se terminer exactement ici -->