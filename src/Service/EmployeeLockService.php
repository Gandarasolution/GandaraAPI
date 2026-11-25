<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class EmployeeLockService
{
    private const KEY_PREFIX = 'lock_employee_';
    private const DEFAULT_TTL = 300;

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {}

    /**
     * Tente de poser ou prolonger un verrou sur un employé.
     * Retourne null si succès, ou l'ID du détenteur actuel si verrouillé par quelqu'un d'autre.
     * @throws InvalidArgumentException
     */
    public function acquireLock(int $employeeId, int $userId, string $userNom, int $ttl = self::DEFAULT_TTL): array
    {
        $cacheKey = self::KEY_PREFIX . $employeeId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $cached = $cacheItem->get();
            $ownerId = is_array($cached) ? (int) ($cached['updatedById'] ?? 0) : (int) $cached;
            $isLocked = !is_array($cached) || (bool)($cached['status'] ?? true);

            if ($isLocked && $ownerId !== $userId) {
                $existingLockData = is_array($cached) ? $cached : [
                    'status'       => true,
                    'expiresAt'    => (int) round((microtime(true) + $ttl) * 1000),
                    'updatedById'  => $ownerId,
                    'updatedByNom' => null,
                ];

                return $existingLockData;
            }
        }

        $lockData = [
            'status'       => true,
            'expiresAt'    => (int) round((microtime(true) + $ttl) * 1000),
            'updatedById'  => $userId,
            'updatedByNom' => $userNom,
        ];

        $cacheItem->set($lockData);
        $cacheItem->expiresAfter($ttl);
        $this->cache->save($cacheItem);

        return $lockData;
    }

    /**
     * Libère le verrou s'il appartient à l'utilisateur (ou de force si $force = true).
     * @throws InvalidArgumentException
     */
    public function releaseLock(int $employeeId, int $userId, bool $force = false): bool
    {
        $cacheKey = self::KEY_PREFIX . $employeeId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            return true;
        }

        $cached = $cacheItem->get();
        $ownerId = is_array($cached) ? (int) ($cached['updatedById'] ?? 0) : (int) $cached;

        if (!$force && $ownerId !== $userId) {
            return false;
        }

        return $this->cache->deleteItem($cacheKey);
    }

    /**
     * Enrichit une liste d'employés avec isLocked et lockedBy en une seule requête Redis (MGET).
     * @throws InvalidArgumentException
     */
    public function attachLockStatus(array $employees, int $currentUserId): array
    {
        if (empty($employees)) {
            return $employees;
        }

        $keysMap = [];
        foreach ($employees as $index => $emp) {
            $empId = $emp['IdPersonnel'] ?? $emp['id'] ?? null;
            if ($empId !== null) {
                $keysMap[self::KEY_PREFIX . $empId] = $index;
            }
        }

        if (empty($keysMap)) {
            return $employees;
        }

        $items = $this->cache->getItems(array_keys($keysMap));
        $defaultTimestamp = (int) round(microtime(true) * 1000);

        foreach ($items as $key => $item) {
            $index = $keysMap[$key];

            if ($item->isHit()) {
                $cached = $item->get();

                // Support du nouveau format tableau et rétrocompatibilité avec l'ancien int
                $ownerId      = is_array($cached) ? (int) ($cached['updatedById'] ?? 0) : (int) $cached;
                $ownerNom     = is_array($cached) ? ($cached['updatedByNom'] ?? null) : null;
                $rawStatus    = !is_array($cached) || (bool)($cached['status'] ?? true);
                $expiresAt    = is_array($cached) ? (int) ($cached['expiresAt'] ?? $defaultTimestamp) : $defaultTimestamp;

                $isLockedByOther = $rawStatus && ($ownerId !== $currentUserId);

                $employees[$index]['isLocked'] = [
                    'status'       => $isLockedByOther,
                    'expiresAt'    => $expiresAt,
                    'updatedById'  => $ownerId ?: null,
                    'updatedByNom' => $ownerNom,
                ];
            } else {
                $employees[$index]['isLocked'] = [
                    'status'       => false,
                    'expiresAt'    => $defaultTimestamp,
                    'updatedById'  => null,
                    'updatedByNom' => null,
                ];
            }
        }

        return $employees;
    }


    /**
     * Vérifie si un employé est verrouillé par un autre utilisateur.
     * Retourne ['isLocked' => bool, 'lockedBy' => ?int]
     * @throws InvalidArgumentException
     */
    public function getLockStatus(int $employeeId, int $currentUserId): array
    {
        $cacheKey = self::KEY_PREFIX . $employeeId;
        $cacheItem = $this->cache->getItem($cacheKey);
        $defaultTimestamp = (int) round(microtime(true) * 1000);

        if ($cacheItem->isHit()) {
            $cached = $cacheItem->get();

            $ownerId   = is_array($cached) ? (int) ($cached['updatedById'] ?? 0) : (int) $cached;
            $ownerNom  = is_array($cached) ? ($cached['updatedByNom'] ?? null) : null;
            $rawStatus = !is_array($cached) || (bool)($cached['status'] ?? true);
            $timestamp = is_array($cached) ? (int) ($cached['timestamp'] ?? $defaultTimestamp) : $defaultTimestamp;

            $isLockedByOther = $rawStatus && ($ownerId !== $currentUserId);

            return [
                'isLocked' => [
                    'status'       => $isLockedByOther,
                    'timestamp'    => $timestamp,
                    'updatedById'  => $isLockedByOther ? $ownerId : null,
                    'updatedByNom' => $isLockedByOther ? $ownerNom : null,
                ],
                'lockedBy' => $isLockedByOther ? $ownerId : null,
            ];
        }

        return [
            'isLocked' => [
                'status'       => false,
                'timestamp'    => $defaultTimestamp,
                'updatedById'  => null,
                'updatedByNom' => null,
            ],
            'lockedBy' => null,
        ];
    }
}
