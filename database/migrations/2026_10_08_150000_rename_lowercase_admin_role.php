<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $lowercase = DB::table('roles')->where('guard_name', 'web')->whereRaw('BINARY name = ?', ['admin'])->first();
        $title = DB::table('roles')->where('guard_name', 'web')->whereRaw('BINARY name = ?', ['Admin'])->first();

        if ($lowercase && $title && (int) $lowercase->id === (int) $title->id) {
            return;
        }

        if ($lowercase && ! $title) {
            DB::table('roles')->where('id', $lowercase->id)->update(['name' => 'Admin']);
        } elseif ($lowercase && $title) {
            $assignments = DB::table('model_has_roles')->where('role_id', $lowercase->id)->get();
            foreach ($assignments as $assignment) {
                $already = DB::table('model_has_roles')
                    ->where('role_id', $title->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->exists();

                if ($already) {
                    DB::table('model_has_roles')
                        ->where('role_id', $lowercase->id)
                        ->where('model_type', $assignment->model_type)
                        ->where('model_id', $assignment->model_id)
                        ->delete();
                } else {
                    DB::table('model_has_roles')
                        ->where('role_id', $lowercase->id)
                        ->where('model_type', $assignment->model_type)
                        ->where('model_id', $assignment->model_id)
                        ->update(['role_id' => $title->id]);
                }
            }

            DB::table('roles')->where('id', $lowercase->id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $title = DB::table('roles')->where('name', 'Admin')->where('guard_name', 'web')->first();
        $lowercase = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->first();

        if ($title && ! $lowercase) {
            DB::table('roles')->where('id', $title->id)->update(['name' => 'admin']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
