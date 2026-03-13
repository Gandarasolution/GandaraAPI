<?php
// import_db.php
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$sm = $em->getConnection()->createSchemaManager();

// On récupère toutes les tables de votre SQL Server
$tables = $sm->listTables();

foreach ($tables as $table) {
    if (in_array($table->getName(), ['doctrine_migration_versions', 'messenger_messages'])) {
        continue;
    }

    if (!str_contains($table->getName(), 'Planning')) {
        continue; // Si "Planning" n'est pas dedans, on saute à la suivante
    }

    if (!str_contains($table->getName(), 'Planning')) {
        continue; // Si "Planning" n'est pas dedans, on saute à la suivante
    }

    $tableName = $table->getName();
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($tableName))));
    $filePath = __DIR__ . '/src/Entity/' . $className . '.php';


    if (file_exists($filePath)) {
        echo "L'entité $className existe déjà. Ignorée.\n";
        continue;
    }


    echo "Génération de l'entité pour la table : " . $tableName . "\n";

    $properties = [];
    $methods = [];

    $pkColumns = $table->getPrimaryKey() ? $table->getPrimaryKey()->getColumns() : [];

    foreach ($table->getColumns() as $column) {
        $colName = $column->getName();
        $propName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($colName)))));
        $type = \Doctrine\DBAL\Types\Type::lookupName($column->getType());
        $nullable = !$column->getNotnull();

        // Mapping simple DBAL -> PHP
        $phpType = match ($type) {
            'integer', 'smallint', 'bigint' => 'int',
            'boolean' => 'bool',
            'datetime', 'date', 'time', 'datetimetz' => '\DateTimeInterface',
            'float', 'decimal' => 'float',
            default => 'string',
        };

        $nullableSign = $nullable ? '?' : '';
        $defaultValue = $nullable ? ' = null' : '';

        // Construction des attributs
        $attributes = [];
        if (in_array($colName, $pkColumns)) {
            $attributes[] = '#[ORM\Id]';
        }
        if ($column->getAutoincrement()) {
            $attributes[] = '#[ORM\GeneratedValue]';
        }

        $colAttrParts = ["name: '$colName'"];

        // Si le type DBAL n'est pas standard (ex: datetime), on peut le préciser,
        // mais souvent Doctrine le devine. Pour être sûr :
        // Cependant Types::STRING est 'string', etc.
        // On peut omettre `type` si ça correspond, mais mieux vaut être explicite pour la relecture
        // Sauf que 'int' n'est pas un type doctrine, c'est 'integer'.
        // $type est le nom du type DBAL (ex: 'integer', 'string', 'datetime')
        $colAttrParts[] = "type: '$type'";

        if ($nullable) {
            $colAttrParts[] = 'nullable: true';
        }
        if ($type === 'string' && $column->getLength()) {
            $colAttrParts[] = 'length: ' . $column->getLength();
        }

        $attributes[] = '#[ORM\Column(' . implode(', ', $colAttrParts) . ')]';

        $propCode = "";
        foreach ($attributes as $attr) {
            $propCode .= "    $attr\n";
        }
        $propCode .= "    private $nullableSign$phpType $$propName$defaultValue;";
        $properties[] = $propCode;

        // Getter
        $methodName = ucfirst($propName);
        $getter = "    public function get$methodName(): $nullableSign$phpType\n    {\n        return \$this->$propName;\n    }";
        $methods[] = $getter;

        // Setter
        // Pour les IDs auto-incrémentés, on ne génère pas forcément de setter, mais bon, ça ne mange pas de pain.
        $setter = "    public function set$methodName($nullableSign$phpType $$propName): static\n    {\n        \$this->$propName = $$propName;\n        return \$this;\n    }";
        $methods[] = $setter;
    }

    $content = <<<PHP
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '$tableName')]
class $className
{
PHP;

    $content .= "\n" . implode("\n\n", $properties) . "\n\n";
    $content .= implode("\n\n", $methods);
    $content .= "\n}\n";

    file_put_contents($filePath, $content);
}
