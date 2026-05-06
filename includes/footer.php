</div><!-- end container -->

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999" id="toastContainer"></div>

<footer class="bg-dark text-white-50 text-center py-3 mt-5">
  <small>© <?php echo date('Y'); ?> Training Management System. All rights reserved.</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global toast function
function showToast(message, type = 'success') {
    const id = 'toast_' + Date.now();
    const colors = { success: 'bg-success', danger: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info' };
    const html = `
        <div id="${id}" class="toast align-items-center text-white ${colors[type] || 'bg-success'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    new bootstrap.Toast(toastEl, { delay: 3000 }).show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert.alert-dismissible').forEach(function(alert) {
    setTimeout(function() {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        if(bsAlert) bsAlert.close();
    }, 5000);
});

// Confirm before delete/danger actions
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if(!confirm(this.dataset.confirm)) e.preventDefault();
    });
});

// Add loading spinner to payment button
const payBtn = document.getElementById('rzp-pay-btn');
if(payBtn){
    payBtn.addEventListener('click', function(){
        setTimeout(() => {
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Opening payment...';
            this.disabled = false; // Razorpay handles the rest
        }, 100);
    });
}
</script>
</body>
</html>
