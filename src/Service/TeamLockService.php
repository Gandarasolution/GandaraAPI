<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class TeamLockService
{
    private const KEY_PREFIX = 'lock_team_';
    private const DEFAULT_TTL = 300;

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {}

    /**
     * Pose ou prolonge un verrou sur une équipe.
     * Retourne null si succès, ou l'ID de l'utilisateur qui détient déjà le verrou.
     * @throws InvalidArgumentException
     */
    public function acquireLock(int $teamId, int $userId, string $userNom, int $ttl = self::DEFAULT_TTL): array
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
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

        $nowMs = time() * 1000;

        $lockData = [
            'status'       => true,
            'expiresAt'    => $nowMs + ($ttl * 1000),
            'updatedById'  => $userId,
            'updatedByNom' => $userNom,
        ];

        $cacheItem->set($lockData);
        $cacheItem->expiresAfter($ttl);
        $this->cache->save($cacheItem);

        return $lockData;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getLockStatus(int $teamId, int $currentUserId): array
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $ownerId = is_array($data) ? (int) $data['updatedById'] : (int) $data;

            // Compatibilité si une ancienne entrée sans 'expiresAt' est encore en cache
            $expiresAt = is_array($data)
                ? $data['expiresAt']
                : null;

            if ($ownerId !== $currentUserId) {
                return [
                    'status'  => true,
                    'lockedBy'  => $ownerId,
                    'expiresAt' => $expiresAt,
                ];
            }
        }

        return [
            'status'  => false,
            'lockedBy'  => null,
            'expiresAt' => null,
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function releaseLock(int $teamId, int $userId, bool $force = false): bool
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            return true;
        }

        $data = $cacheItem->get();
        $ownerId = is_array($data) ? (int) $data['updatedById'] : (int) $data;

        if (!$force && $ownerId !== $userId) {
            return false;
        }

        return $this->cache->deleteItem($cacheKey);
    }

    /**
     * Enrichit chaque équipe avec l'objet `isLocked: { status: bool, expiresAt: int }`
     * en une seule requête Redis (MGET via getItems).
     * @throws InvalidArgumentException
     */
    public function attachLockStatus(array $teams, int $currentUserId): array
    {
        if (empty($teams)) {
            return $teams;
        }

        $keysMap = [];
        foreach ($teams as $index => $team) {
            $teamId = $team['Id'] ?? $team['id'] ?? null;
            if ($teamId !== null) {
                $keysMap[self::KEY_PREFIX . $teamId] = $index;
            }
        }

        if (empty($keysMap)) {
            return $teams;
        }

        $items = $this->cache->getItems(array_keys($keysMap));
        $nowMs = time() * 1000;

        foreach ($items as $key => $item) {
            $index = $keysMap[$key];

            if ($item->isHit()) {
                $data = $item->get();
                $ownerId = is_array($data) ? (int) $data['updatedById'] : (int) $data;
                $expiresAt = is_array($data) && isset($data['expiresAt']) ? (int) $data['expiresAt'] : $nowMs;


                $isLockedByOther = ($ownerId !== $currentUserId);

                $teams[$index]['isLocked'] = [
                    'status'    => $isLockedByOther,
                    'expiresAt' => $isLockedByOther ? $expiresAt : $nowMs,
                    'updatedBy' => $isLockedByOther ? $ownerId : null,
                    'updatedByNom' => $isLockedByOther && is_array($data) ? ($data['updatedByNom'] ?? null) : null
                ];
            } else {
                $teams[$index]['isLocked'] = [
                    'status'    => false,
                    'expiresAt' => $nowMs,
                    'updatedBy' => null,
                    'updatedByNom' => null
                ];
            }
        }

        return $teams;
    }
}
