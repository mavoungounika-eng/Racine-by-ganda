/**
 * ============================================
 * APPEARANCE.JS - RACINE BY GANDA
 * Gestion dynamique de l'apparence
 * ============================================
 */

class AppearanceManager {
    constructor() {
        this.init();
    }

    init() {
        // Charger les préférences au démarrage
        this.loadPreferences();

        // Écouter les changements
        this.setupEventListeners();

        // Mode auto si activé
        if (this.getPreference('display_mode') === 'auto') {
            this.applyAutoMode();
            this.setupAutoModeInterval();
        }
    }

    /**
     * Charger les préférences depuis localStorage
     */
    loadPreferences() {
        const theme = this.getPreference('display_mode') || 'dark';
        const accentPalette = this.getPreference('accent_palette') || 'orange';
        const animationIntensity = this.getPreference('animation_intensity') || 'standard';
        const visualStyle = this.getPreference('visual_style') || 'neutral';
        const contrastLevel = this.getPreference('contrast_level') || 'normal';
        const goldenLight = this.getPreference('golden_light_filter') === 'true';

        this.applyTheme(theme);
        this.applyAccentPalette(accentPalette);
        this.applyAnimationIntensity(animationIntensity);
        this.applyVisualStyle(visualStyle);
        this.applyContrastLevel(contrastLevel);
        if (goldenLight) this.applyGoldenLight(true);
    }

    /**
     * Obtenir une préférence
     */
    getPreference(key) {
        return localStorage.getItem(`racine_${key}`);
    }

    /**
     * Sauvegarder une préférence
     */
    setPreference(key, value) {
        localStorage.setItem(`racine_${key}`, value);
    }

    /**
     * Appliquer le thème
     */
    applyTheme(theme) {
        if (theme === 'auto') {
            this.applyAutoMode();
            return;
        }

        document.documentElement.setAttribute('data-theme', theme);
        this.setPreference('display_mode', theme);

        // Charger le CSS approprié
        this.loadThemeCSS(theme);

        // Sync avec serveur si connecté
        this.syncWithServer({ display_mode: theme });
    }

    /**
     * Mode automatique selon l'heure
     */
    applyAutoMode() {
        const hour = new Date().getHours();
        const theme = (hour >= 6 && hour < 18) ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', theme);
        this.loadThemeCSS(theme);
    }

    /**
     * Intervalle pour le mode auto
     */
    setupAutoModeInterval() {
        setInterval(() => {
            if (this.getPreference('display_mode') === 'auto') {
                this.applyAutoMode();
            }
        }, 60000); // Vérifier chaque minute
    }

    /**
     * Charger le CSS du thème
     */
    loadThemeCSS(theme) {
        const existingLink = document.getElementById('theme-css');
        if (existingLink) {
            existingLink.remove();
        }

        const link = document.createElement('link');
        link.id = 'theme-css';
        link.rel = 'stylesheet';
        link.href = `/css/design-system-${theme}.css`;
        document.head.appendChild(link);
    }

    /**
     * Appliquer la palette d'accent
     */
    applyAccentPalette(palette) {
        const colors = {
            orange: '#ED5F1E',
            yellow: '#FFB800',
            gold: '#D4AF37',
            red: '#DC2626'
        };

        document.documentElement.style.setProperty('--accent-primary', colors[palette]);
        this.setPreference('accent_palette', palette);
        this.syncWithServer({ accent_palette: palette });
    }

    /**
     * Appliquer l'intensité des animations
     */
    applyAnimationIntensity(intensity) {
        document.body.classList.remove('animations-none', 'animations-soft', 'animations-standard', 'animations-luxury');
        document.body.classList.add(`animations-${intensity}`);
        this.setPreference('animation_intensity', intensity);
        this.syncWithServer({ animation_intensity: intensity });
    }

    /**
     * Appliquer le style visuel
     */
    applyVisualStyle(style) {
        document.body.classList.remove('style-female', 'style-male', 'style-neutral');
        document.body.classList.add(`style-${style}`);
        this.setPreference('visual_style', style);
        this.syncWithServer({ visual_style: style });
    }

    /**
     * Appliquer le niveau de contraste
     */
    applyContrastLevel(level) {
        document.body.classList.remove('contrast-normal', 'contrast-bright', 'contrast-dark');
        document.body.classList.add(`contrast-${level}`);
        this.setPreference('contrast_level', level);
        this.syncWithServer({ contrast_level: level });
    }

    /**
     * Appliquer/Retirer le filtre Golden Light
     */
    applyGoldenLight(active) {
        if (active) {
            document.body.classList.add('golden-light-active');
        } else {
            document.body.classList.remove('golden-light-active');
        }
        this.setPreference('golden_light_filter', active);
        this.syncWithServer({ golden_light_filter: active });
    }

    /**
     * Synchroniser avec le serveur
     */
    async syncWithServer(data) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;

        try {
            const response = await fetch('/appearance/update-single', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content
                },
                body: JSON.stringify({
                    key: Object.keys(data)[0],
                    value: Object.values(data)[0]
                })
            });

            if (!response.ok) {
                console.error('Erreur lors de la synchronisation des préférences');
            }
        } catch (error) {
            console.error('Erreur réseau:', error);
        }
    }

    /**
     * Prévisualiser un thème
     */
    async previewTheme(theme, accentPalette = null) {
        const data = { display_mode: theme };
        if (accentPalette) data.accent_palette = accentPalette;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;

        try {
            const response = await fetch('/appearance/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            // Appliquer temporairement
            document.documentElement.setAttribute('data-theme', theme);
            this.loadThemeCSS(theme);
            if (accentPalette) this.applyAccentPalette(accentPalette);
        } catch (error) {
            console.error('Erreur de prévisualisation:', error);
        }
    }

    /**
     * Configurer les écouteurs d'événements
     */
    setupEventListeners() {
        // Toggle thème rapide
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-toggle-theme]')) {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                this.applyTheme(newTheme);
            }
        });

        // Raccourci clavier (Ctrl+Shift+T)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                this.applyTheme(newTheme);
            }
        });
    }

    /**
     * Réinitialiser aux valeurs par défaut
     */
    async reset() {
        localStorage.removeItem('racine_display_mode');
        localStorage.removeItem('racine_accent_palette');
        localStorage.removeItem('racine_animation_intensity');
        localStorage.removeItem('racine_visual_style');
        localStorage.removeItem('racine_contrast_level');
        localStorage.removeItem('racine_golden_light_filter');

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            await fetch('/appearance/reset', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.content
                }
            });
        }

        location.reload();
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', () => {
    window.appearanceManager = new AppearanceManager();
});

// Fonctions globales pour faciliter l'utilisation
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    window.appearanceManager.applyTheme(newTheme);
}

function setTheme(theme) {
    window.appearanceManager.applyTheme(theme);
}

function setAccentPalette(palette) {
    window.appearanceManager.applyAccentPalette(palette);
}

function setAnimationIntensity(intensity) {
    window.appearanceManager.applyAnimationIntensity(intensity);
}

function setVisualStyle(style) {
    window.appearanceManager.applyVisualStyle(style);
}

function toggleGoldenLight() {
    const isActive = document.body.classList.contains('golden-light-active');
    window.appearanceManager.applyGoldenLight(!isActive);
}
