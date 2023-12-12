<?php declare(strict_types=1);

namespace Dras\CheckTranslations\Command;

use Dras\CheckTranslations\Service\MissingTranslationsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $this->addOption("base-locale", "l", InputOption::VALUE_REQUIRED, "REQUIRED: Language the Shop is based on (Contains all Snippet Keys)");
        $this->addOption("max-charcters", "m", InputOption::VALUE_REQUIRED, "Truncate text after n characters and add a '...' ellipsis", 0);
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if(empty($input->getOption('base-locale'))) {
            $io->error("Base locale can not be empty. Use -h for more information");
            return 1;
        }

        $baseLocale = $input->getOption('base-locale');

        $maxChars = 0;
        if(\is_numeric($input->getOption('max-charcters'))) {
            $maxChars = (int)$input->getOption('max-charcters');
        }
        
        $missingSnippets = $this->missingTranslationService->getMissingSnippets($baseLocale, $maxChars);
        if($missingSnippets === \false) {
            $io->error("Error retrieving missing snippets. Check that the base-locale is correctly set (e.g. de-DE or en-GB)");
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

    private function kebabToCamelCase($string, $capitalizeFirstCharacter = false)  : string
    {
    
        $str = str_replace('_', '', ucwords($string, '_'));
    
        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }
    
        return $str;
    }
}
