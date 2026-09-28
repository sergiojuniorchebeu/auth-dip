<?php

namespace Database\Seeders;

use App\Models\Diploma;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@authdip.test'], ['name' => 'Administrateur IAI', 'password' => Hash::make('password'), 'role' => 'admin']);
        $employer = User::updateOrCreate(['email' => 'employeur@authdip.test'], ['name' => 'Entreprise Démo', 'password' => Hash::make('password'), 'role' => 'employer', 'registration_status' => 'approved', 'company_legal_name' => 'Entreprise Démo SARL', 'company_registration_number' => 'RC/DLA/2026/B/001', 'company_tax_number' => 'M012345678901', 'company_sector' => 'Technologies de l’information', 'company_phone' => '+237 690 00 00 00', 'company_address' => 'Bonanjo, Douala', 'company_city' => 'Douala', 'company_country' => 'Cameroun', 'contact_position' => 'Responsable RH']);
        $diploma = Diploma::updateOrCreate(['number' => 'IAI-DTS-2024-00112'], ['holder_name' => 'Nadia Mbarga', 'program' => 'Génie logiciel', 'diploma_type' => 'DTS', 'study_level' => 'Bac+2', 'study_year' => 2, 'average' => 12.50, 'mention' => 'Assez bien', 'graduation_year' => 2024, 'qr_token' => Str::uuid()->toString()]);
        Diploma::updateOrCreate(['number' => 'IAI-LP-2025-00482'], ['holder_name' => 'Nadia Mbarga', 'program' => 'Génie logiciel', 'diploma_type' => 'Licence professionnelle', 'study_level' => 'Bac+3', 'study_year' => 3, 'average' => 11.80, 'mention' => 'Assez bien', 'graduation_year' => 2025, 'qr_token' => Str::uuid()->toString()]);
        Diploma::updateOrCreate(['number' => 'IAI-IT-2025-00621'], ['holder_name' => 'Jean Kouam', 'program' => 'Systèmes et réseaux', 'diploma_type' => 'Ingénieur des travaux informatiques', 'study_level' => 'Bac+3', 'study_year' => 3, 'average' => 13.20, 'mention' => 'Bien', 'graduation_year' => 2025, 'qr_token' => Str::uuid()->toString()]);
        Diploma::updateOrCreate(['number' => 'IAI-IC-2026-00017'], ['holder_name' => 'Amina Tchoumi', 'program' => 'Génie logiciel', 'diploma_type' => 'Ingénieur de conception', 'study_level' => 'Bac+5', 'study_year' => 5, 'average' => 14.10, 'mention' => 'Bien', 'graduation_year' => 2026, 'qr_token' => Str::uuid()->toString()]);
        $employer->verificationRequests()->firstOrCreate(['reference' => 'REQ-2026-0001'], ['diploma_id' => $diploma->id, 'holder_name' => $diploma->holder_name, 'diploma_number' => $diploma->number, 'graduation_year' => $diploma->graduation_year, 'program' => $diploma->program, 'status' => 'pending']);
    }
}
