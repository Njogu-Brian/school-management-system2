<?php

namespace App\Http\Middleware;

use App\Models\SchoolRegistry;
use App\Services\TenantConnectionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For shared ERP installs: first path segment is the school slug.
 * Skips reserved segments. Requires CONTROL_DB_* + schools_registry credentials.
 */
class ResolveTenantFromPath
{
    public function __construct(private TenantConnectionManager $connections)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (config('edulynk.is_control_plane')) {
            return $next($request);
        }

        // Dedicated single-tenant deploy: TENANT_SLUG fixed in .env
        $forced = env('TENANT_SLUG');
        if (filled($forced)) {
            return $this->bindSlug((string) $forced, $request, $next);
        }

        if (! filter_var(env('EDULYNK_PATH_TENANCY', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $segment = $request->segment(1);
        if (! $segment) {
            return $next($request);
        }

        $reserved = config('edulynk.reserved_path_segments', []);
        if (in_array(strtolower($segment), $reserved, true)) {
            return $next($request);
        }

        return $this->bindSlug($segment, $request, $next);
    }

    private function bindSlug(string $slug, Request $request, Closure $next): Response
    {
        $school = SchoolRegistry::query()->where('slug', $slug)->first();
        if (! $school) {
            abort(404, 'School not found.');
        }

        if ($school->status === SchoolRegistry::STATUS_SUSPENDED
            || $school->billing_status === SchoolRegistry::BILLING_SUSPENDED) {
            abort(403, 'This school is suspended. Contact EduLynk support.');
        }

        if (! $school->isActive()) {
            abort(403, 'This school is not active.');
        }

        $this->connections->configureFromSchool($school);

        $base = rtrim((string) config('edulynk.tenant_base_url'), '/').'/'.$school->slug;
        config([
            'app.url' => $base,
            'app.asset_url' => $base,
            'session.path' => '/'.$school->slug,
        ]);

        app()->instance('currentSchoolRegistry', $school);
        $request->attributes->set('tenant_slug', $school->slug);
        $request->attributes->set('tenant_school', $school);

        return $next($request);
    }
}
