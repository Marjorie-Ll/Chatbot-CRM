<?php

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Deshabilitar verificación de claves foráneas temporalmente
        Schema::disableForeignKeyConstraints();

        $this->command->info('🌱 Iniciando seeders del Chatbot CRM...');

        // Limpiar tablas existentes (opcional)
        if (app()->environment('local', 'testing')) {
            $this->command->warn('⚠️  Limpiando datos existentes...');
            
            DB::table('personal_access_tokens')->delete();
            DB::table('documents')->delete();
            // Nota: No eliminamos users para mantener datos existentes
        }

        // ⭐ MÓDULO 1: Usuarios y autenticación
        $this->command->info('👥 Creando usuarios...');
        $this->call([
            UserSeeder::class,
        ]);

        // ⭐ MÓDULO 1: Documentos de prueba (opcional)
        if (app()->environment('local', 'testing')) {
            $this->command->info('📄 Creando documentos de prueba...');
            $this->call([
                DocumentSeeder::class,
            ]);
        }

        // 🔮 MÓDULOS FUTUROS (comentados por ahora)
        // 
        // MÓDULO 2: Chat y conversaciones  
        // $this->call([
        //     ConversationSeeder::class,
        //     MessageSeeder::class,
        // ]);
        //
        // MÓDULO 3: CRM y leads
        // $this->call([
        //     LeadSeeder::class,
        //     ContactSeeder::class,
        // ]);

        // Rehabilitar verificación de claves foráneas
        Schema::enableForeignKeyConstraints();

        $this->command->info('✅ Seeders completados exitosamente!');
        $this->showCredentials();
    }

    /**
     * Mostrar credenciales de acceso
     */
    private function showCredentials(): void
    {
        $this->command->line('');
        $this->command->line('🔑 <bg=blue;fg=white> CREDENCIALES DE ACCESO </bg=blue;fg=white>');
        $this->command->line('');
        $this->command->line('👤 <comment>Administrador:</comment>');
        $this->command->line('   📧 Email: <info>admin@chatbotcrm.com</info>');
        $this->command->line('   🔒 Password: <info>password123</info>');
        $this->command->line('');
        $this->command->line('👤 <comment>Usuario Demo:</comment>');
        $this->command->line('   📧 Email: <info>demo@chatbotcrm.com</info>');
        $this->command->line('   🔒 Password: <info>password123</info>');
        $this->command->line('');
        $this->command->line('🌐 <comment>URLs:</comment>');
        $this->command->line('   🔧 Backend API: <info>http://localhost:8000/api</info>');
        $this->command->line('   💻 Frontend: <info>http://localhost:3000</info>');
        $this->command->line('');
    }
}