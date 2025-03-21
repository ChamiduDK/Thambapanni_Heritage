
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
    });

    closeSidebar.addEventListener('click', function() {
        sidebar.classList.remove('active');
        mainContent.classList.remove('shifted');
    });
});

function toggleDetails(id) {
    const details = document.getElementById(id);
    details.classList.toggle('active');
}
