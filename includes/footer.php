    </main><!-- end main-content -->
</div><!-- end app-wrapper -->

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation' };
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icons[type] || icons.success}"></i></div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-xmark"></i>
        </button>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4500);
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.add('hidden');
    });
});

// Open/close modal helpers
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

// Mobile sidebar
const menuToggle = document.getElementById('menu-toggle');
if (menuToggle) {
    menuToggle.style.display = 'block';
    menuToggle.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });
}
</script>
</body>
</html>
