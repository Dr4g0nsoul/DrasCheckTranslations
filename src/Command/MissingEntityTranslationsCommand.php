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
    name: 'dras:missing-entity-translations',
    description: 'Find missing entity translations in your Shop. (Custom fields not yet supported)',
    hidden: false
)]
class MissingEntityTranslationsCommand extends Command
{

    public function __construct(private MissingTranslationsService $missingTranslationService) {
        parent::__construct();
    }

    // Command name
    protected static $defaultName = 'dras:missing-entity-translations';

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Find missing entity translations in your Shop. (Custom fields not yet supported)');
        $this->addOption("max-charcters", "m", InputOption::VALUE_REQUIRED, "Truncate text after n characters and add a '...' ellipsis", 0);
        $this->addOption("include-tables", "i", InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Array of table names to include. (Use DB Table names, '_translation' can be omitted)", []);
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxChars = 0;
        if(\is_numeric($input->getOption('max-charcters'))) {
            $maxChars = (int)$input->getOption('max-charcters');
        }

        $filterTables = $input->getOption("include-tables");
        if(!empty($filterTables) && \is_array($filterTables)) {
            $filterTables = $this->prepareFilterTables($filterTables);
        } else {
            $filterTables = [];
        }

        $missingTranslations = $this->missingTranslationService->getMissingEntityTranslations($filterTables);
        if($missingTranslations === \false) {
            $io->error("Error retrieving missing translations.");
        } elseif(empty($missingTranslations)) {
            $io->success("All entities are translated!");
        } else {
            $headers = \array_keys($missingTranslations["__activeLanguages"]);
            \array_unshift($headers, "id");
            foreach ($missingTranslations as $entity => $untranslatedEntities) {
                if(\str_starts_with($entity, "__")) continue;
                $io->title("Missing translations for entity ".$this->kebabToCamelCase($entity)." ($entity)");
                $io->table($headers, $this->missingTranslationService->entityTranslationsToTable($untranslatedEntities, $maxChars));
            }
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

    private function prepareFilterTables(array $tableNames) : array {
        $preparedNames = [];
        foreach ($tableNames as $name) {
            if(!\str_ends_with($name, "_translation")) {
                $name .= "_translation";
            }
            $preparedNames[] = $name;
        }
        return $preparedNames;
    }
}
