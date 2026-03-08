// JavaScript for concerns-table.php

// ============================================
    // CONCERNS TABLE SYSTEM
    // ============================================
    let allConcerns = [];
    let currentPage = 1;
    const itemsPerPage = 10;
    let filteredConcerns = [];

    function viewConcern(trackingId) {
        window.location.href = 'concern-details.php?id=' + trackingId;
    }

    function renderTable() {
        const tbody = document.getElementById('concernsTableBody');
        const noResults = document.getElementById('noResults');
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageData = filteredConcerns.slice(start, end);

        if (pageData.length === 0) {
            tbody.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }

        noResults.style.display = 'none';
        tbody.innerHTML = pageData.map(concern => {
            const statusStr = concern.status || 'Pending';
            const categoryStr = concern.category || 'Others';
            const statusClass = statusStr.toLowerCase().replace(/\s+/g, '-');
            const categoryClass = categoryStr.toLowerCase().replace(/\s+/g, '-');
            
            return `
    <tr onclick="viewConcern('${concern.tracking_id}')">
        <td class="concern-id">${concern.tracking_id}</td>
        <td>${concern.student_name || 'Anonymous'}</td> 
        <td>${concern.is_anonymous == 1 ? '---' : concern.student_id}</td>
        <td>${concern.year_level || '---'}</td>
        <td>${concern.subject}</td>
                    <td><span class="badge badge-${categoryClass}">${categoryStr}</span></td>
                    <td><span class="badge badge-${statusClass}">${statusStr}</span></td>
                    <td>${concern.created_at_formatted || concern.created_at}</td>
                    <td>${concern.updated_at_formatted || concern.updated_at || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    function filterConcerns() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
const yearLevelFilter = document.getElementById('yearLevelFilter').value;
const urgencyFilter = document.getElementById('urgencyFilter').value;

filteredConcerns = allConcerns.filter(concern => {
    const matchSearch = 
        (concern.tracking_id || '').toLowerCase().includes(searchTerm) ||
        (concern.student_name || '').toLowerCase().includes(searchTerm) ||
        (concern.subject || '').toLowerCase().includes(searchTerm);
    
    const concernStatus = (concern.status || '').toLowerCase();
    const concernCategory = (concern.category || '').toLowerCase();
    
    const matchStatus = !statusFilter || concernStatus === statusFilter;
    const matchCategory = !categoryFilter || concernCategory === categoryFilter;
    const matchYearLevel = !yearLevelFilter || concern.year_level === yearLevelFilter;
    const matchUrgency = !urgencyFilter || (concern.urgency || '') === urgencyFilter;

    return matchSearch && matchStatus && matchCategory && matchYearLevel && matchUrgency;
});

        currentPage = 1;
        renderTable();
        updatePagination();
    }

    function updatePagination() {
        const totalPages = Math.ceil(filteredConcerns.length / itemsPerPage) || 1;
        document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages} (${filteredConcerns.length} concerns)`;
        document.getElementById('prevBtn').disabled = currentPage === 1;
        document.getElementById('nextBtn').disabled = currentPage === totalPages || filteredConcerns.length === 0;
    }

    function changePage(direction) {
        currentPage += direction;
        renderTable();
        updatePagination();
    }

    function loadConcerns() {
        fetch('../../api/get-concerns.php')
            .then(response => response.json())
            .then(data => {
                console.log('Concerns API response:', data);
                
                if (data.success) {
                    allConcerns = data.data.concerns || data.data || [];
                    filteredConcerns = [...allConcerns];
                    
                    document.getElementById('concernCount').textContent = allConcerns.length;
                    
                    renderTable();
                    updatePagination();
                } else {
                    console.error('API Error:', data.message);
                    document.getElementById('concernsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center; padding:30px; color:red;">Error: ' + data.message + '</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                document.getElementById('concernsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center; padding:30px; color:red;">Failed to load concerns. Check console.</td></tr>';
            });
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadConcerns();
        document.getElementById('searchInput').addEventListener('input', filterConcerns);
document.getElementById('statusFilter').addEventListener('change', filterConcerns);
document.getElementById('categoryFilter').addEventListener('change', filterConcerns);
document.getElementById('yearLevelFilter').addEventListener('change', filterConcerns);
document.getElementById('urgencyFilter').addEventListener('change', filterConcerns);
    });
