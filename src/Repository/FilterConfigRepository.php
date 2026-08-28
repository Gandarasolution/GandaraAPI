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

    public function get(mixed $types, mixed $keys, LoggerInterface $logger)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_GetDynamicFilterOptions @Keys = :Keys, @ViewType = :Types';
            $params = [
                'Keys' => trim($keys, '"'),
                'Types' => $types
            ];

            $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            //$logger->debug("Résultat brut de la procédure stockée", ['resultSet' => $resultSet]);

            $structuredData = [];

            foreach ($resultSet as $row) {
                // On récupère la clé (ex: "etat") et la valeur (ex: "En cours")
                $key = $row['FilterKey'];
                $value = $row['FilterValue'];

                // Si la clé n'existe pas encore dans notre tableau final, on l'initialise comme un tableau vide
                if (!isset($structuredData[$key])) {
                    $structuredData[$key] = [];
                }

                // On ajoute la valeur dans le tableau correspondant à la clé
                $structuredData[$key][] = $value;
            }

            return $structuredData;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }
}
