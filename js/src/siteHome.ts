import { initSiteLogic } from './site.js';

export const initHomeLogic = async (response: ApiResponseSite) => {
    const main = $("main");
    // if (response.status === 'success') {
    //     const templateWrapper: string = await $.get('./html/template/wrapper-cards.html');
    //     const $wrapper = $(templateWrapper).first().clone();
    //     $wrapper.addClass('home-wrapper');
    //     $wrapper.find('#title').text(response.sectionTitle);

    //     if (response.dataCards.length > 0) {
    //         const cards = $wrapper.find('#card');
    //         const templateCard: string = await $.get('./html/template/card.html');
    //         let $card: JQuery<HTMLElement>;

    //         response.dataCards.forEach(card => {
    //             $card = $(templateCard).first().clone();
    //             $card.find('#img').attr('src', card.img);
    //             $card.find('#title-serie').text(card.titolo);
    //             $card.find('#link-serie').attr('data-url', card.url);
    //             $card.find('#link-serie').removeAttr('id');
    //             $card.find('#img').removeAttr('id');
    //             $card.find('#title-serie').removeAttr('id');
    //             cards.append($card);
    //         });

    //         $wrapper.find('#card').replaceWith(cards);
    //     } else $wrapper.find('#card').remove();

    //     if (response.dataNavigation.length > 0) {
    //         const navigation = $wrapper.find('#navigation').find('ul');
    //         const templateNavigationItem: string = await $.get('./html/template/navigation.html');
    //         let $navigationItem: JQuery<HTMLElement>;

    //         response.dataNavigation.forEach(nav => {
    //             $navigationItem = $(templateNavigationItem).first().clone();
    //             var link = $navigationItem.find('#link');
    //             var activeDots = $navigationItem.find('#active-dots');

    //             if (nav.isDots) {
    //                 $navigationItem.find('#link').remove();
    //                 activeDots.text('...');
    //                 $navigationItem.addClass('disabled');
    //                 activeDots.removeAttr('id');
    //                 $navigationItem.find('#active-dots').replaceWith(activeDots);
    //             } else {
    //                 if (nav.isActive) {
    //                     $navigationItem.addClass('active');
    //                     $navigationItem.find('#link').remove();
    //                     activeDots.text(nav.text);
    //                     activeDots.removeAttr('id');
    //                     $navigationItem.find('#active-dots').replaceWith(activeDots);
    //                 } else {
    //                     $navigationItem.find('#active-dots').remove();
    //                     link.text(nav.text);
    //                     var pageNum = nav.pageNum;
    //                     if (nav.isPrev) {
    //                         pageNum = parseInt(navigation.find('.active').first().text()) + pageNum;
    //                         $navigationItem.addClass('position-absolute start-0 ms-3');
    //                     } else if (nav.isNext) {
    //                         pageNum = parseInt(navigation.find('.active').first().text()) + pageNum;
    //                         $navigationItem.addClass('position-absolute end-0 me-3');
    //                     }
    //                     link.attr('data-page', pageNum);
    //                     link.removeAttr('id');
    //                     $navigationItem.find('#link').replaceWith(link);
    //                 }
    //             }

    //             navigation.append($navigationItem);
    //         });

    //         $wrapper.find('#navigation').find('ul').replaceWith(navigation);
    //     } else $wrapper.find('#navigation').remove();

    //     main.append($wrapper);
    // }

    await siteLogicHTML(main, response, 'home');

    initSiteLogic((page: number) => {
        main.empty().append(loadingElement);
        main.show();
        $.post('./php/siteHome.php', { p: page }, (response: ApiResponseSite) => {
            main.fadeOut(600, () => {
                main.empty();
                // Chiamiamo la logica di inizializzazione/rendering passando i dati
                initHomeLogic(response);

                // Mostriamo i risultati
                main.fadeIn(400);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }, 'json').fail(() => {
            console.error("Errore nel caricamento dati da: ./php/siteHome.php");
        });
    });
};
