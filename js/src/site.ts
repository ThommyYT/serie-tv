import { initSerieLogic } from './siteSerie.js';

export const initSiteLogic = (logic: Function) => {
    $(document).off('click', '.btn-serie').on('click', '.btn-serie', function (this: HTMLElement, _: JQuery.ClickEvent) {
        const url = $(this).data('url') as string;
        const main = $('main');
        if (!url) return;

        // main.fadeOut(600, () => {
        main.empty().append(loadingElement);
        main.show();
        resetHeaderNav();
        $.post('./php/siteSerie.php', { url: url }, async (response: ApiResponseSerie) => {
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

    });
    // });
    // NUOVA LOGICA: Gestione Paginazione Dinamica
    $(document).off('click', '.btn-pagination').on('click', '.btn-pagination', function (this: HTMLElement, _: JQuery.ClickEvent) {
        _.preventDefault();
        let page = $(this).data('page');

        if (page == '-1') {
            var pageNum = $('#navigation').find('.active .page-link').text();
            page = (parseInt(pageNum) - 1).toString();
        }

        if (page) {
            // Carica la nuova pagina nel main via AJAX
            $("main").fadeOut(300, () => {
                logic(page);
            });
        }
    });

    $(document).off('click', '.dots .page-link').on('click', '.dots .page-link', function (this: HTMLElement, _: JQuery.ClickEvent) {
        _.preventDefault();

        var formTemp = $('#navigation').find('form');
        if (formTemp.length > 0) {
            formTemp.remove();
            return;
        }

        const input = $('<input>')
            .addClass('bg-transparent border rounded text-center w-100')
            .attr('id', 'input-pagination')
            .attr('type', 'number')
            .attr('min', 1)
            .attr('max', parseInt($('#navigation li:not(.position-absolute)').last().find('.page-link').data('page')))
            .attr('value', parseInt($('#navigation .active .page-link').text()));

        const form = $('<form>')
            .css('width', $('#navigation > ul').width() + 'px')
            .addClass('pb-4 position-absolute start-50 top-0 translate-middle z-0')
            .attr('id', 'form-pagination')
            .on('submit', function (e) {
                e.preventDefault();
                $("main").fadeOut(300, () => {
                    logic(input.val() as string);
                });
            })
            .append(input);

        $('#navigation').append(form);
    });
};

/* 
<?php if (empty($data['stagioni'])): ?>
                <div class="alert alert-danger">Nessun episodio trovato.</div>
            <?php else: ?>
                <div class="accordion w-100" id="accStagioni">
                    <?php foreach ($data['stagioni'] as $idx => $st): ?>
                        <div class="accordion-item bg-dark border-secondary">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-dark text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#s-<?= $idx ?>">
                                    <span class="text-info fw-bold"><?= htmlspecialchars($st['nome']) ?></span>
                                </button>
                            </h2>
                            <div id="s-<?= $idx ?>" class="accordion-collapse collapse" data-bs-parent="#accStagioni">
                                <div class="accordion-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($st['episodi'] as $ep):
                                            $uniqueId = $idSerie . "--" . $ep['id']; // ID finale per TS
                                        ?>
                                            <li class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small><?= htmlspecialchars($ep['titolo']) ?></small>
                                                    <span class="status-badge ms-2" data-status-id="<?= $uniqueId ?>"></span>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <?php foreach ($ep['links'] as $l): ?>
                                                        <button class="btn btn-outline-info px-2 btn-carica-video"
                                                            data-url="<?= htmlspecialchars($l['url']) ?>"
                                                            data-id="<?= $uniqueId ?>"
                                                            data-titolo-completo="<?= htmlspecialchars($data['titolo'] . " - " . $ep['titolo']) ?>">
                                                            <?= htmlspecialchars($l['host']) ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

*/
