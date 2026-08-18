// ============================================================
// The Final Chapter – main.js
// Vanilla JS – Kein jQuery, kein Framework
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- 1. Hamburger-Menü Toggle ----
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav   = document.querySelector('.main-nav');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('open');
            navToggle.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', mainNav.classList.contains('open'));
        });

        // Klick außerhalb schließt Menü
        document.addEventListener('click', function (e) {
            if (!navToggle.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('open');
                navToggle.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ---- 2. Dropdown-Untermenüs auf Touch-Geräten ----
    document.querySelectorAll('.main-nav .dropdown-toggle').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const parent = button.closest('.has-children');
            const open = !parent.classList.contains('submenu-open');

            document.querySelectorAll('.main-nav .has-children.submenu-open').forEach(function (item) {
                if (item !== parent) {
                    item.classList.remove('submenu-open');
                    const toggle = item.querySelector(':scope > .dropdown-toggle');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }
            });

            parent.classList.toggle('submenu-open', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    // ---- 3. Header-Suche Toggle ----
    const searchToggle = document.querySelector('.search-toggle');
    const headerSearch = document.querySelector('.header-search');

    if (searchToggle && headerSearch) {
        searchToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            headerSearch.classList.toggle('open');
            if (headerSearch.classList.contains('open')) {
                const input = headerSearch.querySelector('.header-search-input');
                if (input) setTimeout(() => input.focus(), 50);
            }
        });

        document.addEventListener('click', function (e) {
            if (!headerSearch.contains(e.target)) {
                headerSearch.classList.remove('open');
            }
        });

        // Escape schließt Suchfeld
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                headerSearch.classList.remove('open');
            }
        });
    }

    // ---- 3. Reading Progress Bar ----
    const progressBar = document.getElementById('reading-progress');
    const articleContent = document.querySelector('.article-full-content');

    if (progressBar && articleContent) {
        window.addEventListener('scroll', function () {
            const scrollTop  = window.scrollY || document.documentElement.scrollTop;
            const docHeight  = document.documentElement.scrollHeight - window.innerHeight;
            const progress   = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            progressBar.style.width = Math.min(100, Math.max(0, progress)) + '%';
        }, { passive: true });
    }

    // ---- 4. Slug-Auto-Generator (Admin) ----
    const titleInput = document.getElementById('title');
    const slugInput  = document.getElementById('slug');

    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function () {
            if (!slugInput.dataset.manual) {
                slugInput.value = slugify(titleInput.value);
            }
        });

        slugInput.addEventListener('input', function () {
            slugInput.dataset.manual = 'true';
        });

        // Wenn Slug leer wird, wieder Auto-Modus
        slugInput.addEventListener('blur', function () {
            if (slugInput.value.trim() === '') {
                delete slugInput.dataset.manual;
                slugInput.value = slugify(titleInput.value);
            }
        });
    }

    // ---- 5. Smooth Scroll für Anker-Links ----
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const headerHeight = document.querySelector('.site-header')?.offsetHeight || 0;
                const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

    // ---- 6. Lazy Loading für Bilder ----
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');

        if (lazyImages.length > 0) {
            const imageObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        img.classList.add('loaded');
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '200px 0px'
            });

            lazyImages.forEach(function (img) {
                imageObserver.observe(img);
            });
        }
    } else {
        // Fallback: Alle data-src sofort laden
        document.querySelectorAll('img[data-src]').forEach(function (img) {
            img.src = img.dataset.src;
        });
    }

    // ---- 7. Sticky Header Shadow on Scroll ----
    const siteHeader = document.querySelector('.site-header');
    if (siteHeader) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 10) {
                siteHeader.classList.add('scrolled');
            } else {
                siteHeader.classList.remove('scrolled');
            }
        }, { passive: true });
    }

    // ---- 8. Aktiven Nav-Link markieren ----
    const currentPath = window.location.pathname + window.location.search;
    document.querySelectorAll('.main-nav a').forEach(function (link) {
        const linkPath = link.getAttribute('href');
        if (linkPath && currentPath === linkPath) {
            link.classList.add('active');
        }
    });

});

// ---- Slugify-Funktion ----
function slugify(text) {
    const map = {
        'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss',
        'Ä': 'ae', 'Ö': 'oe', 'Ü': 'ue'
    };
    return text
        .toLowerCase()
        .replace(/[äöüßÄÖÜ]/g, function (c) { return map[c] || c; })
        .replace(/[^a-z0-9\-]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}
