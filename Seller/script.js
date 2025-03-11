// Modal functionality for "Become a Pro Seller"
function openModal() {
    const modal = document.getElementById('sellerModal');
    modal.style.display = 'block';
    // Add fade-in animation
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.style.transition = 'opacity 0.5s';
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('sellerModal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 500);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('sellerModal');
    if (event.target == modal) {
        closeModal();
    }
};

// Add loading animation (optional, for future dynamic content)
window.addEventListener('load', () => {
    const loader = document.createElement('div');
    loader.className = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
    setTimeout(() => {
        loader.style.display = 'none';
    }, 1000);
});