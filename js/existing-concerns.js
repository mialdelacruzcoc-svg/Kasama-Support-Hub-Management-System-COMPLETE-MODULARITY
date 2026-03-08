// JavaScript for existing-concerns.php

function filterConcerns() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        document.querySelectorAll('.concern-card').forEach(card => {
            const matchSearch = card.dataset.search.includes(search);
            const matchCategory = !category || card.dataset.category === category;
            const matchStatus = !status || card.dataset.status === status;
            
            if (matchSearch && matchCategory && matchStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Check if any visible
        const visibleCards = document.querySelectorAll('.concern-card[style=""], .concern-card:not([style])');
        const grid = document.getElementById('concernsGrid');
        const existingEmpty = grid.querySelector('.empty-state');
        
        if (visibleCards.length === 0 && !existingEmpty) {
            // All filtered out - could add "no results" message
        }
    }
