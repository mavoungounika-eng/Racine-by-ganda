<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait pour la gestion des uploads d'images
 * 
 * Fournit des méthodes réutilisables pour gérer les uploads d'images
 * dans les contrôleurs et services
 */
trait HandlesImageUploads
{
    /**
     * Upload une image et retourne le chemin.
     * 
     * @param UploadedFile|null $file Fichier image à uploader
     * @param string $directory Répertoire de destination (ex: 'products', 'users')
     * @param string|null $oldPath Chemin de l'ancienne image à supprimer (optionnel)
     * @param string $disk Disque de stockage (par défaut: 'public')
     * @return string|null Chemin de l'image uploadée ou null si aucun fichier
     */
    protected function uploadImage(
        ?UploadedFile $file,
        string $directory = 'uploads',
        ?string $oldPath = null,
        string $disk = 'public'
    ): ?string {
        if (!$file || !$file->isValid()) {
            return null;
        }

        // Supprimer l'ancienne image si elle existe
        if ($oldPath) {
            $this->deleteImage($oldPath, $disk);
        }

        // Générer un nom de fichier unique
        $filename = $this->generateUniqueFilename($file, $directory);

        // Uploader le fichier
        $path = $file->storeAs($directory, $filename, $disk);

        return $path;
    }

    /**
     * Supprimer une image du stockage.
     * 
     * @param string $path Chemin de l'image à supprimer
     * @param string $disk Disque de stockage (par défaut: 'public')
     * @return bool True si supprimé avec succès, false sinon
     */
    protected function deleteImage(string $path, string $disk = 'public'): bool
    {
        if (empty($path)) {
            return false;
        }

        // Si le chemin contient déjà le répertoire, utiliser tel quel
        // Sinon, construire le chemin complet
        $fullPath = str_contains($path, '/') ? $path : "products/{$path}";

        if (Storage::disk($disk)->exists($fullPath)) {
            return Storage::disk($disk)->delete($fullPath);
        }

        return false;
    }

    /**
     * Générer un nom de fichier unique.
     * 
     * @param UploadedFile $file Fichier uploadé
     * @param string $directory Répertoire de destination
     * @return string Nom de fichier unique
     */
    protected function generateUniqueFilename(UploadedFile $file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($basename);
        $timestamp = now()->timestamp;
        $random = Str::random(8);

        return "{$slug}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Valider une image selon les règles spécifiées.
     * 
     * @param UploadedFile|null $file Fichier à valider
     * @param int $maxSize Taille maximale en Ko (par défaut: 4096 = 4MB)
     * @param array $allowedMimes Types MIME autorisés (par défaut: jpg, jpeg, png, webp)
     * @return bool True si valide, false sinon
     */
    protected function validateImage(
        ?UploadedFile $file,
        int $maxSize = 4096,
        array $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
    ): bool {
        if (!$file) {
            return true; // Image optionnelle
        }

        if (!$file->isValid()) {
            return false;
        }

        // Vérifier le type MIME
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // Vérifier la taille (en Ko)
        $fileSizeInKb = $file->getSize() / 1024;
        if ($fileSizeInKb > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Redimensionner une image (nécessite l'extension GD ou Imagick).
     * 
     * @param string $path Chemin de l'image
     * @param int $width Largeur maximale
     * @param int $height Hauteur maximale
     * @param string $disk Disque de stockage
     * @return bool True si redimensionné avec succès
     */
    protected function resizeImage(
        string $path,
        int $width = 1200,
        int $height = 1200,
        string $disk = 'public'
    ): bool {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            return false;
        }

        $fullPath = Storage::disk($disk)->path($path);

        if (!file_exists($fullPath)) {
            return false;
        }

        // Implémentation basique avec GD
        if (extension_loaded('gd')) {
            $imageInfo = getimagesize($fullPath);
            if (!$imageInfo) {
                return false;
            }

            $mime = $imageInfo['mime'];
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];

            // Calculer les nouvelles dimensions en conservant le ratio
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = (int) ($originalWidth * $ratio);
            $newHeight = (int) ($originalHeight * $ratio);

            // Créer l'image source selon le type
            switch ($mime) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($fullPath);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($fullPath);
                    break;
                default:
                    return false;
            }

            if (!$source) {
                return false;
            }

            // Créer l'image redimensionnée
            $destination = imagecreatetruecolor($newWidth, $newHeight);

            // Préserver la transparence pour PNG
            if ($mime === 'image/png') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
            }

            // Redimensionner
            imagecopyresampled(
                $destination,
                $source,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            // Sauvegarder selon le type
            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($destination, $fullPath, 85);
                    break;
                case 'image/png':
                    imagepng($destination, $fullPath, 9);
                    break;
                case 'image/webp':
                    imagewebp($destination, $fullPath, 85);
                    break;
            }

            imagedestroy($source);
            imagedestroy($destination);

            return true;
        }

        return false;
    }
}

