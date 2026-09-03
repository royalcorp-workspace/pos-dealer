document.addEventListener('submit', async function(e) {
    const form = e.target;
    if (form.hasAttribute('data-direct-upload-handled')) return;
    
    // Temukan semua input type file yang berisi file
    const fileInputs = Array.from(form.querySelectorAll('input[type="file"]')).filter(input => input.files.length > 0);
    
    if (fileInputs.length === 0) return;

    e.preventDefault();
    
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.remove('hidden');
        if(loader.querySelector('p')) {
            loader.querySelector('p').textContent = 'Uploading media to Object Storage...';
        }
    }

    try {
        for (let input of fileInputs) {
            for (let file of input.files) {
                const extension = file.name.split('.').pop().toLowerCase();
                const mimeType = file.type || 'application/octet-stream';

                const authRes = await fetch('/api/media/upload-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ mime_type: mimeType, extension })
                });

                if (!authRes.ok) throw new Error('Gagal mendapatkan pre-signed URL');
                const { upload_url, file_path, public_url } = await authRes.json();

                const uploadRes = await fetch(upload_url, {
                    method: 'PUT',
                    headers: { 'Content-Type': mimeType },
                    body: file
                });

                if (!uploadRes.ok) throw new Error('Gagal mengunggah file ke Object Storage');

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = input.name;
                hiddenInput.value = file_path;
                form.appendChild(hiddenInput);
            }
            input.disabled = true;
        }

        form.setAttribute('data-direct-upload-handled', 'true');
        form.submit();
    } catch (err) {
        alert('Upload Error: ' + err.message);
        if (loader) loader.classList.add('hidden');
        fileInputs.forEach(input => input.disabled = false);
    }
});
