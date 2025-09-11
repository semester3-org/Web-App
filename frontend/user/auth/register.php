<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KostHub - Register</title>
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
          <h3 class="fw-bold mb-3">Create an Account</h3>
          <p class="text-muted mb-4">Start your adventure with us.</p>
          <form>
            <div class="mb-3">
              <label for="registerName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="registerName" placeholder="John Doe">
            </div>
            <div class="mb-3">
              <label for="registerEmail" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="registerEmail" placeholder="you@example.com">
            </div>
            <div class="mb-3">
              <label for="registerPassword" class="form-label">Password</label>
              <input type="password" class="form-control" id="registerPassword" placeholder="••••••••">
            </div>
            <div class="mb-3">
              <label for="confirmPassword" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="confirmPassword" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-success w-100">Create Account</button>
          </form>
          <p class="mt-3 text-center text-muted">
            Already have an account? <a href="login.php" class="auth-link">Login here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
