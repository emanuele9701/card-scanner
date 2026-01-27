<?php

namespace App\Console\Commands;

use App\Models\PokemonCard;
use App\Services\GoogleDriveService;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Exception;

class DriveTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:drive-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive operations with token management';

    /**
     * Google Client instance
     */
    protected $client;

    /**
     * Google Drive Service instance
     */
    protected $driveService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gdriveClient = new GoogleDriveService();
        try {

            $cardsToUpload = PokemonCard::with('driveFile')->get();

            foreach ($cardsToUpload as $card) {
                if (!$card->driveFile) {
                    // Procedo al caricamento su drive
                    $d = $gdriveClient->uploadFile($card->storage_path, basename($card->storage_path), $card->user->id, $card->id);

                    Storage::disk('public')->delete($card->storage_path);
                }
            }

            return 0;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Initialize Google Client with OAuth 2.0 and token management
     */
    protected function initializeClient()
    {
        $this->info('Initializing Google Drive client...');

        $this->client = new Client();
        $this->client->setClientId(config('filesystems.disks.google.clientId'));
        $this->client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $this->client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $this->client->setScopes([Drive::DRIVE]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Load access token from env
        $accessToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');

        if ($accessToken) {
            $this->client->setAccessToken($accessToken);

            // Check if token is expired
            if ($this->client->isAccessTokenExpired()) {
                $this->warn('Access token expired. Refreshing...');
                $this->refreshAccessToken();
            } else {
                $this->info('Access token is valid.');
            }
        } else {
            $this->error('No access token found in .env file.');
            throw new Exception('GOOGLE_DRIVE_ACCESS_TOKEN not set in .env');
        }

        // Initialize Drive Service
        $this->driveService = new Drive($this->client);
        $this->info('Google Drive client initialized successfully.');
    }

    /**
     * Refresh the access token using the refresh token
     */
    protected function refreshAccessToken()
    {
        $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

        if (!$refreshToken) {
            throw new Exception('GOOGLE_DRIVE_REFRESH_TOKEN not set in .env');
        }

        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $newAccessToken = $this->client->getAccessToken();

        if (isset($newAccessToken['access_token'])) {
            $this->info('Access token refreshed successfully.');

            // Update .env file with new access token
            $this->updateEnvFile('GOOGLE_DRIVE_ACCESS_TOKEN', $newAccessToken['access_token']);

            $this->info('New access token saved to .env file.');
        } else {
            throw new Exception('Failed to refresh access token.');
        }
    }

    /**
     * Update .env file with new value
     */
    protected function updateEnvFile($key, $value)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            throw new Exception('.env file not found');
        }

        $envContent = file_get_contents($envPath);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            // Update existing key
            $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
        } else {
            // Add new key
            $envContent .= "\n{$key}={$value}";
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Upload a file from private storage to Google Drive
     * 
     * Usage: php artisan app:drive-test upload --file=path/to/file.jpg --name="My File"
     */
    protected function uploadFile()
    {
        $filePath = $this->option('file');
        $fileName = $this->option('name');

        if (!$filePath) {
            $this->error('Please provide a file path using --file option');
            return;
        }

        // Check if file exists in private storage
        if (!Storage::exists($filePath)) {
            $this->error("File not found in storage: {$filePath}");
            return;
        }

        $this->info("Uploading file: {$filePath}");

        // Get file content from private storage
        $fileContent = Storage::get($filePath);
        $mimeType = Storage::mimeType($filePath);

        // Use provided name or get from path
        if (!$fileName) {
            $fileName = basename($filePath);
        }

        // Create Drive File metadata
        $fileMetadata = new DriveFile([
            'name' => $fileName,
        ]);

        // Set parent folder if specified in env
        $parentFolder = env('GOOGLE_DRIVE_FOLDER');
        if ($parentFolder) {
            // First, find or create the folder
            $folderId = $this->findOrCreateFolder($parentFolder);
            $fileMetadata->setParents([$folderId]);
        }

        // Upload file
        $file = $this->driveService->files->create($fileMetadata, [
            'data' => $fileContent,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, name, mimeType, size, createdTime, webViewLink'
        ]);

        $this->info('File uploaded successfully!');
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $file->id],
                ['Name', $file->name],
                ['MIME Type', $file->mimeType],
                ['Size', $this->formatBytes($file->size)],
                ['Created', $file->createdTime],
                ['Web Link', $file->webViewLink],
            ]
        );

        return $file->id;
    }

    /**
     * Get information about a specific file
     * 
     * Usage: php artisan app:drive-test get-info --id=FILE_ID
     */
    protected function getFileInfo()
    {
        $fileId = $this->option('id');

        if (!$fileId) {
            $this->error('Please provide a file ID using --id option');
            return;
        }

        $this->info("Getting file info for ID: {$fileId}");

        try {
            $file = $this->driveService->files->get($fileId, [
                'fields' => 'id, name, mimeType, size, createdTime, modifiedTime, webViewLink, webContentLink, parents, owners, shared'
            ]);

            $this->info('File information retrieved successfully!');

            $tableData = [
                ['ID', $file->id],
                ['Name', $file->name],
                ['MIME Type', $file->mimeType],
                ['Size', $file->size ? $this->formatBytes($file->size) : 'N/A (folder)'],
                ['Created', $file->createdTime],
                ['Modified', $file->modifiedTime],
                ['Shared', $file->shared ? 'Yes' : 'No'],
                ['Web View Link', $file->webViewLink ?? 'N/A'],
                ['Download Link', $file->webContentLink ?? 'N/A'],
            ];

            if ($file->parents) {
                $tableData[] = ['Parent Folders', implode(', ', $file->parents)];
            }

            if ($file->owners) {
                $owners = array_map(fn($owner) => $owner->emailAddress, $file->owners);
                $tableData[] = ['Owners', implode(', ', $owners)];
            }

            $this->table(['Property', 'Value'], $tableData);
        } catch (Exception $e) {
            $this->error("Failed to get file info: " . $e->getMessage());
        }
    }

    /**
     * List all files in a specific folder
     * 
     * Usage: php artisan app:drive-test list-files --folder=FOLDER_ID
     */
    protected function listFiles()
    {
        $folderId = $this->option('folder');

        if (!$folderId) {
            $this->error('Please provide a folder ID using --folder option');
            return;
        }

        $this->info("Listing files in folder: {$folderId}");

        try {
            $query = "'{$folderId}' in parents and trashed=false";

            $response = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime)',
                'pageSize' => 100,
                'orderBy' => 'name'
            ]);

            $files = $response->getFiles();

            if (count($files) === 0) {
                $this->warn('No files found in this folder.');
                return;
            }

            $this->info(sprintf('Found %d file(s):', count($files)));

            $tableData = [];
            foreach ($files as $file) {
                $tableData[] = [
                    $file->id,
                    $file->name,
                    $this->getFileType($file->mimeType),
                    $file->size ? $this->formatBytes($file->size) : 'N/A',
                    $file->modifiedTime,
                ];
            }

            $this->table(['ID', 'Name', 'Type', 'Size', 'Modified'], $tableData);
        } catch (Exception $e) {
            $this->error("Failed to list files: " . $e->getMessage());
        }
    }

    /**
     * Create a new folder in Google Drive
     * 
     * Usage: php artisan app:drive-test create-folder --name="My Folder" [--folder=PARENT_FOLDER_ID]
     */
    protected function createFolder()
    {
        $folderName = $this->option('name');

        if (!$folderName) {
            $this->error('Please provide a folder name using --name option');
            return;
        }

        $this->info("Creating folder: {$folderName}");

        try {
            $fileMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            // Set parent folder if specified
            $parentFolderId = $this->option('folder');
            if ($parentFolderId) {
                $fileMetadata->setParents([$parentFolderId]);
            }

            $folder = $this->driveService->files->create($fileMetadata, [
                'fields' => 'id, name, webViewLink'
            ]);

            $this->info('Folder created successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['ID', $folder->id],
                    ['Name', $folder->name],
                    ['Web Link', $folder->webViewLink],
                ]
            );

            return $folder->id;
        } catch (Exception $e) {
            $this->error("Failed to create folder: " . $e->getMessage());
        }
    }

    /**
     * Delete a file or folder from Google Drive
     * 
     * Usage: php artisan app:drive-test delete --id=FILE_OR_FOLDER_ID
     */
    protected function deleteItem()
    {
        $itemId = $this->option('id');

        if (!$itemId) {
            $this->error('Please provide a file or folder ID using --id option');
            return;
        }

        // Get item info first
        try {
            $item = $this->driveService->files->get($itemId, [
                'fields' => 'id, name, mimeType'
            ]);

            $itemType = $this->getFileType($item->mimeType);

            $this->warn("About to delete {$itemType}: {$item->name} (ID: {$itemId})");

            if (!$this->confirm('Are you sure you want to delete this item?')) {
                $this->info('Deletion cancelled.');
                return;
            }

            $this->info("Deleting {$itemType}: {$item->name}");

            $this->driveService->files->delete($itemId);

            $this->info("{$itemType} deleted successfully!");
        } catch (Exception $e) {
            $this->error("Failed to delete item: " . $e->getMessage());
        }
    }

    /**
     * Find a folder by name or create it if it doesn't exist
     */
    protected function findOrCreateFolder($folderName)
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
            $this->info("Folder '{$folderName}' found with ID: {$files[0]->id}");
            return $files[0]->id;
        }

        // Create folder if not found
        $this->info("Folder '{$folderName}' not found. Creating...");

        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        $folder = $this->driveService->files->create($fileMetadata, [
            'fields' => 'id'
        ]);

        $this->info("Folder created with ID: {$folder->id}");

        return $folder->id;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get human readable file type from MIME type
     */
    protected function getFileType($mimeType)
    {
        if ($mimeType === 'application/vnd.google-apps.folder') {
            return 'Folder';
        }

        $types = [
            'image/' => 'Image',
            'video/' => 'Video',
            'audio/' => 'Audio',
            'application/pdf' => 'PDF',
            'application/zip' => 'Archive',
            'text/' => 'Text',
        ];

        foreach ($types as $mime => $type) {
            if (strpos($mimeType, $mime) === 0 || $mimeType === $mime) {
                return $type;
            }
        }

        return 'File';
    }
}
