const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'ico'];

function isImageFile(file) {
    if (!file) return false;
    if (file.type && file.type.startsWith('image/')) return true;
    const ext = file.name.split('.').pop().toLowerCase();
    return ALLOWED_IMAGE_EXTENSIONS.includes(ext);
}

function resolveUploadFolder(input, form) {
    // 1. Explicit data-folder on input
    if (input.dataset.folder) return input.dataset.folder;
    if (input.dataset.uploadFolder) return input.dataset.uploadFolder;
    
    // 2. Explicit data-folder on form
    if (form.dataset.folder) return form.dataset.folder;
    if (form.dataset.uploadFolder) return form.dataset.uploadFolder;

    // 3. Inspect input name
    const inputName = (input.name || '').toLowerCase();
    if (inputName.includes('proof')) return 'payment_proofs';
    if (inputName.includes('review')) return 'reviews';
    if (inputName.includes('avatar')) return 'users';

    // 4. Inspect form action URL or window.location.pathname
    const actionUrl = (form.getAttribute('action') || window.location.pathname || '').toLowerCase();
    if (actionUrl.includes('checkout') || actionUrl.includes('order') || actionUrl.includes('payment')) return 'payment_proofs';
    if (actionUrl.includes('review')) return 'reviews';
    if (actionUrl.includes('product')) return 'products';
    if (actionUrl.includes('user') || actionUrl.includes('profile')) return 'users';

    return 'products';
}

document.addEventListener('submit', async function(e) {
    const form = e.target;
    if (!form || !(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute('data-direct-upload-handled') || form.id === 'productForm' || form.hasAttribute('data-no-direct-upload')) return;
    
    // Temukan semua input type file yang berisi file gambar
    const allFileInputs = Array.from(form.querySelectorAll('input[type="file"]')).filter(input => !input.disabled && input.files.length > 0);
    if (allFileInputs.length === 0) return;

    // Filter hanya input yang berisi file gambar (lewati dokumen/excel/csv)
    const imageInputs = allFileInputs.filter(input => {
        return Array.from(input.files).some(file => isImageFile(file));
    });

    if (imageInputs.length === 0) return;

    e.preventDefault();
    
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.remove('hidden');
        if(loader.querySelector('p')) {
            loader.querySelector('p').textContent = 'Uploading media to Object Storage...';
        }
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
            || form.querySelector('input[name="_token"]')?.value 
            || '';

        for (let input of imageInputs) {
            const folder = resolveUploadFolder(input, form);

            for (let file of input.files) {
                if (!isImageFile(file)) continue;

                const extension = file.name.split('.').pop().toLowerCase();
                let mimeType = file.type;
                if (!mimeType) {
                    if (extension === 'png') mimeType = 'image/png';
                    else if (extension === 'webp') mimeType = 'image/webp';
                    else if (extension === 'gif') mimeType = 'image/gif';
                    else if (extension === 'svg') mimeType = 'image/svg+xml';
                    else if (extension === 'avif') mimeType = 'image/avif';
                    else mimeType = 'image/jpeg';
                }

                const authRes = await fetch('/api/media/upload-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ mime_type: mimeType, extension, folder })
                });

                if (!authRes.ok) {
                    const errData = await authRes.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal mendapatkan pre-signed URL');
                }

                const { upload_url, file_path } = await authRes.json();

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
        imageInputs.forEach(input => input.disabled = false);
    }
});
