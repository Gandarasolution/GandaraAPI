<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;

class PlanningTypesJourRepository
{

    public function __construct(private readonly Connection $connection)
    { }

    public function getAllTypesJour(): array
    {
        $sql = 'EXEC ps_PlanningTypeJourSelect';
        $result = $this->connection->fetchAllAssociative($sql);

        foreach ($result as &$row) {
            $row['isItalic'] = (bool) $row['isItalic'];
            $row['isBold']   = (bool) $row['isBold'];
            $row['isLocked'] = (bool) $row['isLocked'];
        }
        unset($row);

        return $result;
    }

    public function addTypeJour(array $data): array
    {
        $sql = 'EXEC ps_PlanningTypeJourInsert @Libelle = ?, @Code = ?, @CouleurFond = ?, @CouleurText = ?, @IsItalic = ?, @IsBold = ?';
        $code =$this->toCamelCase($data['Libelle']);
        $this->connection->executeQuery($sql,
            [$data['Libelle'], $code, $data['CouleurFond'], $data['CouleurTexte'], $data['isItalic'], $data['isBold']]
        );
        return $this->getAllTypesJour();
    }

    public function updateTypeJour(array $data): array
    {
        $sql = 'EXEC ps_PlanningTypeJourUpdate @Libelle = ?, @Code = ?, @CouleurFond = ?, @CouleurText = ?, @IsItalic = ?, @IsBold = ?';
        $this->connection->executeQuery($sql,
            [$data['Libelle'], $data['Id'], $data['CouleurFond'], $data['CouleurTexte'], $data['isItalic'], $data['isBold']]
        );
        return $this->getAllTypesJour();
    }

    public function removeTypeJour(string $id, int $numberDayCheck): array
    {
        $sql = 'EXEC ps_PlanningTypeJourDelete @Code = ?, @NombreJour = ?';
        $this->connection->executeQuery($sql, [$id, $numberDayCheck]);
        return $this->getAllTypesJour();

    }


    private function toCamelCase(string $text): string
    {
        // Enlève les accents
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text) ?: $text;

        // Supprime les caractères spéciaux sauf espaces
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        // Découpe les mots
        $words = explode(' ', strtolower(trim($text)));

        if (empty($words)) {
            return 'dayType_' . time();
        }

        $camel = array_shift($words);
        foreach ($words as $word) {
            $camel .= ucfirst($word);
        }

        return $camel;
    }

    public function getDeleteImpact(string $id): ?string
    {
        $sql = '
            SELECT COUNT(IdPlanningJourNontravaille) AS nombreJours FROM PlanningJourNontravaille
            WHERE TypeJourCode = ?
        ';
        $result = $this->connection->fetchAssociative($sql, [$id]);

        $nombreJours = $result['nombreJours'] ?? 0;
        if ($nombreJours > 0) {
            return 'Ce type contient ' . $nombreJours . ' jours. Pour confirmer la suppression de ce type merci de ressaisir le nombre de jours si dessous.';
        }
        return null;
    }
}
