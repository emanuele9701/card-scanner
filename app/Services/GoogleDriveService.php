<?php

namespace App\Services;

use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveToken;
use App\Models\PokemonCard;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
    protected $client;
    protected $driveService;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google Client with OAuth 2.0 and token management from database
     */
    protected function initializeClient()
    {
        $this->client = new Client();
        $this->client->setClientId(config('filesystems.disks.google.clientId'));
        $this->client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $this->client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $this->client->setScopes([Drive::DRIVE]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Load access token from database
        $tokenRecord = GoogleDriveToken::getGoogleDriveToken();

        if (!$tokenRecord) {
            // Fallback to env for initial setup
            $this->initializeFromEnv();
            return;
        }

        // Set token from database
        $this->client->setAccessToken($tokenRecord->toGoogleClientArray());

        // Check if token is expired or expiring soon
        if ($tokenRecord->isExpired() || $tokenRecord->isExpiringSoon()) {
            Log::info('Google Drive access token expired or expiring soon. Refreshing...', [
                'expires_at' => $tokenRecord->expires_at,
                'is_expired' => $tokenRecord->isExpired(),
                'is_expiring_soon' => $tokenRecord->isExpiringSoon(),
            ]);
            $this->refreshAccessToken();
        } else {
            Log::info('Google Drive access token is valid', [
                'expires_at' => $tokenRecord->expires_at,
                'expires_in_minutes' => $tokenRecord->expires_at->diffInMinutes(now()),
            ]);
        }

        // Initialize Drive Service
        $this->driveService = new Drive($this->client);
    }

    /**
     * Refresh the access token using the refresh token from database
     */
    protected function refreshAccessToken()
    {
        $tokenRecord = GoogleDriveToken::getGoogleDriveToken();

        if (!$tokenRecord || !$tokenRecord->refresh_token) {
            // Fallback to env if no database token
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            if (!$refreshToken) {
                throw new Exception('GOOGLE_DRIVE_REFRESH_TOKEN not found in database or .env');
            }
        } else {
            $refreshToken = $tokenRecord->refresh_token;
        }

        // Fetch new access token from Google
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $newTokenData = $this->client->getAccessToken();

        if (isset($newTokenData['access_token'])) {
            Log::info('Google Drive access token refreshed successfully.', [
                'expires_in' => $newTokenData['expires_in'] ?? 'unknown',
            ]);

            // Save to database
            if ($tokenRecord) {
                $tokenRecord->updateAccessToken(
                    $newTokenData['access_token'],
                    $newTokenData['expires_in'] ?? 3600
                );
            } else {
                // Create new record if doesn't exist
                GoogleDriveToken::createOrUpdateToken($newTokenData);
            }

            Log::info('Google Drive token saved to database', [
                'token_id' => $tokenRecord->id ?? 'new',
                'expires_at' => $tokenRecord->expires_at ?? 'unknown',
            ]);
        } else {
            throw new Exception('Failed to refresh Google Drive access token.');
        }
    }

    /**
     * Initialize from .env file (fallback for first-time setup)
     */
    protected function initializeFromEnv()
    {
        Log::info('No token in database. Initializing from .env...');

        $accessToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');
        $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

        if (!$accessToken || !$refreshToken) {
            throw new Exception('GOOGLE_DRIVE_ACCESS_TOKEN and GOOGLE_DRIVE_REFRESH_TOKEN must be set in .env for initial setup');
        }

        // Create token record in database
        $tokenData = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600, // Default 1 hour
            'token_type' => 'Bearer',
        ];

        $tokenRecord = GoogleDriveToken::createOrUpdateToken($tokenData);

        Log::info('Token imported from .env to database', [
            'token_id' => $tokenRecord->id,
            'expires_at' => $tokenRecord->expires_at,
        ]);

        // Set the token in the client
        $this->client->setAccessToken($tokenRecord->toGoogleClientArray());

        // Check if expired immediately
        if ($this->client->isAccessTokenExpired()) {
            Log::info('Imported token is expired. Refreshing...');
            $this->refreshAccessToken();
        }
    }

    /**
     * Upload a file from local storage to Google Drive
     * 
     * @param string $localPath Path del file nello storage locale
     * @param string $fileName Nome del file su Google Drive
     * @param int $userId ID dell'utente
     * @param int|null $pokemonCardId ID della carta (opzionale)
     * @param bool $makePublic Se rendere il file pubblico
     * @return GoogleDriveFile
     */
    public function uploadFile(
        string $localPath,
        string $fileName,
        int $userId,
        ?int $pokemonCardId = null,
        bool $makePublic = true
    ): GoogleDriveFile {
        try {
            Log::info('Starting Google Drive upload', [
                'local_path' => $localPath,
                'file_name' => $fileName,
                'user_id' => $userId,
                'pokemon_card_id' => $pokemonCardId,
                'make_public' => $makePublic,
            ]);

            // Create database record first
            $driveFile = GoogleDriveFile::create([
                'user_id' => $userId,
                'pokemon_card_id' => $pokemonCardId,
                'name' => $fileName,
                'status' => 'uploading',
                'drive_id' => 0,
                'mime_type' => 0,
            ]);

            // Get file content from storage (supporta sia 'public' che 'private')
            $disk = Storage::disk('public');
            if (!$disk->exists($localPath)) {
                // Prova con lo storage privato
                $disk = Storage::disk('local');
                if (!$disk->exists($localPath)) {
                    throw new Exception("File not found in storage: {$localPath}");
                }
            }

            $fileContent = $disk->get($localPath);
            $mimeType = $disk->mimeType($localPath);
            $fileSize = $disk->size($localPath);

            Log::info('File retrieved from storage', [
                'mime_type' => $mimeType,
                'size' => $fileSize,
            ]);

            // Create Drive File metadata
            $fileMetadata = new DriveFile([
                'name' => $fileName,
            ]);

            // Set parent folder if specified in env
            $parentFolder = env('GOOGLE_DRIVE_FOLDER');
            if ($parentFolder) {
                $folderId = $this->findOrCreateFolder($parentFolder);
                $fileMetadata->setParents([$folderId]);

                // Save parent folder ID
                $driveFile->update(['parent_folder_id' => $folderId]);
            }

            // Upload file to Google Drive
            $uploadedFile = $this->driveService->files->create($fileMetadata, [
                'data' => $fileContent,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, mimeType, size, createdTime, modifiedTime, webViewLink, webContentLink, thumbnailLink, parents, owners, shared'
            ]);

            Log::info('File uploaded to Google Drive', [
                'drive_id' => $uploadedFile->id,
                'name' => $uploadedFile->name,
            ]);

            // Make file public if requested
            if ($makePublic) {
                $this->makeFilePublic($uploadedFile->id);
            }

            // Update database record with all metadata
            $owners = [];
            if ($uploadedFile->owners) {
                foreach ($uploadedFile->owners as $owner) {
                    $owners[] = [
                        'email' => $owner->emailAddress ?? null,
                        'displayName' => $owner->displayName ?? null,
                    ];
                }
            }

            $driveFile->update([
                'drive_id' => $uploadedFile->id,
                'name' => $uploadedFile->name,
                'mime_type' => $uploadedFile->mimeType,
                'size' => $uploadedFile->size,
                'is_public' => $makePublic,
                'is_shared' => $uploadedFile->shared ?? false,
                'web_view_link' => $uploadedFile->webViewLink,
                'web_content_link' => $uploadedFile->webContentLink,
                'thumbnail_link' => $uploadedFile->thumbnailLink,
                'drive_created_at' => $uploadedFile->createdTime,
                'drive_modified_at' => $uploadedFile->modifiedTime,
                'owners' => $owners,
                'status' => 'uploaded',
            ]);

            Log::info('Google Drive file record updated successfully', [
                'record_id' => $driveFile->id,
                'drive_id' => $driveFile->drive_id,
            ]);

            return $driveFile;
        } catch (Exception $e) {
            Log::error('Failed to upload file to Google Drive', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update status to failed if record was created
            if (isset($driveFile) && $driveFile->exists) {
                $driveFile->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Make a file publicly accessible
     */
    public function makeFilePublic(string $driveId): void
    {
        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            $this->driveService->permissions->create($driveId, $permission);

            Log::info('File made public', ['drive_id' => $driveId]);
        } catch (Exception $e) {
            Log::error('Failed to make file public', [
                'drive_id' => $driveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get file information from Google Drive
     */
    public function getFileInfo(string $driveId): DriveFile
    {
        return $this->driveService->files->get($driveId, [
            'fields' => 'id, name, mimeType, size, createdTime, modifiedTime, webViewLink, webContentLink, thumbnailLink, parents, owners, shared'
        ]);
    }

    /**
     * Delete a file from Google Drive
     */
    public function deleteFile(string $driveId): void
    {
        try {
            $this->driveService->files->delete($driveId);

            Log::info('File deleted from Google Drive', ['drive_id' => $driveId]);

            // Update database record
            $driveFile = GoogleDriveFile::where('drive_id', $driveId)->first();
            if ($driveFile) {
                $driveFile->update(['status' => 'deleted']);
                $driveFile->delete(); // Soft delete
            }
        } catch (Exception $e) {
            Log::error('Failed to delete file from Google Drive', [
                'drive_id' => $driveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List all files in a specific folder
     */
    public function listFilesInFolder(string $folderId, int $pageSize = 100): array
    {
        $query = "'{$folderId}' in parents and trashed=false";

        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink)',
            'pageSize' => $pageSize,
            'orderBy' => 'name'
        ]);

        return $response->getFiles();
    }

    /**
     * Create a folder in Google Drive
     */
    public function createFolder(string $folderName, ?string $parentFolderId = null): DriveFile
    {
        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        if ($parentFolderId) {
            $fileMetadata->setParents([$parentFolderId]);
        }

        $folder = $this->driveService->files->create($fileMetadata, [
            'fields' => 'id, name, webViewLink'
        ]);

        Log::info('Folder created on Google Drive', [
            'folder_id' => $folder->id,
            'name' => $folder->name,
        ]);

        return $folder;
    }

    /**
     * Find a folder by name or create it if it doesn't exist
     */
    protected function findOrCreateFolder(string $folderName): string
    {
        // Search for folder
        $query = "mimeType='application/vnd.google-apps.folder' and name='{$folderName}' and trashed=false";

        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)',
            'pageSize' => 1
        ]);

        $files = $response->getFiles();

        if (count($files) > 0) {
            Log::info("Folder '{$folderName}' found", ['folder_id' => $files[0]->id]);
            return $files[0]->id;
        }

        // Create folder if not found
        Log::info("Folder '{$folderName}' not found. Creating...");

        $folder = $this->createFolder($folderName);

        return $folder->id;
    }

    /**
     * Get the public download URL for a file
     * This is a direct download link that works without authentication
     */
    public function getPublicDownloadUrl(string $driveId): string
    {
        return "https://drive.google.com/uc?export=download&id={$driveId}";
    }

    /**
     * Get the public view URL for a file (for images)
     */
    public function getPublicViewUrl(string $driveId): string
    {
        return "https://drive.google.com/uc?id={$driveId}";
    }

    /**
     * Download and stream a file from Google Drive
     * This streams the file directly without loading it entirely into memory
     * 
     * @param string $driveId Google Drive file ID
     * @return \Illuminate\Http\Response StreamedResponse
     */
    public function streamFile(string $driveId)
    {
        try {
            Log::info('Streaming file from Google Drive', ['drive_id' => $driveId]);

            // Get file metadata first
            $file = $this->driveService->files->get($driveId, [
                'fields' => 'id, name, mimeType, size'
            ]);

            // Get the file content as stream
            $response = $this->driveService->files->get($driveId, [
                'alt' => 'media'
            ]);

            $body = $response->getBody();
            $mimeType = $file->mimeType ?? 'application/octet-stream';
            $fileSize = $file->size ?? 0;

            Log::info('File stream ready', [
                'drive_id' => $driveId,
                'mime_type' => $mimeType,
                'size' => $fileSize
            ]);

            // Return streamed response
            return response()->stream(
                function () use ($body) {
                    // Stream in chunks to avoid memory issues
                    $chunkSize = 8192; // 8KB chunks

                    while (!$body->eof()) {
                        echo $body->read($chunkSize);
                        flush();
                    }

                    $body->close();
                },
                200,
                [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $fileSize,
                    'Cache-Control' => 'public, max-age=31536000',
                    'Content-Disposition' => 'inline',
                ]
            );
        } catch (Exception $e) {
            Log::error('Failed to stream file from Google Drive', [
                'drive_id' => $driveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get file content as string (for small files only)
     * WARNING: This loads the entire file into memory
     * 
     * @param string $driveId Google Drive file ID
     * @return string File content
     */
    public function getFileContent(string $driveId): string
    {
        try {
            Log::info('Downloading file content from Google Drive', ['drive_id' => $driveId]);

            $response = $this->driveService->files->get($driveId, [
                'alt' => 'media'
            ]);

            $content = $response->getBody()->getContents();

            Log::info('File downloaded', [
                'drive_id' => $driveId,
                'size' => strlen($content)
            ]);

            return $content;
        } catch (Exception $e) {
            Log::error('Failed to download file from Google Drive', [
                'drive_id' => $driveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
