async function uploadProductImage(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    const mimeType = file.type;

    // 1. Minta Signed URL
    const authRes = await fetch('/api/media/upload-url', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // Pastikan meta csrf-token ada di layout HTML
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ mime_type: mimeType, extension })
    });

    if (!authRes.ok) throw new Error('Gagal mendapatkan pre-signed URL');
    const { upload_url, file_path, public_url } = await authRes.json();

    // 2. Direct upload file binary ke RustFS via HTTP PUT
    const uploadRes = await fetch(upload_url, {
        method: 'PUT',
        headers: {
            'Content-Type': mimeType // Wajib sama dengan yang divalidasi di backend
        },
        body: file
    });

    if (!uploadRes.ok) throw new Error('Gagal mengunggah file ke Object Storage');

    // 3. File berhasil diupload, kembalikan parameter path
    return { file_path, public_url };
}

// Export fungsi jika menggunakan module bundler
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { uploadProductImage };
}
