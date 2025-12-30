<?php

namespace App\Services;

use App\Models\StorageBucket;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RcloneService
{
    private string $apiUrl;
    private int $timeout;

    public function __construct(?string $apiUrl = null, int $timeout = 30)
    {
        $this->apiUrl = $apiUrl ?? config('services.rclone.url', 'http://rclone:5572');
        $this->timeout = $timeout;
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->post("{$this->apiUrl}/core/version", (object) []);
            return $response->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function testConnection(StorageBucket $bucket): bool
    {
        try {
            $fs = $this->buildFs($bucket);
            Log::info('Rclone connection test', [
                'bucket_id' => $bucket->id,
                'provider' => $bucket->provider,
                'fs' => $fs,
            ]);
            
            $response = Http::timeout(15)
                ->post("{$this->apiUrl}/operations/list", [
                    'fs' => $fs,
                    'remote' => '',
                    'opt' => ['maxDepth' => 1],
                ]);
            
            if (!$response->successful()) {
                Log::warning('Rclone connection test failed', [
                    'bucket_id' => $bucket->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            Log::warning('Rclone connection test exception', [
                'bucket_id' => $bucket->id,
                'provider' => $bucket->provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function listFiles(StorageBucket $bucket, string $path = '', int $limit = 100): array
    {
        $response = $this->call('operations/list', [
            'fs' => $this->buildFs($bucket),
            'remote' => $path,
            'opt' => [
                'maxDepth' => 1,
            ],
        ]);

        $list = $response['list'] ?? [];
        
        if ($limit > 0 && count($list) > $limit) {
            $list = array_slice($list, 0, $limit);
        }

        return $list;
    }

    public function readFile(StorageBucket $bucket, string $path): string
    {
        $fs = $this->buildFs($bucket);
        
        Log::debug('Rclone readFile via cat', [
            'bucket_id' => $bucket->id,
            'path' => $path,
        ]);
        
        // Use operations/cat which streams file content directly
        $response = Http::timeout($this->timeout)
            ->post("{$this->apiUrl}/operations/cat", [
                'fs' => $fs,
                'remote' => $path,
            ]);

        if (!$response->successful()) {
            Log::warning('Rclone readFile cat failed', [
                'bucket_id' => $bucket->id,
                'path' => $path,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new Exception("Failed to read file: {$response->body()}");
        }

        return $response->body();
    }

    public function writeFile(StorageBucket $bucket, string $path, string $contents): bool
    {
        $fs = $this->buildFs($bucket);
        $dir = dirname($path);
        if ($dir === '.') {
            $dir = '';
        }
        
        $url = "{$this->apiUrl}/operations/uploadfile?" . http_build_query([
            'fs' => $fs,
            'remote' => $dir,
        ]);

        $response = Http::timeout($this->timeout)
            ->attach('file', $contents, basename($path))
            ->post($url);

        if (!$response->successful()) {
            Log::error('Rclone uploadfile failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'fs' => $fs,
                'path' => $path,
            ]);
        }

        return $response->successful();
    }

    public function copyFile(
        StorageBucket $srcBucket,
        string $srcPath,
        StorageBucket $dstBucket,
        string $dstPath
    ): bool {
        $response = $this->call('operations/copyfile', [
            'srcFs' => $this->buildFs($srcBucket),
            'srcRemote' => $srcPath,
            'dstFs' => $this->buildFs($dstBucket),
            'dstRemote' => $dstPath,
        ]);

        return true;
    }

    public function moveFile(
        StorageBucket $srcBucket,
        string $srcPath,
        StorageBucket $dstBucket,
        string $dstPath
    ): bool {
        $response = $this->call('operations/movefile', [
            'srcFs' => $this->buildFs($srcBucket),
            'srcRemote' => $srcPath,
            'dstFs' => $this->buildFs($dstBucket),
            'dstRemote' => $dstPath,
        ]);

        return true;
    }

    public function deleteFile(StorageBucket $bucket, string $path): bool
    {
        $response = $this->call('operations/deletefile', [
            'fs' => $this->buildFs($bucket),
            'remote' => $path,
        ]);

        return true;
    }

    public function getFileInfo(StorageBucket $bucket, string $path): ?array
    {
        try {
            $response = $this->call('operations/stat', [
                'fs' => $this->buildFs($bucket),
                'remote' => $path,
            ]);

            return $response['item'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function syncAsync(
        StorageBucket $srcBucket,
        StorageBucket $dstBucket,
        string $srcPath = '',
        string $dstPath = ''
    ): string {
        $response = $this->call('sync/copy', [
            'srcFs' => $this->buildFs($srcBucket) . ($srcPath ? "/{$srcPath}" : ''),
            'dstFs' => $this->buildFs($dstBucket) . ($dstPath ? "/{$dstPath}" : ''),
            '_async' => true,
        ]);

        return $response['jobid'] ?? '';
    }

    public function getJobStatus(string $jobId): array
    {
        $response = $this->call('job/status', [
            'jobid' => (int) $jobId,
        ]);

        return $response;
    }

    public function stopJob(string $jobId): bool
    {
        try {
            $this->call('job/stop', [
                'jobid' => (int) $jobId,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTransferStats(): array
    {
        $response = $this->call('core/stats');
        return $response;
    }

    private function call(string $endpoint, array $params = []): array
    {
        $body = empty($params) ? (object) [] : $params;
        
        $response = Http::timeout($this->timeout)
            ->post("{$this->apiUrl}/{$endpoint}", $body);

        if (!$response->successful()) {
            $error = $response->json('error') ?? $response->body();
            throw new Exception("Rclone API error: {$error}");
        }

        return $response->json() ?? [];
    }

    private function buildFs(StorageBucket $bucket): string
    {
        $config = $bucket->buildRcloneConfig();
        $type = $config['type'] ?? 's3';
        unset($config['type']);
        
        $bucketName = $config['bucket'] ?? '';
        unset($config['bucket']);
        
        $params = [];
        foreach ($config as $key => $value) {
            // SFTP passwords must be obscured for rclone
            if ($key === 'pass' && !empty($value)) {
                $value = $this->obscurePassword((string) $value);
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            if (preg_match('/[,:=\s]/', (string) $value)) {
                $value = '"' . addslashes((string) $value) . '"';
            }
            $params[] = "{$key}={$value}";
        }
        
        $paramString = implode(',', $params);
        
        return ":{$type},{$paramString}:{$bucketName}";
    }

    private function obscurePassword(string $password): string
    {
        try {
            $response = Http::timeout(5)
                ->post("{$this->apiUrl}/core/obscure", [
                    'clear' => $password,
                ]);

            if ($response->successful()) {
                return $response->json('obscured') ?? $password;
            }

            Log::warning('Failed to obscure password via rclone API', [
                'status' => $response->status(),
            ]);
            return $password;
        } catch (Exception $e) {
            Log::warning('Exception obscuring password', [
                'error' => $e->getMessage(),
            ]);
            return $password;
        }
    }
}
