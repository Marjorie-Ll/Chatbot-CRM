<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador por defecto
        User::create([
            'name' => 'Admin Chatbot CRM',
            'email' => 'admin@chatbotcrm.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Usuario de prueba
        User::create([
            'name' => 'Usuario Demo',
            'email' => 'demo@chatbotcrm.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        echo "✅ Usuarios creados exitosamente\n";
        echo "📧 Email: admin@chatbotcrm.com | Password: password123\n";
        echo "📧 Email: demo@chatbotcrm.com | Password: password123\n";
    }
}