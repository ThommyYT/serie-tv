// import Hls from 'hls.js';


/**
 * Inizializza la logica del player video.
 * @param {string} id - ID univoco dell'episodio.
 * @returns {void}
 */
export const initVideoLogic = (id: string): void => {
    // --- 1. SELETTORI E VARIABILI DI STATO ---
    const $video = $('#video') as JQuery<HTMLVideoElement>;
    const $seekBar = $('#seek-bar') as JQuery<HTMLInputElement>;
    const $volumeBar = $('#volume-bar') as JQuery<HTMLInputElement>;
    const $playBtn = $('#play-pause') as JQuery<HTMLButtonElement>;
    const $fullScreenBtn = $('#full-screen') as JQuery<HTMLButtonElement>;
    const $muteBtn = $('#mute-btn') as JQuery<HTMLButtonElement>;
    const $container = $('#video-container') as JQuery<HTMLDivElement>;
    const $controls = $('#video-controls') as JQuery<HTMLDivElement>;
    const $nextBtn = $('#next-episode') as JQuery<HTMLButtonElement>;
    const $openMenuBtn = $('#open-series-menu') as JQuery<HTMLButtonElement>;
    const $seriesOverlay = $('#series-overlay') as JQuery<HTMLDivElement>;
    const $seasonSelector = $('#season-selector') as JQuery<HTMLSelectElement>;
    const $episodesList = $('#episodes-list') as JQuery<HTMLDivElement>;

    let controlsTimer: number;
    let volumeTimer: number;
    let lastMouseX = 0;
    let lastMouseY = 0;

    // --- 2. DEFINIZIONE FUNZIONI LOGICHE ---
    /**
     * Trova e clicca il prossimo episodio disponibile nell'accordion globale
     */
    const playNext = () => {
        // 1. Trova il pulsante che l'utente ha cliccato per aprire questo video
        const $currentBtn = $(`#accStagioni .btn-carica-video[data-id="${id}"]`).first();

        // 2. Trova il contenitore dell'episodio attuale (il <li>)
        const $currentLi = $currentBtn.closest('li.list-group-item');

        // 3. Cerca il prossimo <li> nella stessa stagione
        let $nextLi = $currentLi.next('li.list-group-item');

        // 4. Se non c'è un prossimo episodio nella stagione attuale, saltiamo alla stagione successiva
        if ($nextLi.length === 0) {
            const $currentSeasonItem = $currentLi.closest('.accordion-item');
            const $nextSeasonItem = $currentSeasonItem.next('.accordion-item');

            if ($nextSeasonItem.length > 0) {
                // Troviamo il primo episodio della nuova stagione
                $nextLi = $nextSeasonItem.find('li.list-group-item').first();

                // Opzionale: apriamo l'accordion della nuova stagione visivamente
                $nextSeasonItem.find('.accordion-button').trigger('click');
            }
        }

        // 5. Se abbiamo trovato un nuovo episodio (nella stessa stagione o nella prossima)
        if ($nextLi.length > 0) {
            // Clicchiamo sul primo bottone disponibile (es. MaxStream)
            const $btnToClick = $nextLi.find('.btn-carica-video').first();
            $btnToClick.trigger('click');
        } else {
            console.log("Fine della serie: non ci sono più episodi o stagioni.");
            // Qui potresti mostrare un avviso con SweetAlert2 o chiudere il player
        }
    };

    /**
     * Popola il menu laterale del player basandosi sull'accordion esistente
     */
    const populateSeriesMenu = () => {
        $seasonSelector.empty();

        // 1. Popola le Stagioni nel select
        $('#accStagioni .accordion-item').each(function (index) {
            const titoloStagione = $(this).find('.accordion-button span').text().trim();
            const targetId = $(this).find('.accordion-collapse').attr('id');
            $seasonSelector.append(`<option value="${targetId}" ${$(this).find('.show').length ? 'selected' : ''}>${titoloStagione}</option>`);
        });

        // 2. Funzione per mostrare episodi di una stagione specifica
        const updateEpisodesList = (targetId: string) => {
            $episodesList.empty();
            const $container = $(`#${targetId}`);

            $container.find('li.list-group-item').each(function () {
                const epTitle = $(this).find('small').text();
                const epId = $(this).find('.btn-carica-video').first().data('id');
                const isCurrent = epId === id;

                // Clona i bottoni (MaxStream/DL) per mantenerli funzionanti
                const $btns = $(this).find('.btn-group').clone();
                $btns.addClass('mt-1');

                const $item = $(`
                    <div class="list-group-item bg-transparent text-white border-secondary py-2 px-0 ${isCurrent ? 'bg-secondary bg-opacity-25' : ''}">
                        <div class="small ${isCurrent ? 'text-info fw-bold' : ''}">${epTitle}</div>
                    </div>
                `);

                $item.append($btns);
                $episodesList.append($item);
            });
        };

        // Inizializza con la stagione corrente
        updateEpisodesList($seasonSelector.val() as string);

        // Listener cambio stagione nel select
        $seasonSelector.on('change', function () {
            updateEpisodesList($(this).val() as string);
        });
    };

    /**
     * Formatta i secondi in una stringa mm:ss.
     * @param {number} s - Secondi da formattare.
     * @returns {string} Tempo formattato (es. "01:25").
     */
    const formatTime = (s: number): string => {
        if (!s || isNaN(s)) return "0:00";
        const m = Math.floor(s / 60);
        const sec = Math.floor(s % 60);
        return `${m}:${sec.toString().padStart(2, '0')}`;
    };

    /**
     * Gestisce la visibilità dei controlli e del cursore.
     * @param {JQuery.MouseMoveEvent} [e] - Evento mouse opzionale.
     * @returns {boolean} True se i controlli sono visualizzati, false se l'evento è stato ignorato.
     */
    const showControls = (e?: JQuery.MouseMoveEvent): boolean => {
        const isHoveringControls = $controls.is(':hover');

        // Protezione contro micro-movimenti
        if (e && e.pageX === lastMouseX && e.pageY === lastMouseY) return false;
        if (e) {
            lastMouseX = e.pageX;
            lastMouseY = e.pageY;
        }

        $controls.removeClass('controls-hidden');
        $container.css('cursor', 'default');

        clearTimeout(controlsTimer);

        // Nasconde automaticamente se in fullscreen e in riproduzione
        if (!$video.prop('paused') && document.fullscreenElement && !isHoveringControls) {
            controlsTimer = window.setTimeout(() => {
                if (!$video.prop('paused')) {
                    $controls.addClass('controls-hidden');
                    $container.css('cursor', 'none');
                }
            }, 2000);
        }
        return true;
    };

    /**
     * Alterna lo stato di riproduzione (Play/Pause).
     * @returns {boolean} True se l'azione è stata eseguita.
     */
    const togglePlay = (): boolean => {
        const videoEl = $video.get(0);
        if (!videoEl) return false;

        if (videoEl.paused) {
            videoEl.play().catch(err => console.warn("Autoplay bloccato:", err));
            $playBtn.html('<i class="bi bi-pause-fill"></i>');
        } else {
            videoEl.pause();
            $playBtn.html('<i class="bi bi-play-fill"></i>');
        }
        return true;
    };

    /**
     * Alterna lo stato del muto.
     * @returns {boolean} True se lo stato è stato cambiato.
     */
    const toggleMute = (): boolean => {
        if (!$video.length) return false;
        const isMuted = !$video.prop('muted');
        const isHalfVol = $video.prop('volume') <= 0.5;
        $video.prop('muted', isMuted);
        $muteBtn.html(isMuted ? '<i class="bi bi-volume-mute-fill"></i>' :
            isHalfVol ? '<i class="bi bi-volume-down-fill"></i>' : '<i class="bi bi-volume-up-fill"></i>');
        $volumeBar.val(isMuted ? 0 : $video.prop('volume'));
        return true;
    };

    /**
     * Imposta il volume del video.
     * @param {number} newVol - Valore tra 0 e 1.
     * @returns {boolean} True se il volume è stato applicato.
     */
    const setVolume = (newVol: number): boolean => {
        if (!$video.length) return false;
        let vol = Math.round(newVol * 10) / 10;
        vol = Math.max(0, Math.min(1, vol));
        $video.prop('volume', vol);
        $volumeBar.val(vol);
        const isHalfVol = vol <= 0.5;
        const isMuted = vol === 0;
        $video.prop('muted', isMuted);
        $muteBtn.html(isMuted ? '<i class="bi bi-volume-mute-fill"></i>' :
            isHalfVol ? '<i class="bi bi-volume-down-fill"></i>' : '<i class="bi bi-volume-up-fill"></i>');

        if ($volumeBar.hasClass('d-none') && !$volumeBar.parent().is(':hover')) {
            $volumeBar.removeClass('d-none');
            volumeTimer = window.setTimeout(() => {
                $volumeBar.addClass('d-none');
            }, 2000);
        };

        return true;
    };

    /**
     * Imposta la posizione temporale del video (Seek).
     * @param {number} newTime - Tempo in secondi.
     * @returns {boolean} True se la posizione è stata aggiornata.
     */
    const setCurrentTime = (newTime: number): boolean => {
        const videoEl = $video.get(0);
        if (!videoEl || isNaN(videoEl.duration)) return false;

        // Validazione range
        let time = Math.max(0, Math.min(newTime, videoEl.duration));
        videoEl.currentTime = time;

        // Aggiornamento UI manuale (opzionale se già gestito da timeupdate)
        const pct = (time / videoEl.duration) * 100;
        $seekBar.val(pct);
        $('#current-time').text(formatTime(time));

        return true;
    };

    /**
     * Alterna la modalità Full Screen.
     * @returns {boolean} True se l'operazione è stata avviata.
     */
    const toggleFullScreen = async (): Promise<boolean> => {
        const containerEl = $container.get(0);
        if (!containerEl) return false;

        if (!document.fullscreenElement) {
            try {
                await containerEl.requestFullscreen();
                $('#full-screen').html('<i class="bi bi-fullscreen-exit"></i>');
                $container.addClass('fullscreen-active');
                $container.on('mousemove', showControls);
                showControls();
            } catch (err) {
                console.error("Errore Fullscreen:", err);
            }
        } else {
            if (document.exitFullscreen) {
                await document.exitFullscreen();
                $('#full-screen').html('<i class="bi bi-fullscreen"></i>');
                $container.removeClass('fullscreen-active').off('mousemove', showControls).css('cursor', 'default');
                $controls.removeClass('controls-hidden');
                clearTimeout(controlsTimer);
                lastMouseX = 0; lastMouseY = 0;
            }
        }
        return true;
    };

    // --- 3. LISTENER DI EVENTI ---

    // Cambiamento stato Fullscreen (Esc o tasto UI)
    $(document).off('fullscreenchange').on('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            $('#full-screen').html('<i class="bi bi-fullscreen"></i>');
            $container.removeClass('fullscreen-active').off('mousemove', showControls).css('cursor', 'default');
            $controls.removeClass('controls-hidden');
            clearTimeout(controlsTimer);
            lastMouseX = 0; lastMouseY = 0;
        }
    });

    // Shortcut da tastiera
    $(document).off('keydown').on('keydown', (e) => {
        if ($(e.target).is('input, textarea')) return;
        const current = $video.prop('currentTime') as number;
        const vol = $video.prop('volume') as number;

        switch (e.key.toLowerCase()) {
            case ' ': e.preventDefault(); togglePlay(); break;
            case 'm': e.preventDefault(); toggleMute(); break;
            case 'f': e.preventDefault(); toggleFullScreen(); break;
            case 'arrowright': e.preventDefault(); setCurrentTime(current + 5); break;
            case 'arrowleft': e.preventDefault(); setCurrentTime(current - 5); break;
            case 'arrowup': e.preventDefault(); setVolume(vol + 0.1); break;
            case 'arrowdown': e.preventDefault(); setVolume(vol - 0.1); break;
        }
    });

    // Eventi Video
    $video.on('click', togglePlay);

    $video.on('dblclick', toggleFullScreen);

    $video.on('mousewheel', (e) => {
        e.preventDefault();
        const deltaY = (e.originalEvent as WheelEvent).deltaY;
        if (deltaY < 0) setVolume($video.prop('volume') as number + 0.1);
        else if (deltaY > 0) setVolume($video.prop('volume') as number - 0.1);
    });

    $video.on('play pause', () => { if (document.fullscreenElement) showControls(); });

    $video.on('timeupdate', () => {
        const current = $video.prop('currentTime') as number;
        const total = $video.prop('duration') as number;
        $('#current-time').text(formatTime(current));
        if (total > 0) $seekBar.val((current / total) * 100);
        localStorage.setItem('last_pos_' + btoa(id), current.toString());
    });

    $video.on('loadedmetadata', () => {
        $('#total-time').text(formatTime($video.prop('duration')));
        const saved = localStorage.getItem('last_pos_' + btoa(id));
        if (saved) setCurrentTime(parseFloat(saved));
    });
    // Autoplay al termine
    $video.on('ended', () => playNext());

    // Click pulsante Next nel player
    $nextBtn.on('click', () => playNext());

    // Gestione Overlay
    $openMenuBtn.on('click', () => {
        populateSeriesMenu();
        $seriesOverlay.removeClass('d-none');
    });

    $('#close-series-menu').on('click', () => $seriesOverlay.addClass('d-none'));
    // Interfaccia Utente
    $playBtn.on('click', togglePlay);
    $muteBtn.on('click', toggleMute);
    $fullScreenBtn.on('click', toggleFullScreen);

    $controls.on('mouseenter', () => {
        clearTimeout(controlsTimer);
        $controls.removeClass('controls-hidden');
        $container.css('cursor', 'default');
    });

    $seekBar.on('input', function () {
        const pct = parseFloat($(this).val() as string);
        const total = $video.prop('duration') as number;
        setCurrentTime((pct / 100) * total);
    });

    $volumeBar.on('input', function () {
        setVolume(parseFloat($(this).val() as string));
    });

    // on hover volumebar
    $muteBtn.parent().on('mouseenter', () => {
        if (volumeTimer) clearTimeout(volumeTimer);
        $volumeBar.removeClass('d-none');
    });

    $muteBtn.parent().on('mouseleave', () => {
        // Scompare dopo 1 secondo se il mouse esce dal raggio d'azione
        volumeTimer = window.setTimeout(() => {
            $volumeBar.addClass('d-none');
        }, 1000);
    });

    var src: string = $video.data('src');

    // $video.get(0)!.setAttribute('referrerpolicy', 'no-referrer');
    // $video.get(0)!.setAttribute('crossorigin', 'anonymous');

    // Impostiamo le proprietà prima della src
    // $video.get(0)!.setAttribute('referrerpolicy', 'no-referrer');
    // $video.get(0)!.preload = "metadata";

    // --- 4. INIZIALIZZAZIONE ---
    // if (src.includes("mxcontent")) src = `./php/proxy.php?url=${encodeURIComponent(src)}`;
    if (src.includes('.m3u8') && Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia($video.get(0)!);
        hls.on(Hls.Events.MANIFEST_PARSED, () => togglePlay());
    } else {
        $video.attr('src', src);
        $video.trigger('load');
        togglePlay();
    }
};