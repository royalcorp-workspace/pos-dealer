<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function getUploadUrl(Request $request)
    {
        // Whitelist validasi tipe file 
        $request->validate([
            'mime_type' => 'required|string',
            'extension' => 'required|string',
            'folder'    => 'nullable|string|max:50',
        ]);

        $rawMime = strtolower(trim(explode(';', (string) $request->mime_type)[0]));
        $rawExt = strtolower(ltrim(trim((string) $request->extension), '.'));

        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
            'image/avif',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'ico'];

        if (!in_array($rawMime, $allowedMimes) || !in_array($rawExt, $allowedExtensions)) {
            return response()->json([
                'message' => 'Format file tidak didukung.',
                'errors'  => ['mime_type' => ['Format file tidak valid.']]
            ], 422);
        }

        // Sanitize and normalize folder (default: products)
        $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $request->input('folder', 'products'));
        if (empty($folder)) {
            $folder = 'products';
        }

        $filePath = $folder . '/' . date('Y/m/') . Str::uuid() . '.' . $rawExt;
        $bucket = config('filesystems.disks.s3.bucket') ?? env('AWS_BUCKET');
        $client = Storage::disk('s3')->getClient();
        
        $command = $client->getCommand('PutObject', [
            'Bucket'      => $bucket,
            'Key'         => $filePath,
            'ContentType' => $rawMime,
        ]);

        // URL bertanda tangan, valid untuk 5 menit
        $signedRequest = $client->createPresignedRequest($command, '+5 minutes');
        $s3Url = rtrim((string) (config('filesystems.disks.s3.url') ?? env('AWS_URL', '')), '/');
        $publicUrl = $s3Url ? ($s3Url . '/' . $filePath) : $filePath;

        return response()->json([
            'upload_url' => (string) $signedRequest->getUri(),
            'file_path'  => $filePath,
            'public_url' => $publicUrl,
        ]);
    }

    public function destroy($id)
    {
        // Contoh implementasi untuk Web (pos-dealer), model Product bisa diganti sesuai kebutuhan
        // $product = \App\Models\Product::findOrFail($id);

        // Menghapus physical file dari RustFS melalui API S3 backend
        // if ($product->|_path && Storage::disk('s3')->exists($product->|_path)) {
        //     Storage::disk('s3')->delete($product->|_path);
        // }
        
        // $product->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Produk dan gambar berhasil dihapus (Mock)'
        ]);
    }
}
