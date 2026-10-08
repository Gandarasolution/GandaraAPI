<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class FilterConfigRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEvenement::class);
    }

    /**
     * @throws Exception
     */
    public function get(int $idPersonnel, mixed $types, mixed $keys, LoggerInterface $logger)
    {


        $conn = $this->getEntityManager()->getConnection();

        $sql = '
    EXEC dbo.ps_GetDynamicFilterOptions
        @Keys = :Keys,
        @ViewType = :ViewType,
        @IdPersonnel = :IdPersonnel
';

        $params = [
            'Keys' => trim($keys, '"'),
            'ViewType' => $types,
            'IdPersonnel' => $idPersonnel
        ];

        $resultSet = $conn->executeQuery($sql, $params)
            ->fetchAllAssociative();

        $structuredData = [];
        $activeFilter = new \stdClass();

        foreach ($resultSet as $row) {

            if ($row['FilterKey'] === null) {
                $activeFilter = json_decode(
                    $row['ActiveFilters'] ?? '{}',
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );

                continue;
            }

            // Options disponibles
            $key = $row['FilterKey'];
            $value = $row['FilterValue'];
            $label = $row['FilterLabel'];

            if (!isset($structuredData[$key])) {
                $structuredData[$key] = [];
            }

            $structuredData[$key][] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return [
            'Filters' => $structuredData,
            'ActiveFilters' => $activeFilter
        ];

    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function save(int $getIdpersonnel, mixed $viewType, mixed $activeFilters, LoggerInterface $logger): int
    {

        $conn = $this->getEntityManager()->getConnection();

        $sql = ' EXEC ps_SessionFiltreInsertUpdate
            @IdPersonnel = :IdPersonnel,
            @ClePage = :ViewType,
            @FiltresJSON = :ActiveFilters';
        $params = [
            'IdPersonnel' => $getIdpersonnel,
            'ViewType' => $viewType,
            'ActiveFilters' => json_encode(
                (object) $activeFilters,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        ];

        $conn->executeStatement($sql, $params);

        return 0;
    }
}
