(function () {
    var root = document.getElementById('tour');
    if (!root) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var tokenField = document.getElementById('tourToken');
    var stepLabel = document.getElementById('tourStep');
    var title = document.getElementById('tourTitle');
    var text = document.getElementById('tourText');
    var errorBox = document.getElementById('tourError');
    var dots = document.getElementById('tourDots');
    var back = document.getElementById('tourBack');
    var next = document.getElementById('tourNext');
    var skip = document.getElementById('tourSkip');

    var hasArchive = root.getAttribute('data-archive') === '1';
    var hasVisits = root.getAttribute('data-visits') === '1';
    var hasEffects = root.getAttribute('data-effects') === '1';

    var closing = hasArchive
        ? 'In Messages, you will find an Inbox tab and an Archived tab. Archive anything you want to set aside and restore it whenever you need it.'
        : 'In Messages, you can see everything you have received.';
    if (hasVisits) {
        closing += ' Back here on the dashboard, you can also see your stats and the Link visits for your public link.';
    } else {
        closing += ' Back here on the dashboard, you can also see your inbox stats.';
    }

    var profileText = 'Change your display name, bio, and profile picture. You can also edit the page title, placeholder, and button text of your message form.';
    if (hasEffects) {
        profileText += ' You can also set the confirmation text and effect that senders see after their message is delivered.';
    }

    var steps = [
        {
            title: 'Welcome to UNSAID',
            text: 'This is where you receive anonymous messages. Senders do not need an account or a name, and only you can read what they send. This quick tour only takes a moment.',
            target: null
        },
        {
            title: 'This is your public link',
            text: 'Your personal link lives here. Press Copy to copy it right away.',
            target: '.link-bar'
        },
        {
            title: 'Share your link',
            text: 'Paste it in your bio, group chat, or story. Anyone who opens the link can send you a message without logging in.',
            target: '.link-bar'
        },
        {
            title: 'Messages',
            text: 'Read everything you have received here. You can search, filter by read, unread, or date, mark a message as unread, print it, report the sender, or delete it.',
            target: '.main-action[href="messages.php"]'
        },
        {
            title: 'Edit profile',
            text: profileText,
            target: '.main-action[href="edit-profile.php"]'
        },
        {
            title: hasArchive ? 'Archive and more' : 'Stats and more',
            text: closing,
            target: '.stat-row'
        }
    ];

    var index = 0;
    var focused = null;
    var busy = false;

    function clearFocus() {
        if (focused) {
            focused.classList.remove('tour-focus');
            focused = null;
        }
    }

    function reveal(el) {
        var card = root.querySelector('.tour-card');
        var nav = document.getElementById('siteNav');
        var top = (nav ? nav.getBoundingClientRect().height : 64) + 16;
        var rect = el.getBoundingClientRect();
        var limit = card.getBoundingClientRect().top - 16;
        var spare = limit - top - rect.height;
        var wanted = spare > 0 ? top + Math.min(spare / 2, 40) : top;
        window.scrollBy({ top: rect.top - wanted, left: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    }

    function render() {
        var step = steps[index];
        stepLabel.textContent = 'Step ' + (index + 1) + ' of ' + steps.length;
        title.textContent = step.title;
        text.textContent = step.text;
        errorBox.hidden = true;
        errorBox.textContent = '';
        skip.textContent = 'Skip';
        skip.removeAttribute('data-local');
        back.disabled = index === 0;
        next.textContent = index === steps.length - 1 ? 'Finish' : 'Next';
        next.disabled = false;
        skip.disabled = false;
        busy = false;

        dots.innerHTML = '';
        steps.forEach(function (unused, i) {
            var dot = document.createElement('span');
            dot.className = 'tour-dot' + (i === index ? ' is-on' : '');
            dots.appendChild(dot);
        });

        clearFocus();
        if (step.target) {
            var el = document.querySelector(step.target);
            if (el) {
                focused = el;
                el.classList.add('tour-focus');
                reveal(el);
            }
        } else {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
    }

    function showFailure() {
        busy = false;
        next.disabled = false;
        skip.disabled = false;
        errorBox.hidden = false;
        errorBox.textContent = 'Your progress could not be saved. Try again, or close this for now and it will show up again next time.';
        skip.textContent = 'Close for now';
        skip.setAttribute('data-local', '1');
    }

    function close() {
        clearFocus();
        root.hidden = true;
    }

    function persist() {
        if (busy) return;
        busy = true;
        next.disabled = true;
        skip.disabled = true;
        var data = new FormData();
        data.append('csrf_token', tokenField ? tokenField.value : '');
        fetch('tutorial-complete.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('failed');
                return res.json();
            })
            .then(function (body) {
                if (!body || body.ok !== true) throw new Error('failed');
                close();
            })
            .catch(showFailure);
    }

    next.addEventListener('click', function () {
        if (busy) return;
        if (index === steps.length - 1) {
            persist();
            return;
        }
        index += 1;
        render();
    });

    back.addEventListener('click', function () {
        if (busy || index === 0) return;
        index -= 1;
        render();
    });

    skip.addEventListener('click', function () {
        if (busy) return;
        if (skip.getAttribute('data-local') === '1') {
            skip.removeAttribute('data-local');
            close();
            return;
        }
        persist();
    });

    root.hidden = false;
    render();
    next.focus({ preventScroll: true });
})();
