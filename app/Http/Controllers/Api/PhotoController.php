<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhotoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $user = Auth::user();
        $tgId = $user->tg_id;

        if (!$tgId) {
            return response()->json(['error' => 'No Telegram ID linked'], 422);
        }

        $file = $request->file('photo');
        $filename = $tgId . '.jpg';
        $destPath = public_path('photos/' . $filename);

        // Load source image using GD
        $sourceImage = match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };

        if (!$sourceImage) {
            return response()->json(['error' => 'Failed to process image'], 422);
        }

        $srcW = imagesx($sourceImage);
        $srcH = imagesy($sourceImage);

        // Crop to square (center crop) then resize to 640x640
        $side = min($srcW, $srcH);
        $srcX = (int) (($srcW - $side) / 2);
        $srcY = (int) (($srcH - $side) / 2);

        $dest = imagecreatetruecolor(640, 640);
        imagecopyresampled($dest, $sourceImage, 0, 0, $srcX, $srcY, 640, 640, $side, $side);

        imagejpeg($dest, $destPath, 85);
        imagedestroy($sourceImage);
        imagedestroy($dest);

        // Update bot DB
        DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $tgId)
            ->update(['image' => $filename]);

        return response()->json([
            'success' => true,
            'image' => '/photos/' . $filename,
        ]);
    }
}
