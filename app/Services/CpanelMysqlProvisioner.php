<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Create MySQL databases/users on cPanel via UAPI.
 * Falls back is documented: operator can paste pre-created credentials.
 */
class CpanelMysqlProvisioner
{
    public function isConfigured(): bool
    {
        $c = config('edulynk.cpanel');

        return filled($c['host'] ?? null)
            && filled($c['user'] ?? null)
            && filled($c['api_token'] ?? null);
    }

    /**
     * @return array{db_name:string,db_username:string,db_password:string,db_host:string,db_port:int}
     */
    public function createDatabase(string $logicalName, ?string $password = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'cPanel UAPI is not configured. Set CPANEL_HOST, CPANEL_USER, CPANEL_API_TOKEN '.
                'or provision the MySQL database manually and pass credentials to the wizard.'
            );
        }

        $prefix = (string) config('edulynk.cpanel.db_prefix', '');
        $safe = preg_replace('/[^a-z0-9_]/', '', strtolower($logicalName)) ?: 'school';
        $dbName = $prefix.$safe;
        $dbUser = $prefix.$safe;
        // cPanel usernames are often limited length
        if (strlen($dbUser) > 16) {
            $dbUser = substr($dbUser, 0, 16);
        }
        $password = $password ?: $this->randomPassword();

        $this->uapi('Mysql', 'create_database', ['name' => $dbName]);
        $this->uapi('Mysql', 'create_user', [
            'name' => $dbUser,
            'password' => $password,
        ]);
        $this->uapi('Mysql', 'set_privileges_on_database', [
            'user' => $dbUser,
            'database' => $dbName,
            'privileges' => 'ALL PRIVILEGES',
        ]);

        return [
            'db_name' => $dbName,
            'db_username' => $dbUser,
            'db_password' => $password,
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
        ];
    }

    /**
     * @param  array<string, scalar>  $params
     * @return array<string, mixed>
     */
    public function uapi(string $module, string $function, array $params = []): array
    {
        $host = rtrim((string) config('edulynk.cpanel.host'), '/');
        $user = (string) config('edulynk.cpanel.user');
        $token = (string) config('edulynk.cpanel.api_token');
        $port = (int) config('edulynk.cpanel.port', 2083);

        $url = "https://{$host}:{$port}/execute/{$module}/{$function}";

        $response = Http::withHeaders([
            'Authorization' => "cpanel {$user}:{$token}",
        ])->asForm()->timeout(60)->post($url, $params);

        if (! $response->successful()) {
            Log::error('cPanel UAPI HTTP failure', [
                'module' => $module,
                'function' => $function,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("cPanel UAPI HTTP {$response->status()} for {$module}::{$function}");
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException("cPanel UAPI returned non-JSON for {$module}::{$function}");
        }

        $status = (int) ($json['status'] ?? 0);
        if ($status !== 1) {
            $errors = $json['errors'] ?? [$json['statusmsg'] ?? 'Unknown UAPI error'];
            $msg = is_array($errors) ? implode('; ', $errors) : (string) $errors;
            Log::error('cPanel UAPI logical failure', compact('module', 'function', 'msg', 'json'));
            throw new RuntimeException("cPanel UAPI {$module}::{$function}: {$msg}");
        }

        return $json;
    }

    public function wirePublicHtmlSlug(string $slug): void
    {
        $publicHtml = (string) config('edulynk.cpanel.public_html');
        $erpRoot = (string) config('edulynk.cpanel.erp_root');
        if ($publicHtml === '' || $erpRoot === '') {
            return;
        }

        $target = rtrim($publicHtml, '/\\').DIRECTORY_SEPARATOR.$slug;
        if (! is_dir($target)) {
            if (! mkdir($target, 0755, true) && ! is_dir($target)) {
                throw new RuntimeException("Unable to create {$target}");
            }
        }

        $publicSrc = rtrim($erpRoot, '/\\').DIRECTORY_SEPARATOR.'public';
        foreach (['.htaccess'] as $file) {
            $src = $publicSrc.DIRECTORY_SEPARATOR.$file;
            if (is_file($src)) {
                $dest = $target.DIRECTORY_SEPARATOR.$file;
                $contents = file_get_contents($src);
                if ($contents !== false) {
                    $contents = preg_replace('/RewriteBase\s+\S+/', 'RewriteBase /'.$slug.'/', $contents)
                        ?? $contents;
                    if (! str_contains($contents, 'RewriteBase')) {
                        $contents = str_replace(
                            'RewriteEngine On',
                            "RewriteEngine On\n    RewriteBase /{$slug}/",
                            $contents
                        );
                    }
                    file_put_contents($dest, $contents);
                }
            }
        }

        $rel = $this->relativePath($target, $erpRoot);
        $index = <<<PHP
<?php

use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(\$maintenance = __DIR__.'/{$rel}/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require __DIR__.'/{$rel}/vendor/autoload.php';

\$app = require_once __DIR__.'/{$rel}/bootstrap/app.php';

\$app->handleRequest(Request::capture());

PHP;
        file_put_contents($target.DIRECTORY_SEPARATOR.'index.php', $index);
    }

    private function relativePath(string $fromDir, string $toDir): string
    {
        $from = explode(DIRECTORY_SEPARATOR, realpath($fromDir) ?: $fromDir);
        $to = explode(DIRECTORY_SEPARATOR, realpath($toDir) ?: $toDir);
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        $up = str_repeat('../', count($from));

        return str_replace('\\', '/', $up.implode('/', $to));
    }

    private function randomPassword(int $length = 24): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $out;
    }
}
