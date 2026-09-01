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
            'mime_type' => 'required|in:image/jpeg,image/png,|/webp',
            'extension' => 'required|in:jpg,jpeg,png,webp',
        ]);

        $filePath = 'products/' . date('Y/m/') . Str::uuid() . '.' . $request->extension;
        $client = Storage::disk('s3')->getClient();
        
        $command = $client->getCommand('PutObject', [
            'Bucket'      => env('AWS_BUCKET'),
            'Key'         => $filePath,
            'ContentType' => $request->mime_type,
        ]);

        // URL bertanda tangan, valid untuk 5 menit
        $signedRequest = $client->createPresignedRequest($command, '+5 minutes');

        return response()->json([
            'upload_url' => (string) $signedRequest->getUri(),
            'file_path'  => $filePath,
            'public_url' => env('AWS_URL') . '/' . $filePath,
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
