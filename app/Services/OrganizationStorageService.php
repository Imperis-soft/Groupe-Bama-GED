<?php

namespace App\Services;

use App\Models\Organization;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use RuntimeException;
use Throwable;

/**
 * Gestion du bucket MinIO propre à chaque entreprise : vérification et création.
 */
class OrganizationStorageService
{
    // Règles de nommage S3 / MinIO : 3 à 63 caractères, minuscules, chiffres, points et tirets
    public const BUCKET_REGEX = '/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/';

    // Nom de bucket proposé par défaut pour une entreprise
    public static function suggestedBucket(string $slug): string
    {
        $name = trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower('ged-' . $slug)), '-');
        return substr($name, 0, 63);
    }

    private function client(Organization $organization): S3Client
    {
        return $organization->disk()->getClient();
    }

    public function bucketExists(Organization $organization): bool
    {
        // Disque local / simulé (tests) : le dossier est créé à la première écriture
        if (!$organization->disk() instanceof AwsS3V3Adapter) {
            return true;
        }

        try {
            return $this->client($organization)->doesBucketExistV2($organization->storageBucket(), false);
        } catch (Throwable $e) {
            throw new RuntimeException('Connexion à MinIO impossible : ' . $this->message($e), 0, $e);
        }
    }

    /**
     * Vérifie que le bucket existe et le crée si besoin.
     * Retourne true si le bucket vient d'être créé, false s'il existait déjà.
     */
    public function ensureBucket(Organization $organization, bool $create = true): bool
    {
        if ($this->bucketExists($organization)) {
            return false;
        }

        if (!$create) {
            throw new RuntimeException("Le bucket « {$organization->storageBucket()} » n'existe pas sur le serveur MinIO.");
        }

        try {
            $client = $this->client($organization);
            $client->createBucket(['Bucket' => $organization->storageBucket()]);
            $client->waitUntil('BucketExists', ['Bucket' => $organization->storageBucket()]);
        } catch (Throwable $e) {
            throw new RuntimeException("Création du bucket « {$organization->storageBucket()} » impossible : " . $this->message($e), 0, $e);
        }

        return true;
    }

    /**
     * Bucket partagé : crée le dossier de l'entreprise (ged/<dossier>/documents/).
     * S3 n'a pas de vrais dossiers : on dépose un fichier .keep pour matérialiser l'espace.
     */
    public function ensureFolder(Organization $organization): void
    {
        $this->ensureBucket($organization);

        try {
            $disk = $organization->disk();
            $keep = $organization->documentsDir() . '.keep';
            if (!$disk->exists($keep) && !$disk->put($keep, '')) {
                throw new RuntimeException("écriture refusée");
            }
        } catch (Throwable $e) {
            throw new RuntimeException("Création du dossier « {$organization->storageBucket()}/{$organization->storagePrefix()}/ » impossible : " . $this->message($e), 0, $e);
        }
    }

    /**
     * Bucket partagé : supprime tout le dossier de l'entreprise (suppression définitive).
     */
    public function deleteFolder(Organization $organization): bool
    {
        $prefix = trim($organization->storagePrefix(), '/');
        if ($organization->hasDedicatedBucket() || $prefix === '') {
            return false;
        }

        try {
            return $organization->disk()->deleteDirectory($prefix);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Supprime le bucket dédié s'il est vide (suppression d'une entreprise).
     */
    public function deleteBucketIfEmpty(Organization $organization): bool
    {
        if (!$organization->hasDedicatedBucket()) {
            return false;
        }

        try {
            if (!$organization->disk() instanceof AwsS3V3Adapter || $organization->disk()->allFiles() !== []) {
                return false;
            }
            $this->client($organization)->deleteBucket(['Bucket' => $organization->storageBucket()]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function message(Throwable $e): string
    {
        if ($e instanceof S3Exception) {
            return $e->getAwsErrorMessage() ?: ($e->getAwsErrorCode() ?: $e->getMessage());
        }
        return $e->getMessage();
    }
}
