import { initVideoLogic } from './siteVideo.js';

export const initSerieLogic = () => {
    updateStatusBadges();
    let id: string;

    $(document).off('click', '.btn-carica-video').on('click', '.btn-carica-video', function (this: HTMLElement, _: JQuery.ClickEvent) {
        const url = $(this).data('url') as string;
        id = $(this).data('id') as string;
        const titolo = $(this).data('titolo-completo') as string;

        if (!url) return;

        // 1. Mostra subito il Lock con un caricamento
        if ($('#watch-lock').length === 0)
            showWatchLock(titolo, id);
        else {
            closeLock();
            showWatchLock(titolo, id);
        }

        // 2. Chiamata AJAX a test.php per ottenere il Captcha
        $.post('./php/captchaResolver.php', { url: url }, async (response: ApiResponseCaptcha) => {
            if (response.status === 'success') {
                const container = $('#video-content-area');
                const templateForm: string = await $.get('./html/template/form-captcha.html');
                const $form = $(templateForm).first().clone();
                $form.find('#img_captcha').attr('src', response.captcha_src);
                $form.find('#img_captcha').removeAttr('id');

                $form.on('submit', function (e) {
                    e.preventDefault();
                    const formSerialize = $(this).serialize();
                    const container = $('#video-content-area');
                    const childrens = container.children();
                    childrens.last().remove();
                    childrens.first().removeClass('text-info').addClass('text-warning').show();
                    childrens.eq(1).text('Sblocco video...').show();

                    $.post('./php/captchaConfirm.php', formSerialize, async (response: ApiResponseVideo) => {
                        if (response.status === 'success') {
                            // const templateVideo: string = await $.get('./html/template/template-video.html');
                            // const $video = $(templateVideo).first().clone();
                            // $video.find('#video').attr('data-src', response.video_src);

                            // container.empty();
                            // container.append($video);
                            // setTimeout(() => checkVideo(container, id), 100);
                            successVideo(container, id, response);
                        } else if (response.status === 'error') {
                            container.html("<div class='alert alert-danger' role='alert'>" + response.message + "</div>");
                        }
                    }, 'json').fail(() => {
                        console.error("Errore nel caricamento dati da: ./php/captchaConfirm.php");
                    });
                });

                container.children().hide();
                container.append($form);
            } else if (response.status === 'redirect') {
                const container = $('#video-content-area');
                const childrens = container.children();
                childrens.first().removeClass('text-info').addClass('text-warning').show();
                childrens.last().text('Sblocco video...').show();

                $.get('./php/siteVideo.php', async (response: ApiResponseVideo) => {
                    if (response.status === 'success') {
                        // const templateVideo: string = await $.get('./html/template/template-video.html');
                        // const $video = $(templateVideo).first().clone();
                        // $video.find('#video').attr('data-src', response.video_src);

                        // container.empty();
                        // container.append($video);
                        // setTimeout(() => checkVideo(container, id), 100);
                        successVideo(container, id, response);
                    } else if (response.status === 'error') {
                        container.html("<div class='alert alert-danger' role='alert'>" + response.message + "</div>");
                    }
                }, 'json').fail(() => {
                    console.error("Errore nel caricamento dati da: ./php/siteVideo.php");
                });
            }
        }, 'json').fail(() => {
            console.error("Errore nel caricamento dati da: ./php/captchaResolver.php");
        });
    });
};

const showWatchLock = async (titolo: string, id: string) => {
    const lock: string = await $.get('./html/template/watch-lock.html');
    const $lock = $(lock).first().clone();

    $lock.find('#titolo').text(titolo).removeAttr('id');

    $('body').append($lock).css('overflow', 'hidden');

    $('#btn-visto').on('click', () => { localStorage.setItem(`status_${id}`, 'visto'); closeLock(); });
    $('#btn-corso').on('click', () => { localStorage.setItem(`status_${id}`, 'in_corso'); closeLock(); });
    $('#btn-annulla').on('click', () => { closeLock(); });
};


const updateStatusBadges = () => {
    $('.status-badge').each(function () {
        // Usiamo .attr('data-status-id') invece di .data() per evitare problemi con &nbsp;
        const id = $(this).attr('data-status-id') || "";
        const status = localStorage.getItem(`status_${id}`);
        const $badge = $(this);

        // Selettore sicuro per ID con spazi o caratteri speciali
        const safeId = id.replace(/"/g, '\\"');
        const $buttons = $(`.btn-carica-video[data-id="${safeId}"]`);

        $badge.removeClass('badge bg-success bg-warning bg-secondary text-dark');

        if (status === 'visto') {
            $badge.addClass('badge bg-success').text('Visto');
            $buttons.removeClass('btn-outline-info btn-outline-warning').addClass('btn-outline-success');
        } else if (status === 'in_corso') {
            $badge.addClass('badge bg-warning text-dark').text('In corso');
            $buttons.removeClass('btn-outline-info btn-outline-success').addClass('btn-outline-warning');
        } else {
            $badge.addClass('badge bg-secondary').text('Non iniziato');
            $buttons.removeClass('btn-outline-success btn-outline-warning').addClass('btn-outline-info');
        }
    });
};

const closeLock = () => {
    $('#watch-lock').remove();
    $('body').css('overflow', ''); // Ripristina lo scroll
    updateStatusBadges(); // Aggiorna i badge visivi
};

const checkVideo = (container: JQuery<HTMLElement>, id: string) => {
    if (container.has('video')) {
        initVideoLogic(id);
    }
}

const successVideo = async (container: JQuery<HTMLElement>, id: string, response: ApiResponseVideo) => {
    const templateVideo: string = await $.get('./html/template/template-video.html');
    // All'interno del successo di captchaConfirm.php o siteVideo.php:
    const $videoTemplate = $(templateVideo).first().clone();
    $videoTemplate.find('#video').attr('data-src', response.video_src);

    // Opzionale: Popola la lista episodi nell'overlay clonando i bottoni della pagina
    const $listaEpisodi = $('.btn-carica-video').clone();
    $videoTemplate.find('#episodes-list').append($listaEpisodi);

    // console.log($videoTemplate);

    container.empty().append($videoTemplate);
    setTimeout(() => checkVideo(container, id), 100);
}
