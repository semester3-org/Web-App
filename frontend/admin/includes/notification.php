<?php
// File: frontend/admin/includes/notification.php
// Include file ini di halaman facilities.php setelah tag <body>

if (isset($_SESSION['success'])): ?>
  <div class="notification notification-success" id="notification">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    <button class="notification-close" onclick="closeNotification()">&times;</button>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="notification notification-error" id="notification">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
    <button class="notification-close" onclick="closeNotification()">&times;</button>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<style>
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 16px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 10000;
  min-width: 300px;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(400px);
    opacity: 0;
  }
}

.notification-success {
  background: #4CAF50;
  color: white;
}

.notification-error {
  background: #f44336;
  color: white;
}

.notification i {
  font-size: 20px;
}

.notification span {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
}

.notification-close {
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.8;
  transition: opacity 0.3s;
}

.notification-close:hover {
  opacity: 1;
}

@media (max-width: 576px) {
  .notification {
    top: 10px;
    right: 10px;
    left: 10px;
    min-width: auto;
  }
}
</style>

<script>
function closeNotification() {
  const notification = document.getElementById('notification');
  if (notification) {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
      notification.remove();
    }, 300);
  }
}

// Auto close after 5 seconds
setTimeout(() => {
  closeNotification();
}, 5000);
</script>