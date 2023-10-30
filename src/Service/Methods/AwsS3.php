<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Service\InputOutput;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use \Exception;

class AwsS3 extends AbstractMethod
{
    /**
     * @var S3Client|null
     */
    private ?S3Client $client = null;

    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     * @throws Exception
     * @throws S3Exception
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $destFile = $this->getDestinationFile($dbUuid, $filename);
        $client = $this->getClient($dbConfig);
        $client->getObject(array(
            'Bucket' => $dbConfig['aws_s3_bucket'],
            'Key' => $dbConfig['aws_s3_filename'],
            'SaveAs' => $destFile
        ));

        return $destFile;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'aws-s3';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'AWS S3';
    }

    /**
     * @param array $config
     * @return bool
     * @throws Exception
     */
    public function validate(array $config): bool
    {
        $client = $this->getClient($config);
        $objects = $client->listObjects([
            'Bucket' => $config['aws_s3_bucket']
        ]);

        $found = false;
        foreach ($objects['Contents']  as $object) {
            if ($object['Key'] === $config['aws_s3_filename']) {
                $found = true;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput): array
    {
        return [
            'aws_s3_key' => $inputOutput->ask("AWS Key", null, self::validateRequired(...)),
            'aws_s3_secret' => $inputOutput->ask("AWS Secret", null, self::validateRequired(...)),
            'aws_s3_bucket' => $inputOutput->ask("Bucket name", null, self::validateRequired(...)),
            'aws_s3_region' => $inputOutput->ask("Region", null, self::validateRequired(...)),
            'aws_s3_version' => $inputOutput->ask("Version", 'latest', self::validateRequired(...)),
            'aws_s3_filename' => $inputOutput->ask("Filename", 'backup.sql', self::validateRequired(...)),
        ];
    }

    /**
     * Retrieve S3 client
     *
     * @param array $config
     * @return S3Client
     */
    private function getClient(array $config): S3Client
    {
        if ($this->client === null) {
            $this->client = new S3Client([
                'version' => $config['aws_s3_version'],
                'region'  => $config['aws_s3_region'],
                'credentials' => [
                    'key'    => $config['aws_s3_key'],
                    'secret' => $config['aws_s3_secret'],
                ],
            ]);
        }

        return $this->client;
    }
}
