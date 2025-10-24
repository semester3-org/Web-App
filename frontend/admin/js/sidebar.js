// Toggle Sidebar Function
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('minimized');
  
  // Toggle class pada body untuk adjust konten
  document.body.classList.toggle('sidebar-minimized');
  
  // Simpan status ke localStorage
  if (sidebar.classList.contains('minimized')) {
    localStorage.setItem('sidebarMinimized', 'true');
  } else {
    localStorage.setItem('sidebarMinimized', 'false');
  }
}

// Restore sidebar state saat halaman dimuat
window.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const logoContainer = document.getElementById('logoContainer');
  const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
  
  if (isMinimized) {
    sidebar.classList.add('minimized');
    document.body.classList.add('sidebar-minimized');
  }
  
  // Tambahkan onclick pada logo container untuk toggle saat minimized
  if (logoContainer) {
    logoContainer.addEventListener('click', function(e) {
      if (sidebar.classList.contains('minimized')) {
        toggleSidebar();
      }
    });
  }

  // Handle submenu toggle dengan mempertimbangkan inline style dari PHP
  document.querySelectorAll('.menu-toggle').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      const parent = item.closest('.has-submenu');
      const submenu = parent.querySelector('.submenu');
      
      // Toggle class open
      parent.classList.toggle('open');
      
      // Toggle submenu display dengan handle inline style
      if (parent.classList.contains('open')) {
        submenu.style.display = 'block';
      } else {
        submenu.style.display = 'none';
      }
    });
  });
});

