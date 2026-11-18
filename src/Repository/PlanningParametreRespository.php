<?php

namespace App\Repository;

use App\Entity\Planningressource;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class PlanningParametreRespository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router, private LoggerInterface $logger)
    {
        parent::__construct($registry, Planningressource::class);
    }

    public function getParametres(string $listeIdParametre, int $idUser)
    {
        $listeIdParametre = explode(',', $listeIdParametre);

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_ParametreSelect @IdParametre = :listeIdParametre, @IdPersonnel = :IdPersonnel';


        $result = [];

        foreach ($listeIdParametre as $idParametre) {
            $this->logger->debug(sprintf('Exécution de la requête pour l\'ID paramètre : %s et l\'ID utilisateur : %d', $idParametre, $idUser));

            $row = $conn->executeQuery($sql, [
                'listeIdParametre' => $idParametre,
                'IdPersonnel' => $idUser,
            ])->fetchAssociative();

            if ($row) {
                $result[] = [
                    'idParametre' => $row['Id'],
                    'valeurParametre' => $row['ValeurParametre'],
                ];
            }
        }

        return $result;
    }

    public function setParametre(string $idParametre, string $valeurParametre,  int $idUser)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_ParametreInsert @IdParametre = :idParametre, @ValeurParametre = :valeurParametre';

        $this->logger->debug(sprintf('Exécution de la requête pour l\'ID paramètre : %s et l\'ID utilisateur : %d', $idParametre, $idUser));

        $conn->executeQuery($sql, [
            'idParametre' => $idParametre,
            'valeurParametre' => $valeurParametre,
        ]);

        return true;
    }
}
