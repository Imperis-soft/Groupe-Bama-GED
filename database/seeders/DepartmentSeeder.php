<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Services communs à toutes les entreprises de la plateforme.
 * Relançable sans risque : les services existants ne sont ni dupliqués ni modifiés.
 *   php artisan db:seed --class=DepartmentSeeder
 */
class DepartmentSeeder extends Seeder
{
    public const SERVICES = [
        'Direction Générale'             => 'Pilotage de l\'entreprise, décisions stratégiques et documents de gouvernance.',
        'Secrétariat de Direction'       => 'Courrier, agendas, comptes rendus et notes de service.',
        'Ressources Humaines'            => 'Dossiers du personnel, contrats de travail, paie et formation.',
        'Comptabilité'                   => 'Pièces comptables, factures, déclarations fiscales et sociales.',
        'Finances et Trésorerie'         => 'Budget, banque, trésorerie et relations avec les financeurs.',
        'Juridique'                      => 'Contrats, contentieux, statuts et conformité réglementaire.',
        'Commercial'                     => 'Offres, devis, contrats clients et suivi des ventes.',
        'Marketing et Communication'     => 'Supports de communication, relations presse et image de marque.',
        'Achats'                         => 'Appels d\'offres, bons de commande et contrats fournisseurs.',
        'Logistique'                     => 'Stocks, transport, bons de livraison et réceptions.',
        'Informatique'                   => 'Systèmes d\'information, matériel, licences et sécurité.',
        'Audit et Contrôle Interne'      => 'Contrôles, rapports d\'audit et procédures internes.',
        'Qualité, Hygiène, Sécurité, Environnement' => 'Normes, certifications, procédures QHSE et incidents.',
        'Technique et Exploitation'      => 'Production, maintenance, plans et documentation technique.',
        'Service Client'                 => 'Réclamations, demandes et correspondance avec les clients.',
        'Courrier et Archives'           => 'Enregistrement du courrier, classement et archivage.',
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $name => $description) {
            Department::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
