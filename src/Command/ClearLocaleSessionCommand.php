<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-locale-session',
    description: 'Clear locale from session storage'
)]
class ClearLocaleSessionCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Clearing Locale Session Data');
        
        // Session dosyalarını temizle (var/sessions klasörü)
        $sessionPath = __DIR__ . '/../../var/sessions';
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/sess_*');
            $count = 0;
            foreach ($files as $file) {
                if (unlink($file)) {
                    $count++;
                }
            }
            $io->success("Cleared {$count} session files");
        } else {
            $io->note('Session directory not found');
        }
        
        $io->text('Recommendations:');
        $io->listing([
            'Clear browser cache/cookies',
            'Open new incognito/private window',
            'Test with: http://localhost (should be Turkish)',
            'Test with: http://localhost/?_locale=en (should be English)',
        ]);

        return Command::SUCCESS;
    }
}
