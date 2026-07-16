<!-- Superadmin Password Confirmation Modal -->
<div class="modal-overlay" id="superadmin-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>Confirm Action</h3>
            <button class="modal-close" onclick="closeSuperadminModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modal-message" class="modal-message" style="display:none;"></div>
            <form id="superadmin-form" onsubmit="return false;">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="form-group-modal">
                    <label>Enter your password to continue</label>
                    <input type="password" name="superadmin_password" id="superadmin-password" required autocomplete="off">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSuperadminModal()">Cancel</button>
            <button type="button" class="btn" id="modal-confirm-btn">Confirm</button>
        </div>
    </div>
</div>

<script>
let pendingAction = null; // will hold either a form or an object with delete info

function attachSuperadminModals() {
    // 1. Handle forms with class 'superadmin-action'
    document.querySelectorAll('form.superadmin-action').forEach(form => {
        if (form.dataset.modalAttached === 'true') return;
        form.dataset.modalAttached = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            pendingAction = { type: 'form', form: form };
            showModal(form.getAttribute('data-confirm') || 'Perform this action?');
        });
    });

    // 2. Handle buttons with class 'superadmin-delete-btn'
    document.querySelectorAll('.superadmin-delete-btn').forEach(btn => {
        if (btn.dataset.modalAttached === 'true') return;
        btn.dataset.modalAttached = 'true';
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const confirmMsg = this.getAttribute('data-confirm') || 'Delete this student?';
            const redirectUrl = this.getAttribute('data-redirect') || 'index.php';
            pendingAction = {
                type: 'delete',
                studentId: studentId,
                redirect: redirectUrl
            };
            showModal(confirmMsg);
        });
    });
}

function showModal(message) {
    document.getElementById('modal-message').innerHTML = message;
    document.getElementById('modal-message').style.display = 'block';
    document.getElementById('superadmin-modal').classList.add('active');
    document.getElementById('superadmin-password').value = '';
    document.getElementById('superadmin-password').focus();
}

function closeSuperadminModal() {
    document.getElementById('superadmin-modal').classList.remove('active');
    pendingAction = null;
}

document.getElementById('modal-confirm-btn').addEventListener('click', function() {
    const password = document.getElementById('superadmin-password').value;
    if (!password) {
        alert('Please enter your password.');
        return;
    }

    if (!pendingAction) return;

    if (pendingAction.type === 'form') {
        // Add password field and submit the original form
        const form = pendingAction.form;
        form.querySelectorAll('input[name="superadmin_password"]').forEach(el => el.remove());
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'superadmin_password';
        hiddenInput.value = password;
        form.appendChild(hiddenInput);
        form.submit();
    } else if (pendingAction.type === 'delete') {
        // Create a temporary form for deletion
        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = 'delete_student.php';
        tempForm.style.display = 'none';

        // CSRF token (grab from the page – a hidden field exists)
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        // We need a valid CSRF token; the page already has one in the main form, we can copy its value.
        const mainCsrf = document.querySelector('input[name="csrf_token"]');
        csrfInput.value = mainCsrf ? mainCsrf.value : '';
        tempForm.appendChild(csrfInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'student_id';
        idInput.value = pendingAction.studentId;
        tempForm.appendChild(idInput);

        const pwInput = document.createElement('input');
        pwInput.type = 'hidden';
        pwInput.name = 'superadmin_password';
        pwInput.value = password;
        tempForm.appendChild(pwInput);

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect';
        redirectInput.value = pendingAction.redirect;
        tempForm.appendChild(redirectInput);

        document.body.appendChild(tempForm);
        tempForm.submit();
    }

    closeSuperadminModal();
});

document.getElementById('superadmin-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSuperadminModal();
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    attachSuperadminModals();
    // Also observe dynamically added elements (safety net)
    new MutationObserver(attachSuperadminModals).observe(document.body, { childList: true, subtree: true });
});
</script>