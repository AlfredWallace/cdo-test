<?php

namespace App\Command;

use App\CdoDemoApi\MemberFetcher;
use App\CdoDemoApi\ProviderFetcher;
use App\Entity\Document;
use App\Entity\Member;
use App\Entity\Provider;
use App\Exception\CdoDemoException;
use App\Exception\CsvFormatException;
use App\Repository\DocumentRepository;
use App\Repository\MemberRepository;
use App\Repository\ProviderRepository;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:parse-facturation',
    description: "Parses a Facturation CSV file then checks & inserts data in DB.",
)]
class ParseFacturationCommand extends Command
{
    private const INFINITY = "infinity";
    private const CSV_NB_COLS = 9;

    private array $providers = [];
    private array $members = [];

    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly MemberFetcher $memberFetcher,
        private readonly MemberRepository $memberRepository,
        private readonly ProviderFetcher $providerFetcher,
        private readonly ProviderRepository $providerRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filePath', InputArgument::REQUIRED, "Path of Facturation file to parse.")
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit number of lines to parse.', self::INFINITY)
            ->addOption('chunckSize', 'c', InputOption::VALUE_REQUIRED, 'Number of rows to save in one batch.', "50");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info("Loading providers from API...");
            $this->providers = $this->providerFetcher->getAvailableProvidersFromApi();
            $io->info(count($this->providers) . " providers available.");

            $io->info("Loading members from API...");
            $this->members = $this->memberFetcher->getAvailableMembersFromApi();
            $io->info(count($this->members) . " members available.");
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->info("Starting facturation file analysis...");
        $linesToSave = [];

        $filePath = $input->getArgument('filePath');
        $limit = $input->getOption('limit');
        $chunkSize = $input->getOption('chunckSize');

        // Si la limite n'est pas infinie, alors on check si on a bien uniquement des nombres, et pas zéro
        if ($limit !== self::INFINITY) {
            if ((!ctype_digit($limit) || (int)$limit === 0)) {
                $io->error('Limit must be an integer > 0.');
                return Command::FAILURE;
            }

            $limit = (int)$limit;
        }

        if (!ctype_digit($chunkSize) || (int)$chunkSize === 0) {
            $io->error('Chunk size must be an integer > 0.');
            return Command::FAILURE;
        }
        $chunkSize = (int)$chunkSize;

        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::DROP_NEW_LINE |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY
        );
        $file->setCsvControl(";");

        $io->progressStart();
        foreach ($file as $lineNumber => $line) {
            try {
                if ($limit !== null && $lineNumber + 1 > $limit) {
                    $io->success("Exiting at limit $limit.");
                    break;
                }

                $io->progressAdvance();

                $linesToSave[] = $this->getSanitizedLine($lineNumber, $line);

                if (count($linesToSave) % $chunkSize === 0) {
                    try {
                        $this->saveLines($linesToSave);
                    } catch (\Throwable $exception) {
                        $io->error($exception->getMessage());
                    }
                    unset($linesToSave);
                    $linesToSave = [];
                }
            } catch (\Throwable $exception) {
                // todo write error file
                continue;
            }
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }

    private function saveLines(array $lines): void
    {
        $members = [];
        $providers = [];
        $documents = [];

        foreach ($lines as $line) {
            [
                $providerCode,
                $documentNumber,
                $memberCode,
                $documentType,
                $htProduct,
                $vatProduct,
                $htTransport,
                $vatTransport,
                $totalTtc
            ] = $line;

            $document = $this->documentRepository->findOneBy(['number' => $documentNumber]);
            if ($document !== null) {
                continue;
            }
            $document = new Document();
            $document
                ->setType($documentType)
                ->setNumber($documentNumber)
                ->setHtProduct($htProduct)
                ->setVatProduct($vatProduct)
                ->setHtTransport($htTransport)
                ->setVatTransport($vatTransport)
                ->setTotalTtc($totalTtc);
            $this->documentRepository->saveDocument($document);
            $documents[] = $document;

            if (!array_key_exists($providerCode, $providers)) {
                $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);
                if ($provider === null) {
                    $provider = new Provider();
                    $provider
                        ->setCode($providerCode)
                        ->setName($this->providers[$providerCode]);
                    $this->providerRepository->saveProvider($provider);
                }
                $providers[$providerCode] = $provider;
            }

            if (!array_key_exists($memberCode, $members)) {
                $member = $this->memberRepository->findOneBy(['code' => $memberCode]);
                if ($member === null) {
                    $member = new Member();
                    $member
                        ->setCode($memberCode)
                        ->setName($this->members[$memberCode]);
                    $this->memberRepository->saveMember($member);
                }
                $members[$memberCode] = $member;
            }
        }

        if (!empty($members)) {
            $this->memberRepository->flushMembers();
        }

        if (!empty($providers)) {
            $this->providerRepository->flushProviders();
        }

        if (!empty($documents)) {
            $this->documentRepository->flushDocuments();
        }

        $this->memberRepository->clearMembers();
        $this->providerRepository->clearProviders();
        $this->documentRepository->clearDocuments();
    }

    private function getSanitizedLine(int $lineNumber, array $line): array
    {
        if (($nbCols = count($line)) !== self::CSV_NB_COLS) {
            throw new CsvFormatException(
                "Skipping line $lineNumber for having $nbCols columns instead of " . self::CSV_NB_COLS . "."
            );
        }

        [
            $providerCode,
            $documentNumber,
            $memberCode,
            $documentType,
            $htProduct,
            $vatProduct,
            $htTransport,
            $vatTransport,
            $totalTtc
        ] = $line;

        $sanitizedLine = $line;
        $codeRegex = '/^[A-Z0-9]{7}$/';
        $priceRegex = '/^\d+(\.\d{1,2})?$/';

        // code fournisseur
        if (preg_match($codeRegex, $providerCode) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'code fournisseur' format.");
        }
        if (!array_key_exists($providerCode, $this->providers)) {
            throw new CdoDemoException("Skipping line $lineNumber : invalid provider $providerCode.");
        }

        // numéro de document
        if (preg_match('/^[a-zA-Z0-9]{1,255}$/', $documentNumber) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'numéro de document' format.");
        }

        // code adhérent
        if (preg_match($codeRegex, $memberCode) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'code adhérent' format.");
        }
        if (!array_key_exists($memberCode, $this->members)) {
            throw new CdoDemoException("Skipping line $lineNumber : invalid member $memberCode.");
        }

        // type de document
        if ($documentType !== 'F' && $documentType !== 'A') {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'type de document' format.");
        }

        // Vérification de 'montant HT'
        if (preg_match($priceRegex, $htProduct) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'montant HT' format.");
        }
        $sanitizedLine[4] = (float)$htProduct;

        // Vérification de 'montant VAT'
        if (preg_match($priceRegex, $vatProduct) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'montant VAT' format.");
        }
        $sanitizedLine[5] = (float)$vatProduct;

        // Vérification de 'transport HT'
        if (preg_match($priceRegex, $htTransport) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'transport HT' format.");
        }
        $sanitizedLine[6] = (float)$htTransport;

        // Vérification de 'transport VAT'
        if (preg_match($priceRegex, $vatTransport) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'transport VAT' format");
        }
        $sanitizedLine[7] = (float)$vatTransport;

        // total TTC
        if (preg_match('/^\d+(\.\d+)?$/', $totalTtc) !== 1) {
            throw new CsvFormatException("Skipping line $lineNumber : invalid 'total TTC' format");
        }
        $sanitizedLine[8] = (float)$totalTtc;

        $productTtc = $htProduct * (1.0 + $vatProduct * 0.01);
        $transportTtc = $htTransport * (1.0 + $vatTransport * 0.01);

        if (bccomp($totalTtc, $productTtc + $transportTtc, 7) !== 0) {
            throw new CsvFormatException("Skipping line $lineNumber due to price integrity error.");
        }

        return $sanitizedLine;
    }
}
