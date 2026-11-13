(function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initNav() {
        var nav = document.getElementById('siteNav');
        var toggle = document.getElementById('navToggle');
        var links = document.getElementById('navLinks');
        if (!toggle || !links) return;

        function setOpen(open) {
            links.classList.toggle('open', open);
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function () {
            setOpen(!links.classList.contains('open'));
        });
        links.addEventListener('click', function (event) {
            if (event.target.closest('a')) setOpen(false);
        });
        document.addEventListener('click', function (event) {
            if (nav && !nav.contains(event.target)) setOpen(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 820) setOpen(false);
        });
    }

    function initReveal() {
        var targets = document.querySelectorAll('.feature-card, .step, .stat, .panel, .report-card, .message-row');
        if (!('IntersectionObserver' in window)) return;
        targets.forEach(function (el) { el.classList.add('reveal'); });
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        targets.forEach(function (el) { observer.observe(el); });
    }

    function initBackground() {
        var stage = document.getElementById('bgStage');
        if (!stage) return;
        var layers = {};
        stage.querySelectorAll('.bg-layer').forEach(function (layer) {
            layers[layer.getAttribute('data-bg')] = layer;
        });
        var base = stage.getAttribute('data-base') || '';
        var small = window.matchMedia('(max-width: 900px)');
        var current = null;

        function url(n) {
            return base + 'bg-' + n + (small.matches ? '-sm' : '-lg') + '.jpg';
        }

        function ready(layer, done) {
            if (layer.getAttribute('src') && layer.complete && layer.naturalWidth > 0) {
                done();
                return;
            }
            if (!layer.getAttribute('src')) layer.src = url(layer.getAttribute('data-bg'));
            layer.addEventListener('load', function onLoad() {
                layer.removeEventListener('load', onLoad);
                done();
            });
        }

        function preload(n) {
            var layer = layers[n];
            if (layer && !layer.getAttribute('src')) layer.src = url(n);
        }

        function show(n) {
            var layer = layers[n];
            if (!layer || current === n) return;
            current = n;
            ready(layer, function () {
                if (current !== n) return;
                Object.keys(layers).forEach(function (key) {
                    layers[key].classList.toggle('is-active', key === n);
                });
            });
        }

        var first = stage.querySelector('.bg-layer');
        if (first) current = first.getAttribute('data-bg');

        if (stage.getAttribute('data-mode') !== 'scroll') return;

        var sections = Array.prototype.slice.call(document.querySelectorAll('main [data-bg]'));
        if (!sections.length) return;
        var ticking = false;

        function neighbours(index) {
            [index - 1, index + 1].forEach(function (i) {
                if (sections[i]) preload(sections[i].getAttribute('data-bg'));
            });
        }

        function update() {
            ticking = false;
            var probe = window.innerHeight * 0.45;
            var index = 0;
            for (var i = 0; i < sections.length; i++) {
                var rect = sections[i].getBoundingClientRect();
                if (rect.top <= probe) index = i;
            }
            var wanted = sections[index].getAttribute('data-bg');
            if (wanted !== current) show(wanted);
            neighbours(index);
        }

        function request() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', request, { passive: true });
        window.addEventListener('resize', request);
        window.addEventListener('load', function () {
            update();
            setTimeout(function () {
                sections.forEach(function (section) { preload(section.getAttribute('data-bg')); });
            }, 1500);
        });
        update();
    }

    var particleSets = {
        hearts: ['♥', '❤', '♥'],
        emojis: ['😊', '✨', '🌙', '💬', '🤍'],
        sparkles: ['✦', '✧', '•']
    };

    function runParticles(kind, host) {
        var set = particleSets[kind];
        if (!set || reduceMotion || !host) return null;
        var count = window.innerWidth < 600 ? 10 : 16;
        var run = document.createElement('div');
        run.className = 'fx-run';
        var longest = 0;
        for (var i = 0; i < count; i++) {
            var span = document.createElement('span');
            var delay = Math.random() * 0.9;
            var duration = 2.2 + Math.random() * 1.2;
            span.className = 'fx-particle fx-' + kind;
            span.textContent = set[i % set.length];
            span.style.left = (6 + Math.random() * 88) + '%';
            span.style.fontSize = (14 + Math.random() * 14) + 'px';
            span.style.animationDelay = delay + 's';
            span.style.animationDuration = duration + 's';
            longest = Math.max(longest, delay + duration);
            run.appendChild(span);
        }
        host.appendChild(run);
        var timer = setTimeout(function () {
            timer = null;
            if (run.parentNode) run.parentNode.removeChild(run);
        }, Math.ceil(longest * 1000) + 300);
        return {
            stop: function () {
                if (timer !== null) {
                    clearTimeout(timer);
                    timer = null;
                }
                if (run.parentNode) run.parentNode.removeChild(run);
            }
        };
    }

    function initSuccessFx() {
        var fx = document.getElementById('successFx');
        if (!fx) return;
        var kind = fx.getAttribute('data-effect');
        if (kind === 'confirm') {
            var closing = false;
            var close = function () {
                if (closing) return;
                closing = true;
                fx.classList.add('closing');
                setTimeout(function () { fx.remove(); }, 350);
            };
            setTimeout(close, 2200);
            fx.addEventListener('click', close);
            return;
        }
        runParticles(kind, fx);
        setTimeout(function () { fx.remove(); }, 4800);
    }

    function initFileLabels() {
        document.querySelectorAll('.file-label input[type="file"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var label = input.closest('.file-label');
                if (input.files && input.files.length > 0 && label.firstChild) {
                    label.firstChild.textContent = input.files[0].name + ' ';
                }
            });
        });
    }

    function initMessageActions() {
        var buttons = document.querySelectorAll('.action-btn[data-action]');
        if (!buttons.length) return;
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-action');
                var id = btn.getAttribute('data-id');
                var tokenField = document.getElementById('csrfToken');
                var token = tokenField ? tokenField.value : '';
                var detail = btn.closest('.message-detail');
                var tab = detail ? detail.getAttribute('data-tab') : 'inbox';
                var listUrl = 'messages.php' + (tab === 'archived' ? '?tab=archived' : '');

                if (action === 'delete' && !confirm('Delete this message permanently?')) return;
                if (action === 'report' && !confirm('Report this anonymous sender to the administrator?')) return;

                var formData = new FormData();
                formData.append('message_id', id);
                formData.append('action', action);
                formData.append('csrf_token', token);

                btn.disabled = true;
                fetch('message-actions.php', { method: 'POST', body: formData })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.ok) {
                            btn.disabled = false;
                            alert(data.error || 'Something went wrong.');
                            return;
                        }
                        if (action === 'report') {
                            try { window.sessionStorage.setItem('unsaid:noreload', '1'); } catch (err) {}
                            window.location.reload();
                        } else if (action === 'restore') {
                            window.location.href = 'messages.php';
                        } else if (action === 'archive') {
                            window.location.href = 'messages.php';
                        } else {
                            window.location.href = listUrl;
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        alert('Network error. Please try again.');
                    });
            });
        });
    }

    function initConfirmForms() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!confirm(form.getAttribute('data-confirm'))) event.preventDefault();
            });
        });
    }

    function initMessageForm() {
        var form = document.querySelector('.message-form');
        if (!form) return;
        var submit = form.querySelector('button[type="submit"]');
        if (!submit) return;
        var label = submit.textContent;
        form.addEventListener('submit', function () {
            setTimeout(function () {
                submit.disabled = true;
                submit.textContent = 'Sending...';
            }, 0);
        });
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                submit.disabled = false;
                submit.textContent = label;
            }
        });
    }

    function initSelectAll() {
        var selectAll = document.getElementById('selectAll');
        if (!selectAll) return;
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.report-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var input = document.getElementById('profileLink');
            if (!input) { reject(); return; }
            input.select();
            input.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy') ? resolve() : reject();
            } catch (err) {
                reject(err);
            }
        });
    }

    function initCopyLink() {
        var btn = document.getElementById('copyLinkBtn');
        var input = document.getElementById('profileLink');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            var original = btn.textContent;
            copyText(input.value).then(function () {
                btn.textContent = 'Copied!';
            }).catch(function () {
                input.select();
                btn.textContent = 'Press Ctrl+C';
            }).then(function () {
                setTimeout(function () { btn.textContent = original; }, 1600);
            });
        });
    }

    function initSettingsPreview() {
        var button = document.getElementById('previewEffect');
        var box = document.getElementById('effectPreview');
        var text = document.getElementById('success_message');
        var count = document.getElementById('successCount');
        if (text && count) {
            var sync = function () { count.textContent = text.value.length; };
            text.addEventListener('input', sync);
            sync();
        }
        if (!button || !box) return;
        var runId = 0;
        var active = null;
        button.addEventListener('click', function () {
            runId += 1;
            var mine = runId;
            if (active) {
                active.stop();
                active = null;
            }
            var chosen = document.querySelector('input[name="success_effect"]:checked');
            var kind = chosen ? chosen.value : 'confirm';
            var message = text && text.value.trim() !== '' ? text.value.trim() : (text ? text.getAttribute('placeholder') : '');
            while (box.firstChild) box.removeChild(box.firstChild);
            box.classList.add('is-on');
            if (kind === 'confirm') {
                var mark = document.createElement('span');
                mark.className = 'preview-mark';
                mark.textContent = '✓';
                box.appendChild(mark);
            }
            var note = document.createElement('p');
            note.className = 'preview-text';
            note.textContent = kind === 'none' ? message : (kind === 'confirm' ? 'Message sent. ' : '') + message;
            box.appendChild(note);
            var handle = runParticles(kind, box);
            if (mine === runId) active = handle;
        });
    }

    function initRange() {
        var buttons = document.querySelectorAll('.range-btn');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (other) { other.classList.toggle('is-active', other === btn); });
                document.dispatchEvent(new CustomEvent('unsaid:range', { detail: parseInt(btn.getAttribute('data-range'), 10) }));
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initNav();
        initBackground();
        initReveal();
        initFileLabels();
        initMessageActions();
        initConfirmForms();
        initMessageForm();
        initSelectAll();
        initCopyLink();
        initSettingsPreview();
        initRange();
        initSuccessFx();
    });
})();
