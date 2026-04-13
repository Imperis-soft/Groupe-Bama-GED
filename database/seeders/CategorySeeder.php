<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            // ── ADMINISTRATION & DIRECTION ──────────────────────────────
            [
                'name'        => 'Administration & Direction',
                'description' => 'Documents de gouvernance, décisions et correspondances de la direction générale.',
                'children'    => [
                    ['name' => 'Procès-verbaux',          'description' => 'PV de réunions, assemblées générales, conseils d\'administration.'],
                    ['name' => 'Décisions de direction',  'description' => 'Notes de service, circulaires et décisions officielles.'],
                    ['name' => 'Correspondances officielles', 'description' => 'Courriers entrants et sortants de la direction.'],
                    ['name' => 'Rapports annuels',        'description' => 'Rapports d\'activité et bilans annuels.'],
                    ['name' => 'Organigrammes',           'description' => 'Structures organisationnelles et fiches de poste.'],
                ],
            ],

            // ── RESSOURCES HUMAINES ─────────────────────────────────────
            [
                'name'        => 'Ressources Humaines',
                'description' => 'Gestion du personnel, contrats de travail et documents RH.',
                'children'    => [
                    ['name' => 'Contrats de travail',     'description' => 'CDI, CDD, contrats de stage et avenants.'],
                    ['name' => 'Fiches de paie',          'description' => 'Bulletins de salaire et états de paie.'],
                    ['name' => 'Dossiers du personnel',   'description' => 'Dossiers individuels des employés.'],
                    ['name' => 'Formations',              'description' => 'Plans de formation, attestations et certificats.'],
                    ['name' => 'Évaluations',             'description' => 'Entretiens annuels et évaluations de performance.'],
                    ['name' => 'Congés & Absences',       'description' => 'Demandes de congé, arrêts maladie et justificatifs.'],
                    ['name' => 'Règlement intérieur',     'description' => 'Politiques RH, règlement intérieur et chartes.'],
                ],
            ],

            // ── FINANCE & COMPTABILITÉ ──────────────────────────────────
            [
                'name'        => 'Finance & Comptabilité',
                'description' => 'Documents financiers, comptables et fiscaux.',
                'children'    => [
                    ['name' => 'Factures',                'description' => 'Factures clients et fournisseurs.'],
                    ['name' => 'Bons de commande',        'description' => 'Bons de commande et bons de livraison.'],
                    ['name' => 'Budgets & Prévisions',    'description' => 'Budgets annuels, prévisionnels et suivis.'],
                    ['name' => 'Bilans & Comptes',        'description' => 'Bilans comptables, comptes de résultat.'],
                    ['name' => 'Déclarations fiscales',   'description' => 'Déclarations TVA, IS et autres obligations fiscales.'],
                    ['name' => 'Relevés bancaires',       'description' => 'Relevés de compte et rapprochements bancaires.'],
                    ['name' => 'Notes de frais',          'description' => 'Remboursements de frais professionnels.'],
                ],
            ],

            // ── JURIDIQUE & CONFORMITÉ ──────────────────────────────────
            [
                'name'        => 'Juridique & Conformité',
                'description' => 'Contrats, actes juridiques et documents de conformité réglementaire.',
                'children'    => [
                    ['name' => 'Contrats commerciaux',    'description' => 'Contrats clients, fournisseurs et partenaires.'],
                    ['name' => 'Actes notariés',          'description' => 'Actes de propriété, statuts et actes officiels.'],
                    ['name' => 'Licences & Autorisations','description' => 'Licences d\'exploitation, autorisations administratives.'],
                    ['name' => 'Contentieux',             'description' => 'Dossiers de litiges, mises en demeure et jugements.'],
                    ['name' => 'Conformité RGPD',         'description' => 'Politiques de confidentialité, registres de traitement.'],
                    ['name' => 'Assurances',              'description' => 'Polices d\'assurance et déclarations de sinistres.'],
                ],
            ],

            // ── PROJETS & OPÉRATIONS ────────────────────────────────────
            [
                'name'        => 'Projets & Opérations',
                'description' => 'Documentation de projets, cahiers des charges et rapports d\'avancement.',
                'children'    => [
                    ['name' => 'Cahiers des charges',     'description' => 'Spécifications fonctionnelles et techniques.'],
                    ['name' => 'Plans de projet',         'description' => 'Plannings, Gantt et feuilles de route.'],
                    ['name' => 'Rapports d\'avancement',  'description' => 'Comptes rendus de réunion et rapports de suivi.'],
                    ['name' => 'Livrables',               'description' => 'Documents livrés aux clients ou parties prenantes.'],
                    ['name' => 'Procédures opérationnelles', 'description' => 'Modes opératoires, SOP et guides de processus.'],
                ],
            ],

            // ── QUALITÉ & NORMES ────────────────────────────────────────
            [
                'name'        => 'Qualité & Normes',
                'description' => 'Système de management de la qualité, certifications et audits.',
                'children'    => [
                    ['name' => 'Manuel qualité',          'description' => 'Manuel du système de management de la qualité.'],
                    ['name' => 'Procédures qualité',      'description' => 'Procédures et instructions de travail certifiées.'],
                    ['name' => 'Audits internes',         'description' => 'Rapports d\'audit interne et plans d\'action.'],
                    ['name' => 'Certifications',          'description' => 'Certificats ISO, accréditations et attestations.'],
                    ['name' => 'Non-conformités',         'description' => 'Fiches de non-conformité et actions correctives.'],
                ],
            ],

            // ── COMMERCIAL & MARKETING ──────────────────────────────────
            [
                'name'        => 'Commercial & Marketing',
                'description' => 'Offres commerciales, supports marketing et études de marché.',
                'children'    => [
                    ['name' => 'Offres & Devis',          'description' => 'Propositions commerciales et devis clients.'],
                    ['name' => 'Présentations',           'description' => 'Présentations commerciales et pitchs.'],
                    ['name' => 'Études de marché',        'description' => 'Analyses concurrentielles et études sectorielles.'],
                    ['name' => 'Supports marketing',      'description' => 'Brochures, flyers et supports de communication.'],
                    ['name' => 'Rapports commerciaux',    'description' => 'Tableaux de bord commerciaux et rapports de vente.'],
                ],
            ],

            // ── INFORMATIQUE & TECHNIQUE ────────────────────────────────
            [
                'name'        => 'Informatique & Technique',
                'description' => 'Documentation technique, architectures et manuels d\'utilisation.',
                'children'    => [
                    ['name' => 'Architectures système',   'description' => 'Schémas d\'architecture et plans d\'infrastructure.'],
                    ['name' => 'Manuels utilisateurs',    'description' => 'Guides d\'utilisation et documentation fonctionnelle.'],
                    ['name' => 'Spécifications techniques','description' => 'Cahiers des charges techniques et API docs.'],
                    ['name' => 'Sécurité informatique',   'description' => 'Politiques de sécurité, PSSI et rapports de vulnérabilité.'],
                    ['name' => 'Maintenance',             'description' => 'Rapports de maintenance et journaux d\'incidents.'],
                ],
            ],

            // ── ACHATS & LOGISTIQUE ─────────────────────────────────────
            [
                'name'        => 'Achats & Logistique',
                'description' => 'Gestion des fournisseurs, approvisionnements et logistique.',
                'children'    => [
                    ['name' => 'Appels d\'offres',        'description' => 'Dossiers d\'appels d\'offres et cahiers des charges achats.'],
                    ['name' => 'Contrats fournisseurs',   'description' => 'Contrats et conventions avec les fournisseurs.'],
                    ['name' => 'Bons de réception',       'description' => 'Bons de réception et fiches de contrôle qualité.'],
                    ['name' => 'Inventaires',             'description' => 'États des stocks et inventaires physiques.'],
                ],
            ],

            // ── ARCHIVES ────────────────────────────────────────────────
            [
                'name'        => 'Archives',
                'description' => 'Documents archivés, historiques et fonds documentaires.',
                'children'    => [
                    ['name' => 'Archives administratives','description' => 'Documents administratifs archivés.'],
                    ['name' => 'Archives financières',    'description' => 'Documents comptables et financiers archivés.'],
                    ['name' => 'Archives techniques',     'description' => 'Documentation technique archivée.'],
                ],
            ],

        ];

        foreach ($categories as $cat) {
            $children = $cat['children'] ?? [];
            unset($cat['children']);

            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, ['parent_id' => null])
            );

            foreach ($children as $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child['name'])],
                    array_merge($child, ['parent_id' => $parent->id])
                );
            }
        }

        $this->command->info('✓ ' . Category::count() . ' catégories créées.');
    }
}
