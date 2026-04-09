import { initSerieLogic } from "./siteSerie.js";

export const initUserLogic = async (response: ApiResponseUser | ApiResponseFavorites) => {
    const main = $('main');
    if (response.status === 'success') {
        if ((response as ApiResponseUser).full_name !== undefined) {
            response = response as ApiResponseUser;
            const templateAccount: string = await $.get('./html/template/account.html');
            const $account = $(templateAccount).first().clone();
            $account.find('#full_name').text(response.full_name).removeAttr('id');
            $account.find('#name').val(response.full_name);
            $account.find('#email').val(response.email);
            $account.find('#lastLoginDate').val(response.last_login);
            $account.find('#registrationDate').val(response.created_at);
            $account.find('#lastModifiedDate').val(response.updated_at);
            const $passwordInput = $account.find('#newPassword');

            // Elementi della lista requisiti
            const $reqLen = $account.find('#len').removeAttr('id');
            const $reqCap = $account.find('#cap').removeAttr('id');
            const $reqNum = $account.find('#num').removeAttr('id');

            const updateRow = ($el: JQuery, isValid: boolean) => {
                // Gestisce il testo del div
                $el.toggleClass('text-success', isValid).toggleClass('text-danger', !isValid);

                // Gestisce l'icona interna
                $el.find('i')
                    .toggleClass('bi-check-circle-fill', isValid)
                    .toggleClass('bi-x-circle-fill', !isValid);
            };

            // Funzione per aggiornare la UI dei requisiti
            const updateRequirements = (pass: string) => {
                const hasLength = pass.length >= 8;
                const hasCapital = /[A-Z]/.test(pass);
                const hasNumber = /[0-9]/.test(pass);

                updateRow($reqLen, hasLength);
                updateRow($reqCap, hasCapital);
                updateRow($reqNum, hasNumber);

                return hasLength && hasCapital && hasNumber;
            };

            $passwordInput.on('input', function () {
                if (!updateRequirements($(this).val() as string)) {
                    $passwordInput.addClass('is-invalid');
                } else {
                    $passwordInput.removeClass('is-invalid');
                    $passwordInput.addClass('is-valid');
                }
            });

            $account.find('#togglePasswordN').off('click').on('click', () => {
                const type = $account.find('#newPassword').attr('type') === 'password' ? 'text' : 'password';
                $account.find('#newPassword').attr('type', type);
                $account.find('#togglePasswordN').find('i').toggleClass('bi-eye-fill bi-eye-slash-fill');
            })

            if (response.email_verified === 1) {
                $account.find('#verifyEmailBtn').attr('disabled', 'true').find('i').
                    removeClass('text-danger').addClass('text-success').
                    removeClass('bi-x-circle-fill').addClass('bi-check-circle-fill')
                    .text(' Email verificata');
            } else {
                $account.find('#verifyEmailBtn').removeAttr('disabled').find('i').
                    removeClass('text-success').addClass('text-danger').
                    removeClass('bi-check-circle-fill').addClass('bi-x-circle-fill');
                $account.find('#verifyEmailBtn').on('click', function () {
                    const $btn = $(this);

                    $.post('./php/verify.php', {}, async (response: ApiResponse) => {
                        if (response.status === 'email_sent') {
                            $btn.attr('disabled', 'true').find('i').
                                removeClass('text-danger').addClass('text-warning').
                                removeClass('bi-x-circle-fill').addClass('bi-exclamation-triangle-fill');

                            alertMessage('Email di verifica inviata', 'warning', true, 2000);
                            var templateModal: string = await $.get('./html/template/verifyEmail.html');
                            var $modal = $(templateModal).first().clone();
                            $('body').append($modal);
                            const modal = new bootstrap.Modal($modal[0]!);
                            modal.show();
                            $modal.find('#verifyEmailModalBtn').on('click', function () {
                                var codice = $modal.find('#recipient-name').val() as string;
                                $.post('./php/verify.php?token=' + response.message, { codice: codice }, async (response: ApiResponse) => {
                                    if (response.status === 'success') {
                                        $btn.attr('disabled', 'true').find('i').
                                            removeClass('text-warning').addClass('text-success').
                                            removeClass('bi-exclamation-triangle-fill').addClass('bi-check-circle-fill')
                                            .text(' Email verificata');
                                        alertMessage('Email verificata', 'success', true, 2000);
                                        modal.hide();
                                        $modal.remove();
                                    }
                                }, 'json').fail(() => {
                                    console.error("Errore nel caricamento dati da: ./php/verify.php");
                                });
                            });
                        }
                    }, 'json').fail(() => {
                        console.error("Errore nel caricamento dati da: ./php/verify.php");
                    });
                });
            }
            $account.find('#verifyEmailBtn').removeAttr('id');
            // $account.find('#newPassword').on
            main.append($account);
        } else if ((response as ApiResponseFavorites).posts !== undefined) {
            response = response as ApiResponseFavorites;
            // console.log(response.posts);
            var div = $('<div>');
            div.addClass('container wrapper list-group list-group-flush');

            response.posts.forEach(post => {
                // Creiamo il link
                const $link = $('<a>', {
                    href: 'javascript:void(0)',
                    class: 'list-group-item list-group-item-action bg-dark text-info border-secondary border-start-0 border-end-0 py-3 d-flex justify-content-between align-items-center',
                    text: post.post_title,
                    'data-id': post.post_id.split('-')[1], // Salviamo l'ID post-XXXXX
                });

                // Evento onClick
                $link.on('click', (e) => {
                    e.preventDefault();
                    const postId = $(e.currentTarget).attr('data-id');

                    // console.log("Caricamento post ID:", postId);

                    const main = $('main');

                    main.empty().append(loadingElement);
                    main.show();
                    resetHeaderNav();
                    $.post('./php/siteSerie.php', { postId: postId }, async (response: ApiResponseSerie) => {
                        main.fadeOut(600, async () => {
                            main.empty();
                            if (response.status === 'success') {
                                const templateWrapper: string = await $.get('./html/template/wrapper-serie.html');
                                const $wrapper = $(templateWrapper).first().clone();

                                $wrapper.find('#title').text(response.titolo).removeAttr('id');
                                $wrapper.find('#img').attr('src', response.immagine).removeAttr('id');

                                if (response.stagioni.length > 0) {
                                    const templateStagione: string = await $.get('./html/template/stagione.html');
                                    const templateEpisodio: string = await $.get('./html/template/episodio.html');
                                    const templateLink: string = await $.get('./html/template/serie-link.html');
                                    response.stagioni.forEach(async (st, idx) => {
                                        const $stagione = $(templateStagione).first().clone();
                                        $stagione.find('#title').text(st.nome)
                                            .removeAttr('id')
                                            .parent()
                                            .attr('data-bs-target', '#s-' + idx);

                                        const listaEpisodi = $stagione.find('#lista-episodi').find('ul');
                                        $stagione.find('#lista-episodi').attr('id', 's-' + idx);
                                        st.episodi.forEach(async ep => {
                                            var uniqueId = response.id + "--" + ep.id;
                                            const $episodio = $(templateEpisodio).first().clone();
                                            $episodio.find('#episode-title').text(ep.titolo).removeAttr('id');
                                            $episodio.find('#status').attr('data-status-id', uniqueId).removeAttr('id');

                                            ep.links.forEach(l => {
                                                const $link = $(templateLink).first().clone();
                                                $link.text(l.host);
                                                $link.attr('data-url', l.url);
                                                $link.attr('data-id', uniqueId);
                                                $link.attr('data-titolo-completo', response.titolo + ' - ' + ep.titolo);
                                                $episodio.find('#links').append($link);
                                            });

                                            $episodio.find('#links').removeAttr('id');
                                            listaEpisodi.append($episodio);
                                        });

                                        $wrapper.find('#accStagioni').append($stagione);
                                    });
                                } else {
                                    $wrapper.find('#accStagioni').remove();
                                    $wrapper.append('<div class="alert alert-danger">Nessun episodio trovato.</div>');
                                }

                                main.append($wrapper);

                                initSerieLogic();
                                main.fadeIn(400);
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            } else if (response.status === 'error') {
                                main.append('<div class="alert alert-danger text-center">' + response.message + '</div>');
                                main.fadeIn(400);
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            }
                        });
                    });

                    // Qui chiami la tua logica esistente per caricare i dati della serie
                    // Se la tua funzione richiede l'URL, dovrai prima recuperarlo o passarlo nel JSON
                    // loadSerieDetails(postId);
                });


                var arrow = $('<i class="bi bi-chevron-right text-muted small"></i>');
                $link.append(arrow);

                // Aggiungiamo il link alla lista
                div.append($link);




                // const templatePost: string = await $.get('./html/template/post.html');
                // const $post = $(templatePost).first().clone();
                // $post.find('#post_id').text(post.post_id).removeAttr('id');
                // $post.find('#post_title').text(post.post_title).removeAttr('id');
                // $post.find('#post_content').text(post.post_content).removeAttr('id');
                // $post.find('#post_author').text(post.post_author).removeAttr('id');
                // $post.find('#post_date').text(post.post_date).removeAttr('id');
            });

            main.append(div);
        }
    }
}