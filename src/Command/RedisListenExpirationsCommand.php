<?php

namespace App\Command;

use App\Service\MercureNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:redis:listen-expirations',
    description: 'Écoute les expirations de clés Redis en temps réel pour notifier Mercure.',
)]
class RedisListenExpirationsCommand extends Command
{
    public function __construct(
        private readonly MercureNotificationService $notifier,
        private readonly string                     $redisUrl = 'redis://localhost:6379'
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Démarrage de l\'écouteur d\'expiration Redis...</info>');

        // 1. Connexion directe à Redis
        $redis = new \Redis();

        // Parsing de l'URL pour Docker (ex: redis://redis:6379)
        $parsedUrl = parse_url($this->redisUrl);
        $host = $parsedUrl['host'] ?? '127.0.0.1';
        $port = $parsedUrl['port'] ?? 6379;

        try {
            $redis->connect($host, $port);
            // Configuration pour éviter les timeouts lors d'une longue écoute
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);

            $output->writeln('<info>Connecté à Redis. En attente des expirations...</info>');

            // 2. On s'abonne aux événements d'expiration
            $redis->subscribe(['__keyevent@0__:expired'], function (\Redis $instance, string $channel, string $msg) use ($output) {

                // --- GESTION DES RENDEZ-VOUS ---
                if (str_starts_with($msg, 'edit_rdv_')) {
                    // Extraction des IDs depuis la clé.
                    // Si tu formates ta clé en 'edit_rdv_{idPlanning}_{idRdv}', ce code gérera les deux.
                    $parts = explode('_', str_replace('edit_rdv_', '', $msg));

                    // Si on a deux éléments (1_42), index 0 = Planning, index 1 = RDV
                    // Sinon, on met 1 par défaut pour le planning.
                    $idPlanning = isset($parts[1]) ? (int)$parts[0] : 1;
                    $idRendezVous = isset($parts[1]) ? (int)$parts[1] : (int)$parts[0];

                    $output->writeln("Le verrou pour le RDV $idRendezVous a expiré ! Notification Mercure...");

                    // Appel du service centralisé !
                    $this->notifier->notifyPlanningChange(
                        $idPlanning,
                        'APPOINTMENT_UNLOCKED',
                        -1,
                        [
                            'IdPlanningEvenement' => $idRendezVous,
                        ]
                    );
                }

                // --- GESTION DES CONFIGURATIONS ---
                else if (str_starts_with($msg, 'edit_config_')) {
                    $parts = explode('_', str_replace('edit_config_', '', $msg));

                    $idPlanning = isset($parts[1]) ? (int)$parts[0] : 1;
                    $idVue = isset($parts[1]) ? (int)$parts[1] : (int)$parts[0];

                    $output->writeln("Le verrou pour la vue $idVue a expiré ! Notification Mercure...");

                    // Appel du service centralisé !
                    $this->notifier->notifyPlanningChange(
                        $idPlanning,
                        'CONFIG_UNLOCKED',
                        0, // Système
                        [
                            'configId' => $idVue,
                            'message' => 'Le verrou de configuration a expiré.'
                        ]
                    );
                }
            });

        } catch (\Exception $e) {
            $output->writeln('<error>Erreur Redis : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
