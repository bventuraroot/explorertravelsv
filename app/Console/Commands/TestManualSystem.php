<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ManualService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestManualSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:manual-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the manual system functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Probando el sistema de manuales...');

        // Test 1: Verificar servicio de manuales
        $this->info('📚 Probando ManualService...');
        $manualService = new ManualService();
        $manuals = $manualService->getAllManuals();

        $this->info("✅ Manuales encontrados: " . count($manuals));
        foreach ($manuals as $module => $moduleManuals) {
            $this->line("   - {$module}: " . count($moduleManuals) . " manual(es)");
        }

        // Test 2: Verificar permisos
        $this->info('🔐 Verificando permisos...');
        $permissions = Permission::where('name', 'like', '%manual%')->get();
        $this->info("✅ Permisos de manuales: " . $permissions->count());
        foreach ($permissions as $permission) {
            $this->line("   - {$permission->name}");
        }

        // Test 3: Verificar roles
        $this->info('👥 Verificando roles...');
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = $adminRole->permissions()->where('name', 'like', '%manual%')->get();
            $this->info("✅ Permisos del rol admin: " . $adminPermissions->count());
        } else {
            $this->warn("⚠️  Rol admin no encontrado");
        }

        // Test 4: Verificar archivos de manuales
        $this->info('📁 Verificando archivos de manuales...');
        $manualFiles = $manualService->getManualFiles();
        $this->info("✅ Archivos de manuales: " . count($manualFiles));
        foreach ($manualFiles as $file) {
            $this->line("   - {$file['module_name']}/{$file['filename']}.md");
        }

        $this->info('🎉 Sistema de manuales funcionando correctamente!');

        return 0;
    }
}
