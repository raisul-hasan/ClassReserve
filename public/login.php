<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
<<<<<<< HEAD
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? '') === 'faculty' ? '/faculty' : '/public/dashboard.php'));
=======
    header('Location: ' . getBaseUrl() . '/public/dashboard.php');
>>>>>>> origin/Riche01
    exit;
}

$pageTitle = 'Sign In';
$hideNav = true;
ob_start();
?>
<div class="login-page">
    <div class="login-brand">
        <div class="login-brand-icon"><i data-lucide="school"></i></div>
        <h1>ClassReserve</h1>
        <p class="tagline">Smart classroom availability &amp; reservation</p>
        <ul class="value-props">
            <li><i data-lucide="search"></i> Search and book available classrooms instantly</li>
            <li><i data-lucide="calendar-check"></i> View schedules on an interactive calendar</li>
            <li><i data-lucide="bell"></i> Real-time notifications for booking updates</li>
            <li><i data-lucide="message-square-warning"></i> Report issues on the classroom forum</li>
        </ul>
    </div>

    <div class="login-panel">
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to your portal or create a new account</p>

            <div id="alert-box" class="hidden"></div>

            <div class="role-tabs">
                <button class="role-tab active" data-role="student">Student</button>
                <button class="role-tab" data-role="club">Club</button>
                <button class="role-tab" data-role="faculty">Faculty</button>
                <button class="role-tab" data-role="admin">Admin</button>
            </div>

            <div class="auth-tabs">
                <button class="auth-tab active" data-target="login-form">Sign In</button>
                <button class="auth-tab" data-target="signup-form">Create Account</button>
            </div>

            <form id="login-form">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" class="form-input" required placeholder="you@university.edu">
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" class="form-input" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary w-full" style="width:100%">Sign In</button>
            </form>

            <form id="signup-form" class="hidden">
                <div class="form-group">
                    <label for="signup-name">Full Name</label>
                    <input type="text" id="signup-name" class="form-input" required placeholder="Your name">
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" class="form-input" required placeholder="you@university.edu">
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" class="form-input" required minlength="6" placeholder="Min 6 characters">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Create Account</button>
            </form>

            <div class="alert alert-info" style="margin-top:20px;font-size:.78rem">
                <strong>Demo accounts:</strong><br>
                Admin: admin@classreserve.local / password<br>
                Faculty: faculty@classreserve.local / faculty123<br>
                Club: club@classreserve.local / student123<br>
                Student: student@classreserve.local / student123
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let selectedRole = 'student';

    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            selectedRole = tab.dataset.role;
        });
    });

    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('login-form').classList.toggle('hidden', tab.dataset.target !== 'login-form');
            document.getElementById('signup-form').classList.toggle('hidden', tab.dataset.target !== 'signup-form');
        });
    });

    const showAlert = (msg, type = 'error') => {
        const box = document.getElementById('alert-box');
        box.className = `alert alert-${type}`;
        box.textContent = msg;
        box.classList.remove('hidden');
    };

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await ClassReserve.api('/api/auth.php?action=login', {
                method: 'POST',
                credentials: 'same-origin',
                body: JSON.stringify({
                    email: document.getElementById('login-email').value,
                    password: document.getElementById('login-password').value,
                    role: selectedRole
                })
            });
<<<<<<< HEAD
            window.location.href = selectedRole === 'admin' ? '/public/admin.php' : (selectedRole === 'faculty' ? '/faculty' : '/public/dashboard.php');
=======
            window.location.href = ClassReserve.baseUrl + (selectedRole === 'admin' ? '/public/admin.php' : '/public/dashboard.php');
>>>>>>> origin/Riche01
        } catch (err) {
            showAlert(err.message);
        }
    });

    document.getElementById('signup-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (selectedRole === 'admin') { showAlert('Admin accounts cannot be self-registered'); return; }
        try {
            await ClassReserve.api('/api/auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('signup-name').value,
                    email: document.getElementById('signup-email').value,
                    password: document.getElementById('signup-password').value,
                    role: selectedRole
                })
            });
            showAlert('Account created! Please sign in.', 'success');
            document.querySelector('[data-target="login-form"]').click();
        } catch (err) {
            showAlert(err.message);
        }
    });
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/includes/layout.php';
