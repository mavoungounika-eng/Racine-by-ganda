/**
 * RACINE BY GANDA — Core Loading States v1.0
 * Namespace: Racine.Loading
 */

window.Racine = window.Racine || {};

window.Racine.Loading = (function () {
    'use strict';

    /* 1. BARRE DE PROGRESSION */
    const Bar = (function () {
        let _bar = null, _timer = null, _current = 0;

        function _getOrCreate() {
            if (_bar) return _bar;
            _bar = document.createElement('div');
            _bar.id = 'racine-progress-bar';
            _bar.setAttribute('role', 'progressbar');
            _bar.setAttribute('aria-label', 'Chargement en cours');
            document.body.appendChild(_bar);
            return _bar;
        }

        function _set(pct) {
            const b = _getOrCreate();
            _current = Math.min(pct, 100);
            b.style.width = _current + '%';
        }

        function start() {
            const b = _getOrCreate();
            b.classList.remove('racine-bar--done');
            b.classList.add('racine-bar--active');
            _set(2);
            clearInterval(_timer);
            _timer = setInterval(function () {
                if (_current < 85) _set(_current + (85 - _current) * 0.08);
            }, 200);
        }

        function done() {
            clearInterval(_timer);
            _set(100);
            const b = _getOrCreate();
            setTimeout(function () {
                b.classList.remove('racine-bar--active');
                b.classList.add('racine-bar--done');
                setTimeout(function () {
                    b.style.width = '0%';
                    b.classList.remove('racine-bar--done');
                }, 400);
            }, 200);
        }

        return { start, done };
    })();

    /* 2. SPINNER BOUTON */
    function showButtonLoader(btn, text) {
        if (!btn) return;
        btn._racineOriginalHTML     = btn.innerHTML;
        btn._racineOriginalDisabled = btn.disabled;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.innerHTML = '<span class="racine-btn-spinner" aria-hidden="true"></span>'
                      + '<span>' + (text || 'Chargement…') + '</span>';
        btn.classList.add('racine-btn--loading');
    }

    function hideButtonLoader(btn) {
        if (!btn || btn._racineOriginalHTML === undefined) return;
        btn.innerHTML = btn._racineOriginalHTML;
        btn.disabled  = btn._racineOriginalDisabled || false;
        btn.removeAttribute('aria-busy');
        btn.classList.remove('racine-btn--loading');
        delete btn._racineOriginalHTML;
        delete btn._racineOriginalDisabled;
    }

    /* 3. AUTO-SPINNER FORMULAIRES (data-loading="true") */
    function _initFormSpinners() {
        document.querySelectorAll('form[data-loading="true"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn  = form.querySelector('[type="submit"]');
                if (btn) {
                    var text = btn.getAttribute('data-loading-text') || 'Traitement…';
                    showButtonLoader(btn, text);
                }
            });
        });
    }

    /* 4. BARRE SUR NAVIGATION PAGE */
    function _initNavigationBar() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = link.getAttribute('href');
            if (!href
                || href.startsWith('#')
                || href.startsWith('javascript')
                || href.startsWith('mailto')
                || href.startsWith('tel')
                || link.getAttribute('target') === '_blank'
                || link.getAttribute('data-bs-toggle')
                || e.ctrlKey || e.metaKey || e.shiftKey
            ) return;
            Bar.start();
        });
        window.addEventListener('pageshow', function () { Bar.done(); });
    }

    /* 5. OVERLAY */
    const overlay = (function () {
        let _el = null;

        function _getOrCreate() {
            if (_el) return _el;
            _el = document.createElement('div');
            _el.id = 'racine-overlay';
            _el.setAttribute('role', 'status');
            _el.innerHTML =
                '<div class="racine-overlay__box">'
              + '<div class="racine-overlay__spinner" aria-hidden="true"></div>'
              + '<p class="racine-overlay__text" id="racine-overlay-text">Chargement…</p>'
              + '</div>';
            document.body.appendChild(_el);
            return _el;
        }

        function show(text) {
            var el    = _getOrCreate();
            var label = el.querySelector('#racine-overlay-text');
            if (label) label.textContent = text || 'Chargement…';
            el.classList.add('racine-overlay--visible');
            document.body.style.overflow = 'hidden';
        }

        function hide() {
            if (!_el) return;
            _el.classList.remove('racine-overlay--visible');
            document.body.style.overflow = '';
        }

        return { show, hide };
    })();

    /* 6. SKELETON JS */
    function showSkeleton(container, lines) {
        if (!container) return;
        container._racineOriginalContent = container.innerHTML;
        var html = '', widths = ['100%','85%','65%','75%','90%'];
        for (var i = 0; i < (lines || 3); i++) {
            html += '<div class="racine-skeleton racine-skeleton--text" style="width:'
                  + widths[i % widths.length] + ';margin-bottom:10px"></div>';
        }
        container.innerHTML = html;
    }

    function hideSkeleton(container) {
        if (!container || !container._racineOriginalContent) return;
        container.innerHTML = container._racineOriginalContent;
        delete container._racineOriginalContent;
    }

    /* 7. INTERCEPTEUR AXIOS */
    function _initAxiosInterceptors() {
        if (!window.axios) return;
        var _pending = 0;

        window.axios.interceptors.request.use(function (config) {
            if (++_pending === 1) Bar.start();
            return config;
        }, function (error) {
            if (--_pending <= 0) { _pending = 0; Bar.done(); }
            return Promise.reject(error);
        });

        window.axios.interceptors.response.use(function (response) {
            if (--_pending <= 0) { _pending = 0; Bar.done(); }
            return response;
        }, function (error) {
            if (--_pending <= 0) { _pending = 0; Bar.done(); }
            return Promise.reject(error);
        });
    }

    /* INIT */
    function init() {
        _initFormSpinners();
        _initNavigationBar();
        _initAxiosInterceptors();
    }

    document.addEventListener('DOMContentLoaded', init);

    return { bar: Bar, overlay, showButtonLoader, hideButtonLoader, showSkeleton, hideSkeleton };

})();
