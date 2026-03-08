// JavaScript for submit-concern-form.php

// Character counter (your existing code)
        document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(field => {
            const counter = field.nextElementSibling;
            if (counter && counter.classList.contains('char-count')) {
                field.addEventListener('input', () => {
                    counter.textContent = `${field.value.length}/${field.maxLength}`;
                });
            }
        });

        // ============================================
        // FILE UPLOAD HANDLING
        // ============================================
        let selectedFiles = [];
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

        const fileInput = document.getElementById('attachmentInput');
        const uploadArea = document.getElementById('fileUploadArea');
        const previewList = document.getElementById('filePreviewList');

        // File input change
        fileInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            for (let file of files) {
                // Check extension
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(ext)) {
                    alert(`❌ "${file.name}" - File type not allowed`);
                    continue;
                }
                
                // Check size
                if (file.size > maxFileSize) {
                    alert(`❌ "${file.name}" - File too large (Max 5MB)`);
                    continue;
                }
                
                // Check duplicates
                if (selectedFiles.some(f => f.name === file.name)) {
                    alert(`⚠️ "${file.name}" - Already added`);
                    continue;
                }
                
                selectedFiles.push(file);
            }
            
            renderFileList();
        }

        function renderFileList() {
            previewList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const size = file.size >= 1048576 
                    ? (file.size / 1048576).toFixed(2) + ' MB' 
                    : (file.size / 1024).toFixed(2) + ' KB';
                
                const icons = {
                    'pdf': '📄', 'doc': '📝', 'docx': '📝',
                    'xls': '📊', 'xlsx': '📊',
                    'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
                };
                const ext = file.name.split('.').pop().toLowerCase();
                const icon = icons[ext] || '📎';
                
                previewList.innerHTML += `
                    <div class="file-preview-item">
                        <div class="file-info">
                            <span style="font-size: 24px;">${icon}</span>
                            <div>
                                <div class="file-name">${file.name}</div>
                                <div class="file-size">${size}</div>
                            </div>
                        </div>
                        <button type="button" class="remove-file" onclick="removeFile(${index})">✕</button>
                    </div>
                `;
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderFileList();
        }

        // ============================================
        // FORM SUBMISSION (Updated with file upload)
        // ============================================
        document.getElementById('concernForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            
            try {
                // Step 1: Submit the concern first
                const response = await fetch('../../api/submit-concern.php', { 
                    method: 'POST', 
                    body: formData 
                });
                const rawText = await response.text();
                const result = JSON.parse(rawText);
                
                if (result.success) {
                    const trackingId = result.data.tracking_id;
                    const concernId = result.data.concern_id;
                    
                    // Step 2: Upload attachments if any
                    if (selectedFiles.length > 0) {
                        btn.textContent = 'Uploading files...';
                        
                        let uploadSuccess = 0;
                        let uploadFailed = 0;
                        
                        for (let file of selectedFiles) {
                            const fileFormData = new FormData();
                            fileFormData.append('attachment', file);
                            fileFormData.append('tracking_id', trackingId);
                            fileFormData.append('concern_id', concernId);
                            
                            try {
                                const uploadResponse = await fetch('../../api/upload-attachment.php', {
                                    method: 'POST',
                                    body: fileFormData
                                });
                                const uploadResult = await uploadResponse.json();
                                
                                if (uploadResult.success) {
                                    uploadSuccess++;
                                } else {
                                    uploadFailed++;
                                    console.error('Upload failed:', file.name, uploadResult.message);
                                }
                            } catch (err) {
                                uploadFailed++;
                                console.error('Upload error:', file.name, err);
                            }
                        }
                        
                        if (uploadFailed > 0) {
                            alert(`✅ Concern submitted!\n\nTracking ID: ${trackingId}\n\n📎 Files: ${uploadSuccess} uploaded, ${uploadFailed} failed`);
                        } else {
                            alert(`✅ Success!\n\nTracking ID: ${trackingId}\n📎 ${uploadSuccess} file(s) attached`);
                        }
                    } else {
                        alert(`✅ Success!\n\nTracking ID: ${trackingId}`);
                    }
                    
                    window.location.href = 'dashboard.php';
                    
                } else {
                    alert('❌ Error: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Submit Concern';
                }
            } catch (error) {
                alert('⚠️ Connection error.');
                btn.disabled = false;
                btn.textContent = 'Submit Concern';
            }
        });
