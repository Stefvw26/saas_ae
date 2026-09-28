<?php
// fichier : modules/prospects/views/modales.php — modales partagées (v0.35)
// Traiter : confirmation + commentaire FACULTATIF.
// Archiver : confirmation + commentaire OBLIGATOIRE (stocké comme motif
// ET conservé dans la conversation).
// L'action du formulaire est injectée par app.js via data-modal-action.
?>
<div class="modal-overlay" id="modal-traiter">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-traiter-titre">
        <div class="modal-header">
            <span id="modal-traiter-titre">Traiter le prospect</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">Confirmer le traitement de ce prospect ? Un commentaire est facultatif.</p>
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="commentaire_traitement">Commentaire (facultatif)</label>
                    <textarea class="input" id="commentaire_traitement" name="commentaire_traitement" rows="3" maxlength="2000"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Confirmer le traitement</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-archiver">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-archiver-titre">
        <div class="modal-header">
            <span id="modal-archiver-titre">Archiver le prospect</span>
            <button type="button" class="icon-btn" data-modal-close aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-sub">L'archivage exige un commentaire — il sera conservé dans la conversation.</p>
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="form-label" for="motif_archivage">Commentaire <span class="req">*</span></label>
                    <textarea class="input" id="motif_archivage" name="motif_archivage" rows="3" maxlength="255" required></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-danger" type="submit">Confirmer l'archivage</button>
                    <button class="btn btn-ghost" type="button" data-modal-close>Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- AE-EOF : le fichier prospects/modales.php doit se terminer exactement ici -->