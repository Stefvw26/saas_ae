/* fichier : public/assets/js/app.js — CRM Auto-École, v0.41 */
jQuery(function ($) {

    /* ---- Sidebar mobile ---- */
    var $sidebar = $('#sidebar'),
        $overlay = $('#sidebarOverlay');

    function openSidebar()  { $sidebar.addClass('open'); $overlay.addClass('show'); }
    function closeSidebar() { $sidebar.removeClass('open'); $overlay.removeClass('show'); }

    $('#sidebarToggle').on('click', function () {
        $sidebar.hasClass('open') ? closeSidebar() : openSidebar();
    });
    $overlay.on('click', closeSidebar);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            var $ouvertes = $('.modal-overlay.open');
            if ($ouvertes.length) { $ouvertes.removeClass('open'); }
            else { closeSidebar(); }
        }
    });

    /* ---- Accordéons génériques ---- */
    $(document).on('click', '[data-acc-group]', function () {
        var $groupe = $(this).closest('.acc-group');
        var ouvrir = !$groupe.hasClass('open');
        $groupe.removeClass('open');
        $groupe.find('.acc-toggle').attr('aria-expanded', 'false');
        if (ouvrir) {
            $groupe.addClass('open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    /* ---- Navbar : catégories dépliables ---- */
    $(document).on('click', '[data-nav-group]', function () {
        var $section = $(this).closest('.nav-section');
        var ouvrir = !$section.hasClass('open');
        $('.sidebar-nav .nav-section.open').removeClass('open');
        $('.sidebar-nav .nav-group-toggle').attr('aria-expanded', 'false');
        if (ouvrir) {
            $section.addClass('open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    /* ---- Matrice des droits ---- */
    $(document).on('click', '[data-matrix-group]', function () {
        var $groupe = $(this).closest('.matrix-group');
        var ouvrir = !$groupe.hasClass('open');
        $('.matrix-group.open').removeClass('open');
        $('.matrix-toggle').attr('aria-expanded', 'false');
        if (ouvrir) {
            $groupe.addClass('open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    /* ---- Impression ---- */
    $(document).on('click', '[data-print]', function () {
        window.print();
    });

    /* ---- Champs conditionnels (générique) ---- */
    function appliquerConditions($form) {
        if ($form.length === 0) { return; }
        $form.find('[data-cond-champ]').each(function () {
            var $cible = $(this);
            var champ = String($cible.data('cond-champ') || '');
            if (champ === '') { return; }
            var $select = $form.find('select[name="' + champ + '"]');
            var $check  = $form.find('input[type="checkbox"][name="' + champ + '"]');
            var visible = true;
            if ($select.length) {
                var contient = String($cible.data('cond-contient') || '').toLowerCase();
                if (contient !== '') {
                    var texte = ($select.find('option:selected').text() || '').toLowerCase();
                    visible = texte.indexOf(contient) !== -1;
                }
            } else if ($check.length) {
                visible = $check.is(':checked');
            }
            $cible.toggle(visible);
        });
    }

    function appliquerConditionsSections($form) {
        if ($form.length === 0) { return; }
        $form.find('[data-cond-carte]').each(function () {
            var $section = $(this);
            var declencheurs = String($section.data('cond-carte') || '').split('|');
            var visible = false;
            $.each(declencheurs, function (i, champ) {
                if (champ === '') { return; }
                if ($form.find('input[type="checkbox"][name="' + champ + '"]').is(':checked')) {
                    visible = true;
                }
            });
            $section.toggle(visible);
        });
    }

    function rafraichirConditions($form) {
        appliquerConditions($form);
        appliquerConditionsSections($form);
    }

    $('form').each(function () { rafraichirConditions($(this)); });
    $(document).on('change', 'form select, form input[type="checkbox"]', function () {
        rafraichirConditions($(this).closest('form'));
    });

    /* ---- Auto-complétion d'adresse (CDC §34) ---- */
    (function () {
        var delai = null;

        function libellePrincipal(r) {
            var a = r.address || {};
            var parts = [];
            if (a.house_number) { parts.push(a.house_number); }
            if (a.road) { parts.push(a.road); }
            if (parts.length > 0) { return parts.join(' '); }
            return String(r.name || (r.display_name || '').split(',')[0] || '');
        }

        function libelleSecondaire(r) {
            var a = r.address || {};
            var ville = a.city || a.town || a.village || a.municipality || '';
            var parts = [];
            if (a.postcode) { parts.push(a.postcode); }
            if (ville) { parts.push(ville); }
            if (a.country) { parts.push(a.country); }
            return parts.join(' · ');
        }

        function remplir($input, r) {
            var $form = $input.closest('form');
            var champs;
            try { champs = $.parseJSON($input.attr('data-champs') || '{}'); } catch (e) { return; }
            var a = r.address || {};

            $.each(champs, function (cle, nom) {
                if (!nom) { return; }
                var $cible = $form.find('input[name="' + nom + '"]');
                if ($cible.length === 0) { return; }
                var valeur = '';
                if (cle === 'adresse') {
                    var parts = [];
                    if (a.house_number) { parts.push(a.house_number); }
                    if (a.road) { parts.push(a.road); }
                    valeur = parts.join(' ');
                } else if (cle === 'code_postal') { valeur = a.postcode || ''; }
                else if (cle === 'ville') { valeur = a.city || a.town || a.village || a.municipality || ''; }
                else if (cle === 'pays') { valeur = a.country || ''; }
                else if (cle === 'latitude') { valeur = String(r.lat || ''); }
                else if (cle === 'longitude') { valeur = String(r.lon || ''); }
                if (valeur !== '') { $cible.val(valeur).trigger('input'); }
            });
        }

        function rechercher($input) {
            var q = $.trim($input.val() || '');
            var $liste = $input.closest('.addr-autocomplete').find('.addr-results');
            if (q.length < 3) { $liste.removeClass('show').empty(); return; }

            $.getJSON('https://nominatim.openstreetmap.org/search', {
                format: 'jsonv2',
                q: q,
                addressdetails: 1,
                limit: 5,
                'accept-language': 'fr'
            }).done(function (resultats) {
                $liste.empty();
                if (!resultats || resultats.length === 0) {
                    $('<li>').addClass('addr-empty').text('Aucune adresse trouvée.').appendTo($liste);
                } else {
                    $.each(resultats, function (i, r) {
                        var sous = libelleSecondaire(r);
                        var $li = $('<li>').attr('role', 'option');
                        $li.append($('<strong>').text(libellePrincipal(r)));
                        if (sous !== '') { $li.append($('<small>').text(sous)); }
                        $li.on('mousedown', function (e) {
                            e.preventDefault();
                            remplir($input, r);
                            $input.val(libellePrincipal(r) + (sous !== '' ? ', ' + sous : ''));
                            $liste.removeClass('show').empty();
                        });
                        $li.appendTo($liste);
                    });
                }
                $liste.addClass('show');
            }).fail(function () {
                $liste.removeClass('show').empty();
            });
        }

        $(document).on('input', '.addr-search', function () {
            var $input = $(this);
            window.clearTimeout(delai);
            delai = window.setTimeout(function () { rechercher($input); }, 450);
        });

        $(document).on('blur', '.addr-search', function () {
            var $liste = $(this).closest('.addr-autocomplete').find('.addr-results');
            window.setTimeout(function () { $liste.removeClass('show'); }, 180);
        });

        $(document).on('keydown', '.addr-search', function (e) {
            if (e.key === 'Escape') {
                $(this).closest('.addr-autocomplete').find('.addr-results').removeClass('show');
            }
        });
    })();

    /* ---- Téléphone (CDC §35) ---- */
    $(document).on('change', 'select.phone-indicatif', function () {
        var $select = $(this);
        var flags;
        try { flags = $.parseJSON($select.attr('data-flags') || '{}'); } catch (e) { return; }
        var iso = String($select.find('option:selected').data('iso') || '');
        var $flag = $select.closest('.phone-field').find('.phone-flag');
        if (iso !== '' && flags[iso]) { $flag.attr('src', flags[iso]); }
    });

    $('form').on('submit', function () {
        $(this).find('[data-phone]').each(function () {
            var nom = String($(this).data('phone') || '');
            if (nom === '') { return; }
            var ind = $.trim(String($(this).find('select[name="' + nom + '_indicatif"]').val() || ''));
            var num = $.trim(String($(this).find('input[name="' + nom + '_numero"]').val() || ''));
            var complet = (ind !== '' ? '+' + ind.replace(/^\+/, '') + ' ' : '') + num;
            $(this).find('input[name="' + nom + '"]').val($.trim(complet));
        });
    });

    /* ---- Assistant prospects (parcours PRÉ-RENDU + conditions) ---- */
    (function () {
        var $wizard = $('#prospect-wizard');
        if ($wizard.length === 0) { return; }

        var etape = 1;
        var NB_ETAPES = 5;
        var $etapes = $wizard.find('[data-wizard-steps] li');
        var $panes = $wizard.find('[data-wizard-pane]');
        var $prev = $wizard.find('[data-wizard-prev]');
        var $next = $wizard.find('[data-wizard-next]');
        var $submit = $wizard.find('[data-wizard-submit]');
        var $resume = $wizard.find('[data-wizard-resume]');
        var $parcoursVide = $('[data-parcours-vide]');

        function typeChoisi() {
            return String($('input[name="type_permis_id"]:checked').val() || '');
        }

        function afficherParcours() {
            var t = typeChoisi();
            $('[data-parcours-type]').prop('hidden', true);
            if (t !== '') {
                $('[data-parcours-type="' + t + '"]').prop('hidden', false);
            }
            $parcoursVide.toggle(t === '');
            appliquerSi();
        }

        function valeurQuestion($bloc, qid) {
            var $els = $bloc.find('[name="parcours[' + qid + ']"]');
            if ($els.length === 0) { return ''; }
            if ($els.first().is('select')) {
                return String($els.first().val() || '');
            }
            return String($els.filter(':checked').val() || '');
        }

        function appliquerSi() {
            $('[data-parcours-type]').each(function () {
                if ($(this).prop('hidden')) { return; }
                var $bloc = $(this);
                $bloc.find('[data-si-cle]').each(function () {
                    var cle = String($(this).data('si-cle'));
                    var egal = String($(this).data('si-egal'));
                    var valeur = valeurQuestion($bloc, cle);
                    $(this).toggleClass('si-cache', valeur !== egal);
                });
            });
        }

        $(document).on('change', '[name^="parcours["]', appliquerSi);
        $('input[name="type_permis_id"]').on('change', afficherParcours);

        function libelles() {
            var domaines = {};
            $('#domaine_id option').each(function () { domaines[String($(this).val())] = $(this).text(); });
            var agences = {};
            $('#agence_id option').each(function () { agences[String($(this).val())] = $(this).text(); });
            var types = {};
            $('input[name="type_permis_id"]').each(function () {
                types[String($(this).val())] = $(this).closest('.permis-card').find('.permis-nom').text();
            });
            return { domaines: domaines, agences: agences, types: types };
        }

        function resumeParcours() {
            var lignes = [];
            var $bloc = $('[data-parcours-type="' + typeChoisi() + '"]');
            $bloc.find('.field').each(function () {
                if ($(this).hasClass('si-cache')) { return; }
                var label = $.trim($(this).children('.form-label').first().text());
                var valeur = '';
                var $radio = $(this).find('input[type="radio"]:checked');
                var $select = $(this).find('select');
                var $nombre = $(this).find('input[type="number"]');
                var $texte = $(this).find('input[type="text"]');
                if ($radio.length) {
                    valeur = $radio.val() === 'oui' ? 'Oui' : ($radio.val() === 'non' ? 'Non' : '');
                } else if ($select.length && $select.val()) {
                    valeur = $select.find('option:selected').text();
                } else if ($nombre.length && $nombre.val() !== '') {
                    valeur = $nombre.val() + ' h';
                } else if ($texte.length && $texte.val() !== '') {
                    valeur = $texte.val();
                }
                if (valeur !== '') { lignes.push([label, valeur]); }
            });
            return lignes;
        }

        function construireResume() {
            var l = libelles();
            var lignes = [
                ['Nom', $('#nom').val()],
                ['Prénom', $('#prenom').val()],
                ['Date de naissance', $('#date_naissance').val()],
                ['Email', $('#email').val()],
                ['Téléphone', $('input[name="telephone"]').val()],
                ['Adresse', [$('#adresse').val(), $('#code_postal').val(), $('#ville').val(), $('#pays').val()].filter(Boolean).join(' ')],
                ['Lieu de préférence', l.domaines[String($('#domaine_id').val() || '')] || ''],
                ['Agence de référence', l.agences[String($('#agence_id').val() || '')] || ''],
                ['Formation souhaitée', l.types[typeChoisi()] || ''],
                ['Provenance', 'Passant (automatique)']
            ];
            $.each(resumeParcours(), function (i, ligne) { lignes.push(ligne); });

            $resume.empty();
            $.each(lignes, function (i, ligne) {
                if (!ligne[1]) { return; }
                $('<div>').addClass('wizard-resume-row')
                    .append($('<dt>').text(ligne[0]))
                    .append($('<dd>').text(String(ligne[1])))
                    .appendTo($resume);
            });
        }

        function afficher() {
            $panes.prop('hidden', true);
            $wizard.find('[data-wizard-pane="' + etape + '"]').prop('hidden', false);
            $etapes.removeClass('is-active is-done');
            $etapes.each(function (i) {
                if (i + 1 < etape) { $(this).addClass('is-done'); }
            });
            $etapes.eq(etape - 1).addClass('is-active');
            $prev.prop('hidden', etape === 1);
            $next.prop('hidden', etape === NB_ETAPES);
            $submit.prop('hidden', etape !== NB_ETAPES);
            if (etape === 3) { afficherParcours(); }
            if (etape === NB_ETAPES) { construireResume(); }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        $next.on('click', function () { if (etape < NB_ETAPES) { etape++; afficher(); } });
        $prev.on('click', function () { if (etape > 1) { etape--; afficher(); } });
        $etapes.on('click', function () {
            var cible = parseInt($(this).data('step-cible'), 10) || 1;
            etape = Math.min(Math.max(cible, 1), NB_ETAPES);
            afficher();
        });

        var $premiereErreur = $wizard.find('.form-error').first();
        if ($premiereErreur.length) {
            var $pane = $premiereErreur.closest('[data-wizard-pane]');
            if ($pane.length) { etape = parseInt($pane.data('wizard-pane'), 10) || 1; }
        }

        afficherParcours();
        afficher();
    })();

    /* ---- GÉNÉRATEUR de parcours (v0.41 — AUCUN JS de condition :
       les règles « On affiche… si la réponse est égale à » sont rendues
       côté serveur ; il ne reste que options + prévisualisation.) ---- */
    (function () {
        /* Options : visibles si type « Choix (options) ». */
        function syncOptions($select) {
            var $form = $select.closest('form');
            var estChoix = String($select.val()) === 'choix';
            $form.find('.q-options-create').prop('hidden', !estChoix);
            $form.find('.q-options-manage').prop('hidden', !estChoix);
        }
        $(document).on('change', 'select[data-toggle-options]', function () { syncOptions($(this)); });
        $('select[data-toggle-options]').each(function () { syncOptions($(this)); });

        /* Lignes d'options dynamiques (création). */
        $(document).on('click', '[data-opt-add]', function () {
            var $lignes = $(this).closest('.q-options-create').find('.opt-lignes');
            $lignes.children('.opt-ligne').first().clone(true).appendTo($lignes).find('input').val('');
        });
        $(document).on('click', '[data-opt-del]', function () {
            var $lignes = $(this).closest('.opt-lignes');
            if ($lignes.children('.opt-ligne').length > 1) {
                $(this).closest('.opt-ligne').remove();
            }
        });

        /* Prévisualisation photo avant upload. */
        $(document).on('change', 'input.input-file[type="file"]', function () {
            var fichier = this.files && this.files[0];
            if (!fichier) { return; }
            var $champ = $(this).closest('.field');
            var $apercu = $champ.find('.upload-preview');
            if ($apercu.length === 0) {
                $apercu = $('<img>').addClass('avatar avatar-xl upload-preview')
                    .css({ marginTop: '8px', display: 'block' }).insertAfter($(this));
            }
            var lecteur = new FileReader();
            lecteur.onload = function (e) { $apercu.attr('src', String(e.target.result)); };
            lecteur.readAsDataURL(fichier);
        });
    })();

    /* ---- Modale « fournisseur » ---- */
    $(document).on('change', 'input[type="checkbox"][name="fournisseur"]', function () {
        var $form = $(this).closest('form');
        var $modale = $('#modal-fournisseur');
        if ($modale.length === 0) { return; }

        if (this.checked) {
            $modale.find('#modal_f_partenaire').val($form.find('select[name="fournisseur_id"]').val() || '');
            $modale.find('#modal_f_prix').val($form.find('input[name="prix_achete"]').val() || '');
            $modale.addClass('open');
        } else {
            $form.find('select[name="fournisseur_id"]').val('');
        }
    });

    $(document).on('click', '[data-fournisseur-ok]', function () {
        var $modale = $(this).closest('.modal-overlay');
        var $form = $('#referentiel-form');
        $form.find('select[name="fournisseur_id"]').val($modale.find('#modal_f_partenaire').val() || '');
        $form.find('input[name="prix_achete"]').val($modale.find('#modal_f_prix').val() || '');
        $modale.removeClass('open');
    });

    $(document).on('click', '[data-fournisseur-annuler]', function () {
        $(this).closest('.modal-overlay').removeClass('open');
        $('#referentiel-form input[type="checkbox"][name="fournisseur"]').prop('checked', false).trigger('change');
    });

    /* ---- Alertes ---- */
    $(document).on('click', '[data-dismiss]', function () {
        $(this).closest('.alert').remove();
    });
    window.setTimeout(function () {
        $('.alert-auto').fadeOut(350, function () { $(this).remove(); });
    }, 6000);

    /* ---- Soumission : confirmation + anti double-clic ---- */
    $('form').on('submit', function () {
        var $form = $(this);
        var message = $form.data('confirm');
        if (typeof message === 'string' && message !== '' && !window.confirm(message)) {
            return false;
        }
        var $btn = $form.find('button[type="submit"]');
        window.setTimeout(function () { $btn.prop('disabled', true); }, 0);
    });

    /* ---- Modales (générique — data-modal-action) ---- */
    $(document).on('click', '[data-modal]', function (e) {
        e.preventDefault();
        var $declencheur = $(this);
        var $modale = $($declencheur.data('modal'));
        if ($modale.length === 0) { return; }

        var $form = $modale.find('form');
        var action = $declencheur.data('modal-action');
        if (typeof action === 'string' && action !== '' && $form.length) {
            $form.attr('action', action);
            $form.removeAttr('data-action-base');
        }

        var nom = $declencheur.data('user-nom') || '';
        var login = $declencheur.data('user-login') || '';
        var cible = nom !== '' ? nom + (login !== '' ? ' (' + login + ')' : '') : login;
        $modale.find('[data-modal-cible]').text(cible);

        var base = $form.data('action-base');
        if (typeof base === 'string' && $declencheur.data('user-id') !== undefined) {
            $form.attr('action', base + $declencheur.data('user-id') + '/mot-de-passe');
        }
        $modale.addClass('open');
    });

    $(document).on('click', '[data-modal-close]', function () {
        $(this).closest('.modal-overlay').removeClass('open');
    });
    $(document).on('mousedown', '.modal-overlay', function (e) {
        if (e.target === this) { $(this).removeClass('open'); }
    });

    /* ---- Confirmation avant soumission (data-confirm="message") ---- */
    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (!window.confirm($(this).attr('data-confirm'))) {
            e.preventDefault();
        }
    });

    /* ---- Onglets ---- */
    $(document).on('click', '[data-tab]', function (e) {
        e.preventDefault();
        var cible = '#' + $(this).data('tab');
        var $conteneur = $(this).closest('.card-body, .card, body');
        $conteneur.find('[data-tab]').removeClass('active').attr('aria-selected', 'false');
        $(this).addClass('active').attr('aria-selected', 'true');
        $conteneur.find('.tab-panel').removeClass('active');
        $conteneur.find(cible).addClass('active');
    });

    /* ---- Mot de passe ---- */
    $(document).on('click', '[data-toggle-password]', function () {
        var $input = $(this).closest('.pw-field').find('input');
        $input.attr('type', $input.attr('type') === 'password' ? 'text' : 'password');
    });

    $(document).on('click', '[data-generate-password]', function () {
        var $btn = $(this),
            $input = $btn.closest('.pw-field').find('input'),
            len = Math.max(8, parseInt($btn.data('length'), 10) || 12),
            up = String($btn.data('upper')) === '1',
            di = String($btn.data('digits')) === '1',
            sp = String($btn.data('special')) === '1';

        var sets = {
            lower:  'abcdefghijkmnopqrstuvwxyz',
            upper:  'ABCDEFGHJKLMNPQRSTUVWXYZ',
            digits: '23456789',
            special:'!@#$%&*()-_=+?'
        };
        var garanties = [], pool = sets.lower;
        if (up) { garanties.push(sets.upper);   pool += sets.upper; }
        if (di) { garanties.push(sets.digits);  pool += sets.digits; }
        if (sp) { garanties.push(sets.special); pool += sets.special; }

        var chars = [];
        $.each(garanties, function (i, s) {
            chars.push(s.charAt(Math.floor(Math.random() * s.length)));
        });
        while (chars.length < len) {
            chars.push(pool.charAt(Math.floor(Math.random() * pool.length)));
        }
        for (var i = chars.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = chars[i]; chars[i] = chars[j]; chars[j] = t;
        }
        $input.val(chars.join('')).attr('type', 'text').trigger('input');
    });

    /* ---- Identifiant automatique ---- */
    function loginSlug(valeur) {
        var s = (valeur || '').toLowerCase();
        try { s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) { /* anciens navigateurs */ }
        return s.replace(/[^a-z0-9]/g, '');
    }

    var $login = $('#login'), $prenom = $('#prenom'), $nom = $('#nom');
    if ($login.length === 1 && $prenom.length === 1 && $nom.length === 1) {
        var loginAuto = function () {
            var v = loginSlug($prenom.val()) + '.' + loginSlug($nom.val());
            v = v.replace(/\.{2,}/g, '.').replace(/^\.+|\.+$/g, '').substring(0, 80);
            $login.val(v).data('auto', '1');
        };
        $prenom.add($nom).on('input change', function () {
            if ($login.data('auto') === '1' || $.trim($login.val()) === '') {
                loginAuto();
            }
        });
        $login.on('input', function () {
            if (document.activeElement === $login[0]) { $login.data('auto', '0'); }
        });
        if ($.trim($login.val()) === '') { loginAuto(); }
    }

    /* ---- Select avec images ---- */
    $(document).on('change', 'select[data-images]', function () {
        var $select = $(this), images;
        try { images = $.parseJSON($select.attr('data-images') || '{}'); } catch (e) { return; }
        var url = images[String($select.val() || '')];
        var $img = $('[data-preview-for="' + this.id + '"]');
        if ($img.length && url) {
            $img.attr('src', url);
        }
    });
    
      /* ---- Switches fiche élève (v0.46 : BA/BM, B1-B5 — AJAX) ---- */
       /* ---- Switches fiche élève (v0.49 : CSRF inclus — fix 500) ---- */
    $(document).on('click', '[data-switch-group] .sw', function () {
        var $btn   = $(this);
        var $group = $btn.closest('[data-switch-group]');
        if ($group.hasClass('is-busy')) { return; }
        $group.addClass('is-busy');

        var champ    = String($group.data('switch-group'));
        var urlCible = String($group.data('url') || '');
        var valeur   = String($btn.data('value') || '');
        var libelle  = String($btn.data('libelle') || valeur);
        var jeton    = $('meta[name="csrf-token"]').attr('content') || '';

        var donnees = (champ === 'boite' ? { type_boite: valeur } : { type_b: valeur });
        donnees._token = jeton;

        $.post(urlCible, donnees)
            .done(function (reponse) {
                if (!reponse || !reponse.ok) {
                    window.alert((reponse && reponse.erreur) ? reponse.erreur : 'Modification impossible.');
                    $group.removeClass('is-busy');
                    return;
                }
                $group.find('.sw').removeClass('on');
                $btn.addClass('on');
                $('[data-switch-libelle="' + champ + '"]').text(
                    valeur === '' ? (champ === 'boite' ? 'Non définie' : 'Non défini') : libelle
                );
            })
            .fail(function (xhr) {
                if (xhr.status === 419) {
                    window.alert('Session expirée — rechargez la page.');
                } else {
                    window.alert('Modification impossible (serveur).');
                }
            })
            .always(function () {
                $group.removeClass('is-busy');
            });
    });
    
          /* ---- v0.50 : Quantité panier/formule — MAJ automatique (change)
       + message utilisateur (pas de reload). CSRF via meta. ---- */
    function majQuantiteAuto($input) {
        var urlCible = String($input.data('url') || '');
        if (urlCible === '') { return; }
        var jeton = $('meta[name="csrf-token"]').attr('content') || '';
        var donnees = { quantite: parseInt($input.val(), 10) || 1, _token: jeton };

        $.post(urlCible, donnees)
            .done(function (rep) {
                if (!rep || !rep.ok) {
                    window.alert((rep && rep.erreur) ? rep.erreur : 'Modification impossible.');
                    return;
                }
                panierMessage(rep.message || 'Quantité mise à jour.', 'ok');
            })
            .fail(function (xhr) {
                if (xhr.status === 419) {
                    window.alert('Session expirée — rechargez la page.');
                } else {
                    window.alert('Modification impossible (serveur).');
                }
            });
    }

    /* Message discret en haut du panier (pas d'alert bloquante). */
    function panierMessage(texte, type) {
        var $zone = $('#panier-msg');
        if ($zone.length === 0) { return; }
        $zone
            .removeClass('panier-msg-ok panier-msg-ko')
            .addClass(type === 'ko' ? 'panier-msg-ko' : 'panier-msg-ok')
            .text(texte)
            .stop(true, true)
            .fadeIn(120)
            .delay(2600)
            .fadeOut(300);
    }
    window.panierMessage = panierMessage; /* utilisé aussi par le combo */

    /* Quantité : MAJ sur changement (input number du panier). */
    $(document).on('change', 'input.panier-quantite', function () {
        majQuantiteAuto($(this));
    });

    /* Offert / CPF : bascule directe (case du panier). */
    $(document).on('change', 'input.panier-check', function () {
        var $check = $(this);
        var jeton = $('meta[name="csrf-token"]').attr('content') || '';
        var donnees = { prestation_id: $check.data('prestation'), _token: jeton };
        donnees[$check.attr('name')] = $check.is(':checked') ? 1 : 0;

        $.post(String($check.data('url')), donnees)
            .done(function (rep) {
                if (!rep || !rep.ok) {
                    window.alert((rep && rep.erreur) ? rep.erreur : 'Modification impossible.');
                    return;
                }
                panierMessage(rep.message || 'Mis à jour.', 'ok');
            })
            .fail(function () { window.alert('Modification impossible (serveur).'); });
    });
    
});