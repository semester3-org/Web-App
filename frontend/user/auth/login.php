<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KostHub - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { background-color: #f8f9fa; }
    .auth-card {
      background: #fff; border-radius: 10px; padding: 2rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .btn-success { background-color: #28a745; border-color: #28a745; }
    .btn-success:hover { background-color: #218838; border-color: #1e7e34; }
    .auth-link { color: #28a745; text-decoration: none; }
    .auth-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="auth-card">
          <h3 class="fw-bold mb-3">Welcome Back!</h3>
          <p class="text-muted mb-4">Login to continue your journey.</p>
          <form>
            <div class="mb-3">
              <label for="loginEmail" class="form-label">Email or Username</label>
              <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com">
            </div>
            <div class="mb-3">
              <label for="loginPassword" class="form-label">Password</label>
              <input type="password" class="form-control" id="loginPassword" placeholder="••••••••">
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <input type="checkbox" id="rememberMe"> <label for="rememberMe">Remember me</label>
              </div>
              <a href="#" class="auth-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-success w-100">Login</button>
          </form>
          <p class="mt-3 text-center text-muted">
            Don't have an account? <a href="register.php" class="auth-link">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
