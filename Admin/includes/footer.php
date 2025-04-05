<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Wait for document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    let sidebar = document.querySelector(".sidebar");
    let sidebarBtn = document.querySelector(".sidebarBtn");
    
    // Initialize sidebar state from localStorage if available
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
        sidebar.classList.add("active");
        sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
    }
    
    sidebarBtn.onclick = function() {
        sidebar.classList.toggle("active");
        
        // Store sidebar state in localStorage
        if (sidebar.classList.contains("active")) {
            sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
            localStorage.setItem('sidebarState', 'expanded');
        }
    };
    
    // Add hover effect for dashboard boxes
    const boxes = document.querySelectorAll('.box');
    boxes.forEach(box => {
        box.classList.add('card-hover');
    });
    
    // Add fade-in animation for page content
    const homeContent = document.querySelector('.home-content');
    if (homeContent) {
        homeContent.style.opacity = 0;
        setTimeout(() => {
            homeContent.style.opacity = 1;
            homeContent.style.transition = 'opacity 0.5s ease-in-out';
        }, 100);
    }
});
</script>