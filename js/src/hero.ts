export const initHeroVideo = () => {
    const videoContainer = $('#hero-video');
    if (videoContainer.length == 0) return;

    // Se l'API è già stata caricata da un'altra parte, inizializziamo subito
    if (typeof YT !== 'undefined' && YT.Player) {
        createPlayer();
    } else {
        // Altrimenti aspettiamo che l'API sia pronta (YouTube chiama questa funzione globale)
        window.onYouTubeIframeAPIReady = () => {
            createPlayer();
        };
    }
};

let player: YT.Player;

const createPlayer = () => {
    player = new YT.Player('hero-video', {
        videoId: '_8V3p9tm5rc',
        playerVars: {
            'autoplay': 1,
            'mute': 1,
            'controls': 0,
            'loop': 1,
            'playlist': '_8V3p9tm5rc',
            'modestbranding': 1,
            'rel': 0,
            'iv_load_policy': 3,
            'enablejsapi': 1,
            // Rimuovi 'origin' se sei su localhost e vedi errori
            'origin': window.location.origin, // Fondamentale per Codespaces
            // 'widget_referrer': window.location.origin,
        },
        events: {
            'onReady': (event) => {
                event.target.mute();
                event.target.playVideo();
                // Se vuoi l'effetto "cinetico", tieni il 2x, 
                // altrimenti 1.5x è un buon compromesso.
                event.target.setPlaybackQuality('hd1080');
                event.target.setPlaybackRate(3);
            },
            'onStateChange': (event) => {
                if (event.data === YT.PlayerState.ENDED) {
                    player.playVideo(); // Forza il loop
                }
            }
        }
    });
};