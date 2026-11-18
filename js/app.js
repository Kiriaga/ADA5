// js/app.js
document.addEventListener('alpine:init', () => {
    Alpine.data('virtualStore', () => ({
        init() {
            console.log('VirtualStore Front-End inicializado correctamente.');
        },
        formatCurrency(amount) {
            return '$' + Number(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    }));
});

// Función auxiliar global para notificaciones flotantes estilo toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-5 right-5 z-50 px-6 py-3 rounded-lg shadow-xl text-white font-medium transition-all transform translate-y-0 ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}