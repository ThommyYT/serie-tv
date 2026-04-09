import JQ from 'jquery'; // Usa un underscore per l'import locale
import * as _bootstrap from 'bootstrap'; // Importa tutto il modulo bootstrap
import _Hls from "hls.js";
import "@popperjs/core";
import "jquery-mousewheel";

declare global {
    var Hls: typeof _Hls;
    var loadingElement: JQuery<HTMLElement>;
    var errorElement: JQuery<HTMLElement>;
    var siteLogicHTML: (main: JQuery<HTMLElement>, response: ApiResponseSite, type: string) => Promise<void>;
    var onYouTubeIframeAPIReady: () => void;
    var alertMessage: (message: string, type: string, dismissible?: boolean, duration?: number) => void;
    var confirmMessage: (message: string, type: string, onConfirm: () => void) => Promise<void>;
    var resetHeaderNav: () => void;

    interface Window {
        $: typeof JQ;
        jQuery: typeof JQ;
        bootstrap: typeof _bootstrap;
        Hls: typeof _Hls;
        loadingElement: JQuery<HTMLElement>;
        errorElement: JQuery<HTMLElement>;
        siteLogicHTML: (main: JQuery<HTMLElement>, response: ApiResponseSite, type: string) => Promise<void>;
        onYouTubeIframeAPIReady: () => void;
        alertMessage: (message: string, type: string, dismissible?: boolean, duration?: number) => void;
        confirmMessage: (message: string, type: string, onConfirm: () => void) => Promise<void>;
        resetHeaderNav: () => void;
    }

    interface ApiResponse {
        status: string;
        message?: string;
    }

    interface ApiResponseUser extends ApiResponse {
        user_id: number;
        full_name: string;
        email: string;
        email_verified: number;
        role: string;
        last_login: string;
        created_at: string;
        updated_at: string;
    }

    interface ApiResponseLogin extends ApiResponse {
        user_id: number;
        full_name: string;
        email_verified: number;
    }

    interface ApiResponseSerie extends ApiResponse {
        titolo: string;
        immagine: string;
        stagioni: Stagione[];
        id: string;
    }

    interface ApiResponseVideo extends ApiResponse {
        video_src: string;
    }

    interface ApiResponseCaptcha extends ApiResponse {
        captcha_src: string;
    }

    interface ApiResponseSite extends ApiResponse {
        sectionTitle: string;
        dataCards: Card[];
        dataNavigation: Navigation[];
    }

    interface ApiResponseUpdate extends ApiResponse {
        dataUpdates: GiornoData[];
    }

    interface ApiResponseFavorites extends ApiResponse {
        posts: Post[];
    }

    interface Post {
        post_id: string;
        post_title: string;
    }

    interface Card {
        id: string;
        titolo: string;
        url: string;
        img: string;
        favourite: boolean;
    }

    interface Navigation {
        text: string;
        pageNum: number;
        isActive: boolean;
        isPrev: boolean;
        isNext: boolean;
        isDots: boolean;
    }

    interface Serie {
        titolo: string;
        episodio: string;
        url: string;
        extra: string;
    }

    interface Episodio {
        id: string;
        titolo: string;
        links: Link[];
    }

    interface Link {
        host: string;
        url: string;
    }

    interface Stagione {
        nome: string;
        episodi: Episodio[];
    }

    interface GiornoData {
        giorno: string;
        serie: Serie[];
    }

    interface User {
        id: number;
        full_name: string;
        email: string;
        email_verified: number;
        expires?: number;
    }
}

window.bootstrap = _bootstrap;
window.Hls = _Hls;
window.$ = JQ;
window.jQuery = JQ;

var wrapperLoading = $('<div>')
    .addClass('container wrapper d-flex justify-content-center align-items-center fs-4');
var loadingElement = $('<div>').addClass('spinner-border text-info');
var textLoading = $('<p>').addClass('ms-2 mt-3').text('Caricamento...');
wrapperLoading.append(loadingElement)
wrapperLoading.append(textLoading);

window.loadingElement = wrapperLoading;
window.siteLogicHTML = async (main: JQuery<HTMLElement>, response: ApiResponseSite, type: string) => {
    if (response.status === 'success') {
        const templateWrapper: string = await $.get('./html/template/wrapper-cards.html');
        const $wrapper = $(templateWrapper).first().clone();
        $wrapper.addClass(`${type}-wrapper`);
        $wrapper.find('#title').text(response.sectionTitle);

        if (response.dataCards.length > 0) {
            const cards = $wrapper.find('#card');
            const templateCard: string = await $.get('./html/template/card.html');
            let $card: JQuery<HTMLElement>;

            response.dataCards.forEach(card => {
                $card = $(templateCard).first().clone();
                $card.attr('title', card.titolo);
                $card.on({
                    mouseenter: function () {
                        $(this).find('button').stop(true).animate({ opacity: 1 }, 250);
                    },
                    mouseleave: function () {
                        $(this).find('button').stop(true).animate({ opacity: 0 }, 250);
                    }
                });
                $card.find('#img').attr('src', card.img);
                $card.find('#title-serie').text(card.titolo);
                $card.find('#link-serie').attr('data-url', card.url);
                $card.find('#btn-add-list').css('opacity', 0);
                $card.find('#btn-add-list').attr('data-id', card.id);
                $card.find('#btn-add-list').attr('data-title', card.titolo);
                $card.find('#btn-add-list').on({
                    mouseenter: function () {
                        // Se è già selezionato (classe 'active'), non fare nulla
                        if ($(this).hasClass('active')) {
                            $(this).addClass('text-secondary').removeClass('text-danger');
                            $(this).find('i').addClass('bi-heart').removeClass('bi-heart-fill');
                            return;
                        }

                        $(this).addClass('text-danger').removeClass('text-secondary');
                        $(this).find('i').addClass('bi-heart-fill').removeClass('bi-heart');
                    },
                    mouseleave: function () {
                        // Se è attivo, lascialo rosso e pieno
                        if ($(this).hasClass('active')) {
                            $(this).addClass('text-danger').removeClass('text-secondary');
                            $(this).find('i').addClass('bi-heart-fill').removeClass('bi-heart');
                            return;
                        }

                        $(this).removeClass('text-danger').addClass('text-secondary');
                        $(this).find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                    },
                    click: function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        // Toggle della classe 'active' per bloccare l'hover
                        $(this).toggleClass('active');

                        if ($(this).hasClass('active')) {
                            $(this).addClass('text-danger').removeClass('text-secondary');
                            $(this).find('i').addClass('bi-heart-fill').removeClass('bi-heart');
                            $(this).attr('title', 'Rimuovi dalla lista');
                            // Qui la tua logica AJAX per aggiungere alla lista
                            const data = { id: $(this).attr('data-id'), title: $(this).attr('data-title') };

                            $.post('php/addFavoriteList.php', { data: btoa(JSON.stringify(data)) }, () => {
                                if (response.status === 'success') {
                                    alertMessage('Aggiunto alla lista', 'success', true, 3000);
                                } else if (response.status === 'error') {
                                    alertMessage('Errore nell\'aggiunta alla lista', 'danger', true, 3000);
                                }
                            }, 'json').fail(() => {
                                alertMessage('Errore nell\'aggiunta alla lista', 'danger', true, 3000);
                            });
                        } else {
                            $(this).removeClass('text-danger').addClass('text-secondary');
                            $(this).find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                            $(this).attr('title', 'Aggiungi alla lista');
                            // Qui la tua logica AJAX per rimuovere dalla lista
                            const data = { id: $(this).attr('data-id') };

                            $.post('php/removeFavoriteList.php', { data: btoa(JSON.stringify(data)) }, (response: ApiResponse) => {
                                if (response.status === 'success') {
                                    alertMessage('Rimosso dalla lista', 'success', true, 3000);
                                } else if (response.status === 'error') {
                                    alertMessage('Errore nel rimozione dalla lista', 'danger', true, 3000);
                                }
                            }, 'json').fail(() => {
                                alertMessage('Errore nel rimozione dalla lista', 'danger', true, 3000);
                            });
                        }
                    }
                });

                if (card.favourite) {
                    $card.find('#btn-add-list').addClass('active');
                    $card.find('#btn-add-list').addClass('text-danger');
                    $card.find('#btn-add-list').find('i').addClass('bi-heart-fill');
                    $card.find('#btn-add-list').find('i').removeClass('bi-heart');
                    $card.find('#btn-add-list').attr('title', 'Rimuovi dalla lista');
                }

                $card.find('#link-serie').removeAttr('id');
                $card.find('#img').removeAttr('id');
                $card.find('#title-serie').removeAttr('id');
                $card.find('#btn-add-list').removeAttr('id');
                cards.append($card);
            });

            $wrapper.find('#card').replaceWith(cards);
        } else $wrapper.find('#card').remove();

        if (response.dataNavigation.length > 0) {
            const navigation = $wrapper.find('#navigation').find('ul');
            const templateNavigationItem: string = await $.get('./html/template/navigation.html');
            let $navigationItem: JQuery<HTMLElement>;

            response.dataNavigation.forEach(nav => {
                $navigationItem = $(templateNavigationItem).first().clone();
                const $link = $navigationItem.find('.js-nav-link');
                const $label = $navigationItem.find('.js-nav-label');

                if (nav.isDots) {
                    $link.remove();
                    $label.text('...');
                    $navigationItem.addClass('dots');
                    $navigationItem.attr('aria-disabled', 'true');
                } else {
                    if (nav.isActive) {
                        $navigationItem.addClass('active');
                        $link.remove();
                        $label.text(nav.text);
                        $label.attr('aria-current', 'page');
                    } else {
                        $label.remove();
                        $link.text(nav.text);
                        let pageNum = nav.pageNum;
                        if (nav.isPrev) {
                            $navigationItem.addClass('position-absolute start-0');
                        } else if (nav.isNext) {
                            pageNum = parseInt(navigation.find('.active').first().text()) + pageNum;
                            $navigationItem.addClass('position-absolute end-0');
                        }
                        $link.attr('data-page', pageNum);
                        $link.attr('aria-label', nav.isPrev ? 'Pagina precedente' : nav.isNext ? 'Pagina successiva' : `Vai a pagina ${nav.text}`);
                    }
                }

                navigation.append($navigationItem);
            });

            $wrapper.find('#navigation').find('ul').replaceWith(navigation);
        } else $wrapper.find('#navigation').remove();

        main.append($wrapper);
    }
};

window.alertMessage = (message: string, type: string, dismissible: boolean = true, duration?: number) => {
    var buttonClose = $();
    if (dismissible) {
        buttonClose = $('<button>').
            addClass('btn-close').
            attr('data-bs-dismiss', 'alert').
            attr('aria-label', 'Close');
    }

    var icon = $('<i>');

    if (type === 'success') {
        icon.addClass('bi bi-hand-thumbs-up-fill');
    } else if (type === 'warning') {
        icon.addClass('bi bi-exclamation-triangle-fill');
    } else if (type === 'danger') {
        icon.addClass('bi bi-exclamation-octagon-fill');
    }


    var alert = $('<div>').
        addClass(`alert alert-${type} ${dismissible ? 'alert-dismissible' : ''} fade show position-absolute top-0 end-0 m-3`).
        attr('role', 'alert').
        css('z-index', '9999').
        append(icon).
        append(` ${message} `).
        append(dismissible ? buttonClose : '');

    if (duration) {
        setTimeout(() => {
            alert.fadeOut(200, function () {
                $(this).remove();
            });
        }, duration);
    }

    // console.log(alert);

    $('body').append(alert);
};

window.confirmMessage = async (message: string, type: string, onConfirm: () => void) => {
    var templateModal: string = await $.get('./html/template/confirm-message.html');

    var modal = $(templateModal).first().clone();
    var $modal = new bootstrap.Modal(modal[0]);
    modal.find('#confirmMessage').text(message);
    modal.find('#confirmHeader').addClass(`bg-${type}`);

    let iconClass = "bi-question-circle-fill";
    if (type === 'danger') iconClass = "bi-exclamation-triangle-fill";
    if (type === 'warning') iconClass = "bi-exclamation-circle-fill";
    modal.find('#confirmIcon').addClass(iconClass);
    modal.find('#confirmButton').addClass(`btn-${type}`);

    modal.find('#confirmButton').on('click', () => {
        $modal.hide();
        onConfirm();
    });

    modal.on('hidden.bs.modal', () => {
        modal.remove();
    });

    $('body').append(modal);
    $modal.show();

};

window.resetHeaderNav = () => {
    $('header').find('ul.nav').first().find('a').removeClass('fw-bold');
}

var wrapperError = $('<div>')
    .addClass('d-flex flex-column align-items-center justify-content-center h-100 w-100');

var wrapperErrorIcon = $('<i>')
    .addClass('bi bi-exclamation-circle-fill')
    .css('font-size', '100px');

var wrapperErrorText = $('<h1>')
    .addClass('fw-bold')
    .css('font-size', '40px')
    .text('Ops! Qualcosa è andato storto!');

var wrapperErrorInfo = $('<p>')
    .addClass('fw-bold')
    .css('font-size', '20px')
    .text('Riprova più tardi.');

wrapperError.append(wrapperErrorIcon);
wrapperError.append(wrapperErrorText);
wrapperError.append(wrapperErrorInfo);

window.errorElement = wrapperError;

export { };
console.log("🚀 Ambiente globale inizializzato correttamente e avvio di script");
