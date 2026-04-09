import { initSerieLogic } from './siteSerie.js';

export const initUpdateLogic = async (response: ApiResponseUpdate) => {
    const main = $("main");
    if (response.status !== 'success' || response.dataUpdates.length === 0) return;

    // Carichiamo i template necessari
    const [templateWrapper, templateAccordionItem, templateLink] = await Promise.all([
        $.get('./html/template/wrapper-buttons.html'), // Il contenitore dell'accordion
        $.get('./html/template/button.html'), // L'item dell'accordion (Header + Collapse Body)
        $.get('./html/template/link.html')  // Il singolo link della serie
    ]);

    const $wrapper = $(templateWrapper).first().clone();
    const $accordionContainer = $wrapper.find('#buttons'); // Assicurati che nel wrapper ci sia un id="buttons" o rinominalo
    $accordionContainer.addClass('accordion accordion-flush').attr('id', 'accordionUpdates');

    for (const [index, update] of response.dataUpdates.entries()) {
        const $item = $(templateAccordionItem).first().clone();
        const collapseId = `collapse-${index}`;
        const listId = `list-${index}`;

        // 1. Configurazione Header/Bottone Accordion
        const $btn = $item.find('.accordion-button');
        $btn.text(update.giorno)
            .attr('data-bs-target', `#${collapseId}`)
            .attr('aria-controls', collapseId);

        // 2. Configurazione corpo collapse
        const $collapse = $item.find('.accordion-collapse');
        $collapse.attr('id', collapseId)
            .attr('data-bs-parent', '#accordionUpdates');

        const $listContainer = $item.find('.list-group');
        $listContainer.attr('id', listId).empty();

        // 3. Popolamento dei link all'interno di questo specifico giorno
        for (const s of update.serie) {
            const $link = $(templateLink).first().clone();
            $link.find('#title').text(s.titolo).removeAttr('id');
            $link.find('#episode').text(s.episodio).removeAttr('id');
            $link.find('#extra').text(s.extra).removeAttr('id');

            // Logica click sul link (rimane quasi identica alla tua)
            $link.on('click', async (e: JQuery.ClickEvent) => {
                e.preventDefault();
                resetHeaderNav();
                main.empty().append(loadingElement).show();
                $.post('./php/siteSerie.php', { url: s.url }, async (response: ApiResponseSerie) => {
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

            $listContainer.append($link);
        }

        $accordionContainer.append($item);
    }

    main.empty().append($wrapper);
};


/* 

// $(document).off('click', '.btn-giorno').on('click', '.btn-giorno', function (this: HTMLElement, _: JQuery.ClickEvent) {
        //     const dataBridge = $('#php-data-bridge');
        //     if (!dataBridge) return;

        //     const dataset: GiornoData[] = JSON.parse(dataBridge.text() || '[]');
        //     const idx = $(this).data('index') as number;
        //     const item = dataset[idx];

        //     if (!item) return;

        //     const modal = $('#serieModal');
        //     const modalInst = bootstrap.Modal.getOrCreateInstance(modal[0]!);

        //     const list = $('#modal-list');

        //     modal.find('.modal-title').text(item.giorno);
        //     list.empty();

        //     item.serie.forEach((s) => {
        //         const $link = $(`
        //         <a href="javascript:void(0)" class="list-group-item list-group-item-action bg-dark text-white py-3 border-secondary">
        //             <div class="d-flex justify-content-between align-items-center">
        //                 <div>
        //                     <span class="text-info fw-bold">${s.titolo}</span>
        //                     <span class="mx-1 text-muted">–</span>
        //                     <span class="text-warning">${s.episodio}</span>
        //                     <small class="ms-2 text-muted italic">${s.extra}</small>
        //                 </div>
        //             </div>
        //         </a>
        //     `);

        //         $link.on('click', (e: JQuery.ClickEvent) => {
        //             e.preventDefault();
        //             modalInst.hide();
        //             $('main').fadeOut(600, () => {
        //                 $('main').load("./php/siteSerie.php", { url: s.url }, () => {
        //                     initSerieLogic();
        //                     $('main').fadeIn(400);
        //                     window.scrollTo(0, 0);
        //                 });
        //             });
        //         });

        //         list.append($link);
        //     });

        //     modalInst.show();
        // });

*/
/* 
<!-- UI Pulsanti -->
<div class="wrapper container">
    <div class="row g-2 justify-content-center">
        <?php foreach ($data as $index => $item): ?>
            <div class="col-auto">
                <button class="btn btn-outline-primary shadow-sm px-4 btn-giorno" data-index="<?php echo $index; ?>">
                    <?php echo htmlspecialchars($item['giorno']); ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal modal-sheet fade" id="serieModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-monospace text-info">Programmazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modal-list" class="list-group list-group-flush"></div>
            </div>
        </div>
    </div>
</div>

*/