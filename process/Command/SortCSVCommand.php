<?php

namespace App\Command;

use ErrorException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sort `data.csv` file.
 *
 * @package App\Command
 */
class SortCSVCommand extends AbstractCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'sort-csv';

    /**
     * {@inheritdoc}
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Sort `data.csv` file.');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            parent::execute($input, $output);

            $csvPath = sprintf('%s/%s', $this->cityDir, GeoJSONCommand::FILENAME_CSV);
            if (!file_exists($csvPath) || !is_readable($csvPath)) {
                throw new ErrorException(sprintf('File "%s" doesn\'t exist or is not readable.', $csvPath));
            }

            $csv = self::read($csvPath);

            $csv = self::sort($csv);

            self::write($csvPath, $csv);

            return Command::SUCCESS;
        } catch (Exception $error) {
            $output->writeln(sprintf('<error>%s</error>', $error->getMessage()));

            return Command::FAILURE;
        }
    }

    /**
     * Read CSV file.
     *
     * @param string $path Path of CSV file.
     * @return array
     */
    private static function read(string $path): array
    {
        $csv = [];

        if (($fp = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($fp)) !== false) {
                $csv[] = $data;
            }
            fclose($fp);
        }

        // Remove CSV header
        array_shift($csv);

        return $csv;
    }

    /**
     * Wite CSV file.
     *
     * @param string $path Path of CSV file.
     * @param array $csv
     * @return void
     */
    private static function write(string $path, array $csv): void
    {
        array_unshift($csv, ['type', 'id', 'name', 'gender', 'person', 'description']);

        $fp = fopen($path, 'w');

        foreach ($csv as $record) {
            fputcsv($fp, $record);
        }

        fclose($fp);
    }

    /**
     * Remove accents from string.
     *
     * @param string $string
     * @param string $charset
     * @return string
     */
    private static function normalize(string $string, string $charset = 'utf-8'): string
    {
        $str = htmlentities($string, ENT_NOQUOTES, $charset);

        /** @var string */ $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        /** @var string */ $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        /** @var string */ $str = preg_replace('#&[^;]+;#', '', $str);

        $str = strtolower($str);

        return $str;
    }

    /**
     * Sort CSV array by type, gender, street name, and id.
     *
     * @param array $csv
     * @return array
     */
    private static function sort(array $csv): array
    {
        $name = array_map(
            function ($record): string {
                return self::normalize($record[2]);
            },
            $csv
        );

        array_multisort(
            array_column($csv, 0), // type
            SORT_ASC,
            array_column($csv, 3), // gender
            SORT_ASC,
            $name,
            SORT_ASC,
            array_column($csv, 1), // id
            SORT_ASC,
            $csv
        );

        return $csv;
    }
}