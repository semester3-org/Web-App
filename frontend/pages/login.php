<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$page_title = "Login - Kosthub";
include_once '../components/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Login ke Kosthub</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" action="../../backend/auth/login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
            <p><a href="#">Lupa password?</a></p>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const response = await fetch(this.action, {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        window.location.href = 'home.php';
    } else {
        alert(result.error || 'Login gagal');
    }
});
</script>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70vh;
    padding: 2rem 0;
}

.auth-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
}

.auth-card h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: #1f2937;
}

.btn-block {
    width: 100%;
    padding: 1rem;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
}

.auth-links a {
    color: #4f46e5;
    text-decoration: none;
}

.auth-links a:hover {
    text-decoration: underline;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
</style>

<?php include '../includes/footer.php'; ?>