<?php

namespace App\Models;

/**
 * Backwards-compatible alias for the renamed Route model.
 *
 * Some legacy areas (and cached views) still reference App\Models\TransportRoute.
 * Instead of hunting every reference, expose this lightweight proxy so the ORM
 * continues to hit the same `routes` table.
 */
class TransportRoute extends Route
{
    /**
     * Explicit table declaration to avoid relying on pluralisation when the
     * parent model is extended in isolation.
     */
    protected $table = 'routes';
}

