document.addEventListener('DOMContentLoaded', function() {
    // Gallery Filtering
    const filterButtons = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            const filterValue = button.getAttribute('data-filter');

            galleryItems.forEach((item, index) => {
                if (filterValue === 'all') {
                    if (index < 4) {
                        item.style.display = 'block';
                        setTimeout(() => {
                            item.style.opacity = '1';
                        }, 100);
                    } else {
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 300);
                    }
                } else if (item.classList.contains(filterValue)) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                    }, 100);
                } else {
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });

    // Smooth Scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Image Loading Animation
    const images = document.querySelectorAll('img');
    const imageOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px 50px 0px'
    };

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, imageOptions);

    images.forEach(image => imageObserver.observe(image));
});
document.addEventListener('DOMContentLoaded', function() {
    const itemsPerPage = 4;
    let currentPage = 0;
    const galleryItems = document.querySelectorAll('.gallery-item');
    const totalPages = Math.ceil(galleryItems.length / itemsPerPage);

    function showPage(page) {
        const startIndex = page * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        galleryItems.forEach((item, index) => {
            item.classList.remove('active');
            
            setTimeout(() => {
                if (index >= startIndex && index < endIndex) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.classList.add('active');
                    }, 50);
                } else {
                    item.style.display = 'none';
                }
            }, 300);
        });
    }
    

    document.querySelector('.prev-btn').addEventListener('click', () => {
        currentPage = (currentPage - 1 + totalPages) % totalPages;
        showPage(currentPage);
    });

    document.querySelector('.next-btn').addEventListener('click', () => {
        currentPage = (currentPage + 1) % totalPages;
        showPage(currentPage);
    });

    // Show initial page
    showPage(0);

    // Existing filter functionality modified to work with pagination
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentPage = 0;
            
            const filterValue = button.getAttribute('data-filter');
            galleryItems.forEach(item => {
                if (filterValue === 'all' || item.classList.contains(filterValue)) {
                    item.classList.add('filtered-in');
                    item.classList.remove('filtered-out');
                } else {
                    item.classList.add('filtered-out');
                    item.classList.remove('filtered-in');
                }
            });
            
            showPage(0);
        });
    });
});
