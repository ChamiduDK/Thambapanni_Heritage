function openGallery() {
    document.getElementById("galleryModal").style.display = "flex";
}

function closeGallery() {
    document.getElementById("galleryModal").style.display = "none";
}

// Close the gallery when clicked outside the modal content
window.onclick = function(event) {
    var modal = document.getElementById("galleryModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
}
