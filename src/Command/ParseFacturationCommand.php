<?php

namespace App\Command;

use App\CdoDemoApi\MemberFetcher;
use App\CdoDemoApi\ProviderFetcher;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(
        private readonly ProviderFetcher $providerFetcher,
        private readonly MemberFetcher $memberFetcher,
        private readonly EntityManagerInterface $entityManager,
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
        $databaseProviders = [];
        $databaseMembers = [];

        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('filePath');
        $limit = $input->getOption('limit');
        $chunkSize = $input->getOption('chunckSize');

        // Si la limite n'est pas infinie, alors on check si on a bien uniquement des nombres, et pas zéro
        if ($limit !== self::INFINITY) {

            if ((!ctype_digit($limit) || (int)$limit === 0)) {
                $io->error('Limit must be an integer > 0.');
                return Command::FAILURE;
            }

            $limit = ((int)$limit) - 1;
        }

        if (!ctype_digit($chunkSize) || (int)$chunkSize === 0) {
            $io->error('Chunk size must be an integer > 0.');
            return Command::FAILURE;
        }

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
                if ($limit !== null && $lineNumber > $limit) {
                    break;
                }
//
//            if ($lineNumber % 1000 === 0) {
////                $io->info("line $lineNumber");
//                $this->entityManager->clear();
//            }

                $io->progressAdvance();

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

                $productTtc = (float)$htProduct * (1.0 + (float)$vatProduct * 0.01);
                $transportTtc = (float)$htTransport * (1.0 + (float)$vatTransport * 0.01);

                if (bccomp((float)$totalTtc, $productTtc + $transportTtc, 7) !== 0) {
                    $io->error("skipping line $lineNumber due to price error");
                    continue;
                }

                if (!array_key_exists($providerCode, $databaseProviders)) {
                    $databaseProviders[$providerCode] = $this->providerFetcher->getProviderFromCode($providerCode);
                }

                if (!array_key_exists($memberCode, $databaseMembers)) {
                    $databaseMembers[$memberCode] = $this->memberFetcher->getMemberFromCode($memberCode);
                }
            } catch (\Throwable $exception) {
                $io->error($exception->getMessage());
                continue;
            }
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }
}
