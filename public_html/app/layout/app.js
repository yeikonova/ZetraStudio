function showToast(msg, success=true, cb=null) {
    const toastDiv = document.createElement('div');
    toastDiv.className = `toast align-items-center text-white bg-${success?'success':'danger'} border-0 position-fixed bottom-0 start-50 translate-middle-x m-4`;
    toastDiv.setAttribute('role', 'alert');
    toastDiv.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toastDiv);
    const toast = new bootstrap.Toast(toastDiv);
    toast.show();
    setTimeout(() => {
        toastDiv.remove();
        if (cb) cb();
    }, 2500);
}
document.addEventListener('DOMContentLoaded', function() {
    const btnSidebar = document.getElementById('btnSidebar');
    const sidebar = document.getElementById('sidebar');
    if(btnSidebar && sidebar) {
        btnSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        // Oculta sidebar si haces click fuera en móvil
        document.addEventListener('click', function(e){
            if(window.innerWidth < 991 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && e.target!==btnSidebar){
                sidebar.classList.remove('show');
            }
        });
    }
});