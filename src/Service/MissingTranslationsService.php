<?php declare(strict_types=1);

namespace Dras\CheckTranslations\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Snippet\Files\SnippetFileCollection;
use Shopware\Core\System\Snippet\SnippetService;

class MissingTranslationsService extends SnippetService {


    public function __construct(
        private readonly Connection $connection,
        private readonly SnippetFileCollection $snippetFileCollection,
        private readonly EntityRepository $snippetRepository) {}

    public function getMissingSnippets(string $baseLocale, int $limitCharacters = -1) : array|false {

        $context = Context::createDefaultContext();

        $isoList = $this->getActiveSalesChannelSnippetSets();

        $baseSnippetSetId = array_search($baseLocale, $isoList);
        if($baseSnippetSetId === \false) {
            return \false;
        }

        $languageFiles = $this->getSnippetFilesByIso($isoList);

        $fileSnippets = $this->getFileSnippets($languageFiles, $isoList);
        $dbSnippets = $this->databaseSnippetsToArray($this->findSnippetInDatabase(new Criteria(), $context), $fileSnippets);

        $snippets = array_replace_recursive($fileSnippets, $dbSnippets);

        $allSnippetKeys = \array_keys($snippets[$baseSnippetSetId]["snippets"]);

        $missingSnippets = [];
        foreach ($allSnippetKeys as $snippetKey) {
            $missing = \false;
            $missingSnippet = [];
            $missingSnippet["key"] = $snippetKey;
            foreach ($isoList as $key => $value) {
                if(isset($snippets[$key]["snippets"][$snippetKey]) && isset($snippets[$key]["snippets"][$snippetKey]) !== "") {
                    if($limitCharacters > 0) {
                        $missingSnippet[$value] = \mb_strimwidth($snippets[$key]["snippets"][$snippetKey], 0, $limitCharacters, '...');
                    } else {
                        $missingSnippet[$value] = $snippets[$key]["snippets"][$snippetKey];
                    }
                } elseif (isset($snippets[$baseSnippetSetId]["snippets"][$snippetKey])) {
                    $missingSnippet[$value] = "";
                    $missing = \true;
                }
            }
            if($missing) {
                $missingSnippets[] = $missingSnippet;
            }
        }

        return $missingSnippets;
    }

    private function getActiveSalesChannelSnippetSets() : array {
        $query = "SELECT LOWER(HEX(`id`)), `snippet_set`.`iso` FROM `snippet_set` WHERE `iso` IN (
            SELECT `locale`.`code` FROM `locale`
            INNER JOIN `language` ON `language`.`locale_id` = `locale`.`id`
            INNER JOIN `sales_channel_language` ON `sales_channel_language`.`language_id` = `language`.`id`
        )";
        return $this->connection->fetchAllKeyValue($query);
    }

    /**
     * Second parameter $unusedThemes is used for external dependencies
     *
     * @param list<string> $unusedThemes
     *
     * @return array<string, string>
     */
    protected function fetchSnippetsFromDatabase(string $snippetSetId, array $unusedThemes = []): array
    {
        /** @var array<string, string> $snippets */
        $snippets = $this->connection->fetchAllKeyValue('SELECT translation_key, value FROM snippet WHERE snippet_set_id = :snippetSetId', [
            'snippetSetId' => Uuid::fromHexToBytes($snippetSetId),
        ]);

        return $snippets;
    }

    /**
     * @param array<string, string> $isoList
     *
     * @return array<string, array<int, AbstractSnippetFile>>
     */
    private function getSnippetFilesByIso(array $isoList): array
    {
        $result = [];
        foreach ($isoList as $iso) {
            $result[$iso] = $this->snippetFileCollection->getSnippetFilesByIso($iso);
        }

        return $result;
    }

    /**
     * @param array<int, AbstractSnippetFile> $languageFiles
     *
     * @return array<string, array<string, string|null>>
     */
    private function getSnippetsFromFiles(array $languageFiles, string $setId): array
    {
        $result = [];
        foreach ($languageFiles as $snippetFile) {
            $json = json_decode((string) file_get_contents($snippetFile->getPath()), true);

            $jsonError = json_last_error();
            if ($jsonError !== 0) {
                throw new \RuntimeException(sprintf('Invalid JSON in snippet file at path \'%s\' with code \'%d\'', $snippetFile->getPath(), $jsonError));
            }

            $flattenSnippetFileSnippets = $this->flatten(
                $json,
                '',
                ['author' => $snippetFile->getAuthor(), 'id' => null, 'setId' => $setId]
            );

            $result = array_replace_recursive(
                $result,
                $flattenSnippetFileSnippets
            );
            
        }

        return $result;
    }

    /**
     * @param array<string, array<int, AbstractSnippetFile>> $languageFiles
     * @param array<string, string> $isoList
     *
     * @return array<string, array<string, array<string, array<string, string|null>>>>
     */
    private function getFileSnippets(array $languageFiles, array $isoList): array
    {
        $fileSnippets = [];

        foreach ($isoList as $setId => $iso) {
            $fileSnippets[$setId]['snippets'] = $this->getSnippetsFromFiles($languageFiles[$iso], $setId);
        }

        return $fileSnippets;
    }

    /**
     * @param array<string, Entity> $queryResult
     * @param array<string, array<string, array<string, array<string, string|null>>>> $fileSnippets
     *
     * @return array<string, array<string, array<string, array<string, string|null>>>>
     */
    private function databaseSnippetsToArray(array $queryResult, array $fileSnippets): array
    {
        $result = [];
        /** @var SnippetEntity $snippet */
        foreach ($queryResult as $snippet) {
            $currentSnippet = array_intersect_key(
                $snippet->jsonSerialize(),
                array_flip([
                    'author',
                    'id',
                    'setId',
                    'translationKey',
                    'value',
                ])
            );

            $currentSnippet['origin'] = '';
            $currentSnippet['resetTo'] = $fileSnippets[$snippet->getSetId()]['snippets'][$snippet->getTranslationKey()]['origin'] ?? $snippet->getValue();
            $currentSnippet = $currentSnippet['value'];
            $result[$snippet->getSetId()]['snippets'][$snippet->getTranslationKey()] = $currentSnippet;
        }

        return $result;
    }

    /**
     * @return array<string, Entity>
     */
    private function findSnippetInDatabase(Criteria $criteria, Context $context): array
    {
        return $this->snippetRepository->search($criteria, $context)->getEntities()->getElements();
    }

    /**
     * @param array<string, string|array<string, mixed>> $array
     * @param array<string, string|null>|null $additionalParameters
     *
     * @return array<string, string|array<string, mixed>>
     */
    private function flatten(array $array, string $prefix = '', ?array $additionalParameters = null): array
    {
        $result = [];
        foreach ($array as $index => $value) {
            $newIndex = $prefix . (empty($prefix) ? '' : '.') . $index;

            if (\is_array($value)) {
                $result = [...$result, ...$this->flatten($value, $newIndex, $additionalParameters)];
            } else {
                if (!empty($additionalParameters)) {
                    $result[$newIndex] = array_merge([
                        'value' => $value,
                        'origin' => $value,
                        'resetTo' => $value,
                        'translationKey' => $newIndex,
                    ], $additionalParameters);
                    $result[$newIndex] = $result[$newIndex]["value"];

                    continue;
                }

                $result[$newIndex] = $value;
            }
        }

        return $result;
    }

}
