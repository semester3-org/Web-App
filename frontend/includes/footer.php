<?php
// footer.php
?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Kosthub. Semua hak cipta dilindungi.</p>
            <nav class="footer-nav">
                <a href="index.php">Beranda</a> |
                <a href="home.php">Cari Kos</a> |
                <a href="#">Tentang</a> |
                <a href="#">Kontak</a>
            </nav>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

<style>
.footer {
    background-color: #4f46e5;
    color: white;
    padding: 1.5rem 0;
    text-align: center;
    margin-top: 3rem;
    font-size: 0.9rem;
}

.footer a {
    color: #d1d5db;
    text-decoration: none;
    margin: 0 0.5rem;
    transition: color 0.3s;
}

.footer a:hover {
    color: white;
}
</style>