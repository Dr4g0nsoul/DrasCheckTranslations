<?php declare(strict_types=1);

namespace Dras\CheckTranslations\Command;

use Dras\CheckTranslations\Service\MissingTranslationsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dras:missing-snippet-translations',
    description: 'Find missing snippet translations in your Shop.',
    hidden: false
)]
class MissingSnippetTranslationsCommand extends Command
{

    public function __construct(private MissingTranslationsService $missingTranslationService) {
        parent::__construct();
    }

    // Command name
    protected static $defaultName = 'dras:missing-snippet-translations';

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Find missing snippet translations in your Shop.');
        $this->addOption("max-charcters", "m", InputOption::VALUE_REQUIRED, "Truncate text after n characters and add a '...' ellipsis", 0);
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxChars = 0;
        if(\is_numeric($input->getOption('max-charcters'))) {
            $maxChars = (int)$input->getOption('max-charcters');
        }
        
        $missingSnippets = $this->missingTranslationService->getMissingSnippets($maxChars);
        if($missingSnippets === \false) {
            $io->error("Error retrieving missing snippets.");
        } elseif(empty($missingSnippets)) {
            $io->success("Good Job! All snippets are translated!");
        } else {
            $headers = \array_keys($missingSnippets[0]);
            $io->table($headers, $missingSnippets);
            $io->warning("Please check the listed translations above!");
        }
        
        // Exit code 0 for success
        return 0;
    }
}
