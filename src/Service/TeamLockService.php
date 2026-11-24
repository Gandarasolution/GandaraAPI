<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

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
     */
    public function acquireLock(int $teamId, int $userId, int $ttl = self::DEFAULT_TTL): ?int
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $ownerId = is_array($data) ? (int) $data['ownerId'] : (int) $data;

            if ($ownerId !== $userId) {
                return $ownerId;
            }
        }

        $cacheItem->set([
            'ownerId'   => $userId,
            'timestamp' => time() * 1000, // Timestamp en millisecondes pour TypeScript
        ]);
        $cacheItem->expiresAfter($ttl);
        $this->cache->save($cacheItem);

        return null;
    }

    public function getLockStatus(int $teamId, int $currentUserId): array
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $ownerId = is_array($data) ? (int) $data['ownerId'] : (int) $data;

            if ($ownerId !== $currentUserId) {
                return [
                    'isLocked' => true,
                    'lockedBy' => $ownerId,
                ];
            }
        }

        return [
            'isLocked' => false,
            'lockedBy' => null,
        ];
    }

    public function releaseLock(int $teamId, int $userId, bool $force = false): bool
    {
        $cacheKey = self::KEY_PREFIX . $teamId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            return true;
        }

        $data = $cacheItem->get();
        $ownerId = is_array($data) ? (int) $data['ownerId'] : (int) $data;

        if (!$force && $ownerId !== $userId) {
            return false;
        }

        return $this->cache->deleteItem($cacheKey);
    }

    /**
     * Enrichit chaque équipe avec l'objet `isLocked: { status: bool, timestamp: int }`
     * en une seule requête Redis (MGET via getItems).
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
                $ownerId = is_array($data) ? (int) $data['ownerId'] : (int) $data;
                $lockedAt = is_array($data) && isset($data['timestamp']) ? (int) $data['timestamp'] : $nowMs;

                $isLockedByOther = ($ownerId !== $currentUserId);

                $teams[$index]['isLocked'] = [
                    'status'    => $isLockedByOther,
                    'timestamp' => $isLockedByOther ? $lockedAt : $nowMs,
                ];
            } else {
                $teams[$index]['isLocked'] = [
                    'status'    => false,
                    'timestamp' => $nowMs,
                ];
            }
        }

        return $teams;
    }
}
