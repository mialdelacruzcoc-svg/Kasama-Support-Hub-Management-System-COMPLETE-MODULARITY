// JavaScript for coach-faq-manager.php

// ADD FAQ AJAX
        document.getElementById('addFaqForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.textContent;
            btn.textContent = 'Publishing...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            try {
                const res = await fetch('../../api/manage-faq.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) { 
                    location.reload(); 
                } else { 
                    alert('Error: ' + data.message); 
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection error.');
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });

        // DELETE FAQ AJAX
        async function deleteFaq(id) {
            if(confirm('Sigurado ka i-delete kini? Kini mahanaw sab sa FAQ page sa mga estudyante.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                try {
                    const res = await fetch('../../api/manage-faq.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if(data.success) { 
                        location.reload(); 
                    }
                } catch (err) {
                    alert('Failed to delete.');
                }
            }
        }
