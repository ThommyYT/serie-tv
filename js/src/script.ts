import { initUpdateLogic } from './siteUpdate.js';
import { initHomeLogic } from './siteHome.js';
import { initArchiveLogic } from './siteArchive.js';
import { initSearchLogic } from './siteSearch.js';
import { initHeroVideo } from './hero.js';
import { initUserLogic } from './user.js';

// Variabili jQuery globali (gestite via libs.ts / globals.d.ts)
// let topButton: JQuery<HTMLElement>;
let storage_user: User | null = null;
let searchItems: string[] = [];

$(() => {
    // 1. Inizializzazione Ambiente PHP
    $('#init').load('./php/init.php', async () => {
        await checkUserSession();
        await initUserActions();
        await initCommonUI();
    });
});

/**
 * Gestisce il caricamento dei dati e delega il rendering al callback
 */
const loadPage = <T extends ApiResponse>(phpPath: string, renderCallback: (data: T) => void, postData: object = {}) => {
    const hero = $("#hero");
    const main = $("main");

    const performLoad = () => {
        // Usiamo $.post per ricevere JSON
        main.empty().append(loadingElement);
        main.show();
        $.post(phpPath, postData, (response: T) => {
            main.fadeOut(600, () => {
                main.empty();
                // Chiamiamo la logica di inizializzazione/rendering passando i dati
                renderCallback(response);

                // Mostriamo i risultati
                main.fadeIn(400);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }, 'json').fail(() => {
            // Mostriamo il messaggio di errore
            main.empty().append(errorElement);
            console.error("Errore nel caricamento dati da: " + phpPath);
        });
    };

    if (hero.is(':visible')) {
        hero.fadeOut(600, performLoad);
    } else {
        main.fadeOut(600, performLoad);
    }
};

/**
 * Inizializza i click della barra di navigazione
 */
const initNavigation = async () => {
    const resetNavLinks = (target: JQuery.TriggeredEvent) => {
        $(target.currentTarget).parents('ul.nav').first().find('a').removeClass('fw-bold');
        $(target.currentTarget).addClass('fw-bold');
    };

    $('#btn-home').on('click', (e) => {
        e.preventDefault();
        resetNavLinks(e);
        loadPage('./php/siteHome.php', initHomeLogic);
    });

    $('#btn-aggiornamento').on('click', (e) => {
        e.preventDefault();
        resetNavLinks(e);
        loadPage('./php/siteUpdate.php', initUpdateLogic);
    });

    $('#btn-archivio').on('click', (e) => {
        e.preventDefault();
        resetNavLinks(e);
        loadPage('./php/siteArchive.php', initArchiveLogic);
    });

    $('#search-form').on('submit', (e) => {
        e.preventDefault();
        const query = $('#search-input').val() as string;
        // console.log(window.innerWidth);
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            if (!$('#search-form').hasClass('search-expanded')) {
                $('#search-form').addClass('search-expanded');
                $('#search-input').trigger('focus'); // Apre la tastiera del telefono
                return;
            } else if (!query.trim()) {
                $('#search-form').removeClass('search-expanded');
                return;
            }
        }

        if (query.trim()) {
            if (isMobile) {
                $('#search-form').removeClass('search-expanded');
                $('#search-form').trigger('blur');
            }
            loadPage('./php/siteSearch.php', initSearchLogic, { s: query });
        }
    });

    $(document).on('click', (e: JQuery.ClickEvent) => {
        if (window.innerWidth <= 768 && $('#search-form').hasClass('search-expanded')) {
            if (!$(e.target).closest('#search-form').length) {
                $('#search-form').removeClass('search-expanded');
            }
        }
    });

    await $.getJSON('./php/getSearchItems.php', (response: string[]) => {
        searchItems = response;
        // console.log(searchItems);
        updateSuggestions(searchItems);
    });
};

/**
 * Gestione Login / Registrazione / Sessione
 */
const initUserActions = async () => {
    $("header").load("./html/header.html", () => {
        // 1. Applica subito lo stato corretto (Nascosto/Visibile)
        updateUserUI();

        // 2. Inizializza i click degli account (che ora esistono nel DOM)
        $('#navAccount').on("click", "a", function (e) {
            e.preventDefault();

            const target = $(this).data('page');

            if (!target) return;
            else if (target === 'logout') {
                confirmMessage('Sei sicuro di voler uscire?', 'warning', () => {
                    $.get('./php/logout.php', () => {
                        sessionStorage.removeItem('user');
                        localStorage.removeItem('user');
                        updateUserUI();
                        alertMessage('Logout effettuato con successo', 'success', true, 2000);
                    });
                });
                return;
            }

            loadPage(`./php/${target}.php`, initUserLogic);
        });

        // 3. Inizializza i tasti Modali
        $('#buttonRegister').on('click', () => {
            const modal = $('#modalRegister');
            modal.length === 0 ? register() : bootstrap.Modal.getOrCreateInstance(modal[0]).show();
        });

        $('#buttonLogin').on('click', () => {
            const modal = $('#modalLogin');
            modal.length === 0 ? login() : bootstrap.Modal.getOrCreateInstance(modal[0]).show();
        });

        initNavigation();


    });
};

/**
 * UI Comune: Top Button, Hero, Footer
 */
const initCommonUI = async () => {
    // Hero
    $("#hero").load("./html/hero.html", () => {
        initHeroVideo();
        $("#btn-explore").on("click", () => {
            $('#btn-home').addClass('fw-bold');
            loadPage('./php/siteHome.php', initHomeLogic);
        });
    });

    // Footer & Year
    $("footer").load("./html/footer.html", () => {
        const currentYear = new Date().getFullYear();
        $('#year').text(currentYear === 2026 ? "2026" : `2026 - ${currentYear}`);
    });

    // Scroll Behavior
    // $(window).on('scroll', () => {
    //     $(window).scrollTop()! > 20 ? topButton.css("visibility", "visible") : topButton.css("visibility", "hidden");
    // });

    // topButton.on('click', () => {
    //     window.scrollTo({ top: 0, behavior: 'smooth' });
    // });
};

const checkUserSession = async () => {
    const userStr = localStorage.getItem("user") ?? sessionStorage.getItem("user");
    storage_user = userStr ? JSON.parse(atob(userStr)) : null;
    if (userStr && storage_user) {
        try {
            // manda al server l'id dell'utente per verificare l'accesso
            $.post("./php/checkLogin.php", { id: storage_user.id }, (data: ApiResponse) => {
                if (data.status !== "success") {
                    localStorage.removeItem("user");
                    sessionStorage.removeItem("user");
                    updateUserUI();
                }
            }, 'json').fail(() => {
                localStorage.removeItem("user");
                sessionStorage.removeItem("user");
                updateUserUI();
            });

            if (storage_user.expires) {
                if (storage_user.expires > Date.now()) {
                    localStorage.setItem("user", userStr);
                    updateUserUI();
                }
            }
        } catch (e) { console.error("Session error"); }
    }
};

// Funzioni Modali (identiche ma pulite)
const register = () => {
    $("#RegisterLoginContainer").load("./html/register.html", () => {

        const modal = $('#modalRegister');
        const inst = new bootstrap.Modal(modal[0]);
        inst.show();

        const $passwordInput = $('#regPassword');
        const $form = $('#registerForm');

        // Elementi della lista requisiti
        const $reqLen = $('#len');
        const $reqCap = $('#cap');
        const $reqNum = $('#num');

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

        // Validazione "mentre scrivi"
        $passwordInput.on('input', function () {
            if (!updateRequirements($(this).val() as string)) {
                $passwordInput.addClass('is-invalid');
            } else {
                $passwordInput.removeClass('is-invalid');
                $passwordInput.addClass('is-valid');
            }
        });

        $form.off('submit').on('submit', function (e) {
            e.preventDefault();

            const name = ($('#regName').val() as string).trim();
            const email = ($('#regEmail').val() as string).trim();
            const pass = ($passwordInput.val() as string);

            // --- VALIDAZIONE ---
            if (!updateRequirements(pass)) {
                return;
            }

            var postData = btoa(JSON.stringify({
                name: name,
                email: email,
                password: pass
            }));

            $.post(
                "./php/register.php",
                { data: postData },
                function (res: ApiResponse) {
                    if (res.status === 'success') {
                        inst.hide();
                        updateUserUI();
                        // Se login() è una funzione globale o definita altrove
                        login();
                    } else {
                        alertMessage(res.message || "Errore registrazione", "danger", false, 5000);
                    }
                },
                "json"
            ).fail(function () {
                alertMessage("Errore server alla registrazione", "danger", false, 5000);
            });
        });

        $('#redirectL').off('click').on('click', () => {
            inst.hide();
            const loginModal = $('#modalLogin');
            // @ts-ignore (se bootstrap non è tipizzato perfettamente)
            loginModal.length === 0 ? login() : bootstrap.Modal.getOrCreateInstance(loginModal[0]).show();
        });

        $('#togglePasswordR').off('click').on('click', () => {
            const type = $passwordInput.attr('type') === 'password' ? 'text' : 'password';
            $passwordInput.attr('type', type);
            $('#togglePasswordR').find('i').toggleClass('bi-eye-fill bi-eye-slash-fill');
        });
    });
};


const login = () => {
    $("#RegisterLoginContainer").load("./html/login.html", () => {

        const modal = $('#modalLogin');
        const inst = new bootstrap.Modal(modal[0]);
        inst.show();

        const $passwordInput = $('#loginPassword');
        const $form = $('#loginForm');

        // Elementi della lista requisiti
        const $reqLen = $('#len');
        const $reqCap = $('#cap');
        const $reqNum = $('#num');

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

        // Validazione "mentre scrivi"
        $passwordInput.on('input', function () {
            if (!updateRequirements($(this).val() as string)) {
                $passwordInput.addClass('is-invalid');
            } else {
                $passwordInput.removeClass('is-invalid');
                $passwordInput.addClass('is-valid');
            }
        });

        $form.off('submit').on('submit', function (e) {
            e.preventDefault();

            const email = ($('#loginEmail')?.val() as String).trim();
            const pass = ($passwordInput.val() as string);
            const remember = $('#rememberMe').is(':checked');

            // --- VALIDAZIONE ---
            if (!updateRequirements(pass)) {
                return;
            }

            var postData = btoa(JSON.stringify({ email: email, password: pass }));

            $.post(
                "/php/login.php",
                { data: postData },
                function (res: ApiResponseLogin) {
                    // res deve essere JSON
                    if (res.status === 'success') {
                        if (remember) {
                            localStorage.setItem(
                                "user",
                                btoa(JSON.stringify({
                                    id: res.user_id,
                                    name: res.full_name,
                                    email: email,
                                    email_verified: res.email_verified,
                                    expires: Date.now() + 3600 * 1000
                                }))
                            );
                        } else {
                            sessionStorage.setItem(
                                "user",
                                btoa(JSON.stringify({
                                    id: res.user_id,
                                    name: res.full_name,
                                    email_verified: res.email_verified
                                }))
                            );
                        }
                        // localStorage.setItem(
                        //     "user",
                        //     btoa(JSON.stringify({
                        //         id: res.user.id,
                        //         name: res.user.name,
                        //         expires: Date.now() + 3600 * 1000
                        //     }))
                        // );



                        inst.hide();
                        updateUserUI();

                    } else if (res.status === 'error') {
                        alertMessage(res.message || "Credenziali non valide", "danger", true, 2000);
                    }
                },
                "json"
            ).fail(function () {
                alertMessage("Errore server alla login", "danger", false, 5000);
            });

        });

        $('#redirectR').off('click').on('click', () => {
            inst.hide();
            const registerModal = $('#modalRegister');
            registerModal.length === 0 ? register() : bootstrap.Modal.getOrCreateInstance(registerModal[0]).show();
        });

        $('#togglePasswordL').off('click').on('click', () => {
            $('#loginPassword').attr('type', $('#loginPassword').attr('type') === 'password' ? 'text' : 'password');
            $('#togglePasswordL').find('i').toggleClass('bi-eye-fill bi-eye-slash-fill');
        });
    });
};

/**
 * Sincronizza l'interfaccia in base allo stato dell'utente nel localStorage
 */
const updateUserUI = () => {
    const userStr = localStorage.getItem("user") ?? sessionStorage.getItem("user");
    var user = userStr ? JSON.parse(atob(userStr)) : null;
    storage_user = user === storage_user ? storage_user : user;
    const userExists = storage_user !== null;
    const registerLoginGroup = $('#RegisterLogin'); // Il contenitore dei tasti Login/Register
    const dropdownUser = $('#navAccount'); // Il contenitore del dropdown dell'utente

    if (userExists) {
        registerLoginGroup.addClass('d-none').hide();
        dropdownUser.removeClass('d-none').show();
    } else {
        registerLoginGroup.removeClass('d-none').show();
        dropdownUser.addClass('d-none').hide();
    }
};

const updateSuggestions = (titles: string[]) => {
    const $list = $('#search-suggestions');
    $list.empty();
    titles.forEach(title => {
        $list.append($('<option>').val(title));
    });
}


