document.addEventListener('DOMContentLoaded', function() {
        // Function to toggle sidebar state
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }
        
        // Add event listener to the sidebar toggle button
        const sidebarToggle = document.querySelector('.navbar-toggler, #sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        // Check for persisted sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
        
        // Save sidebar state when changed
        document.body.addEventListener('classChange', function() {
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        });
        
        // Create and dispatch custom event when sidebar class changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    document.body.dispatchEvent(new CustomEvent('classChange'));
                }
            });
        });
        
        observer.observe(document.body, { attributes: true });
});