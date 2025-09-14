<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kosthub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom, #e0f7ea, #ffffff);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: auto;
        }

        h2 {
            text-align: center;
            color: #2c7a7b;
            margin-bottom: 30px;
        }

        form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        form input, form select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        form input:focus, form select:focus {
            border-color: #2c7a7b;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #2c7a7b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #285e5f;
        }

        .footer-text {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .footer-text a {
            color: #2c7a7b;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>



    <div class="container">
        <h2>Register</h2>
        <form id="registerForm" method="POST" action="../../backend/auth/register.php">
            <label>Email:</label>
            <input type="email" name="email" placeholder="Masukkan email" required>

            <label>Password:</label>
            <input type="password" name="password" placeholder="Masukkan password" required>

            <label>Nama:</label>
            <input type="text" name="name" placeholder="Masukkan nama lengkap" required>

            <label>Telepon:</label>
            <input type="text" name="phone" placeholder="Masukkan nomor telepon">

            <label>Role:</label>
            <select name="role">
                <option value="user">User</option>
                <option value="owner">Owner</option>
            </select>

            <button type="submit">Register</button>
        </form>
        <div class="footer-text">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script>
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Registrasi berhasil!');
            window.location.href = 'login.php';
        } else {
            alert(result.error || 'Terjadi kesalahan');
        }
    });
    </script>
</body>
</html>
