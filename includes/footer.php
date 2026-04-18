            </div><!-- .page-content -->
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const toggle = document.getElementById('sidebarToggle');
            const body = document.body;
            
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                if (window.innerWidth <= 768) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                }
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && body.classList.contains('sidebar-open')) {
                    if (!e.target.closest('.sidebar') && !e.target.closest('#sidebarToggle')) {
                        body.classList.remove('sidebar-open');
                    }
                }
            });

            // Profile dropdown - close when clicking outside
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown && !e.target.closest('#profileDropdown')) {
                    dropdown.classList.remove('open');
                }
            });

            // Add entrance animations to stat cards
            document.querySelectorAll('.stat-card').forEach((card, i) => {
                card.classList.add('animate-in');
                card.style.animationDelay = (i * 0.05) + 's';
                card.style.animationFillMode = 'both';
            });

            // Add entrance animations to cards
            document.querySelectorAll('.card').forEach((card, i) => {
                card.classList.add('animate-in');
                card.style.animationDelay = (0.1 + i * 0.05) + 's';
                card.style.animationFillMode = 'both';
            });

            // Theme Toggle
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const html = document.documentElement;
                    const current = html.getAttribute('data-theme') || 'light';
                    const next = current === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-theme', next);
                    localStorage.setItem('prifypay_theme', next);
                });
            }
        });

        // Modal open/close helpers
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }
        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
                e.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => {
                    m.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
        });
    </script>
</body>
</html>
