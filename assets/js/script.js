document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });
    }

    const revealTargets = document.querySelectorAll('.feature-card, .step, .dashboard-tile, .message-card');
    revealTargets.forEach(function (el) {
        el.classList.add('reveal');
    });

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealTargets.forEach(function (el) {
        observer.observe(el);
    });

    const fileLabels = document.querySelectorAll('.file-label input[type="file"]');
    fileLabels.forEach(function (input) {
        input.addEventListener('change', function () {
            const label = input.closest('.file-label');
            if (input.files && input.files.length > 0) {
                label.firstChild.textContent = input.files[0].name + ' ';
            }
        });
    });

    document.querySelectorAll('.action-btn[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const action = btn.getAttribute('data-action');
            const id = btn.getAttribute('data-id');
            const csrfTokenField = document.getElementById('csrfToken');
            const token = csrfTokenField ? csrfTokenField.value : '';

            if (action === 'delete' && !confirm('Delete this message permanently?')) {
                return;
            }

            if (action === 'report' && !confirm('Report this anonymous sender to the administrator?')) {
                return;
            }

            const formData = new FormData();
            formData.append('message_id', id);
            formData.append('action', action);
            formData.append('csrf_token', token);

            fetch('message-actions.php', {
                method: 'POST',
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        alert(data.error || 'Something went wrong.');
                        return;
                    }
                    const card = btn.closest('.message-card');
                    if (action === 'delete') {
                        card.style.opacity = '0';
                        setTimeout(function () { card.remove(); }, 200);
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function () {
                    alert('Network error. Please try again.');
                });
        });
    });

    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.report-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }
});

function copyProfileLink() {
    const input = document.getElementById('profileLink');
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function () {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = originalText; }, 1500);
    });
}
