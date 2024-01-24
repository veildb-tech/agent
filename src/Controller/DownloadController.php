<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\AccessDenyException;
use App\Exception\EncryptionException;
use App\Service\Security\Encryptor;
use App\ServiceApi\Actions\ValidateAccessToken;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DumpManagement;
use App\Service\Security\Encryption;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DownloadController extends AbstractController
{
    /**
     * @param ValidateAccessToken $validateAccessToken
     * @param DumpManagement $dumpService
     * @param Encryptor $encryptor
     *
     * Initializes a new instance of the class.
     */
    public function __construct(
        private readonly ValidateAccessToken $validateAccessToken,
        private readonly DumpManagement $dumpService,
        private readonly Encryptor $encryptor
    ) {
    }

    #[Route('/download/{token}/', name: 'app_download', methods: ["POST"])]
    public function downloadAction(string $token, Request $request): JsonResponse | BinaryFileResponse
    {
        $encryptedData = $request->getContent();

        try {
            $this->validateAccessToken->execute($token);

            $decryptedData = $this->encryptor->decryptWithKey($encryptedData);
            $decryptedData = json_decode($decryptedData, true);

            $file = $this->dumpService->getDumpFileByUuid($decryptedData['dumpuuid']);
            if ($file === null) {
                return $this->json(['message' => 'Not found'], 404);
            }
            return $this->file($file);
        } catch (EncryptionException | AccessDenyException $exception) {
            return $this->json([
                'message' => 'Access denied'
            ], 503);
        } catch (
            InvalidArgumentException
            | DecodingExceptionInterface
            | TransportExceptionInterface
            | RedirectionExceptionInterface
            | ClientExceptionInterface
            | ServerExceptionInterface
            | \Exception $exception
        ) {
            return $this->json([
                'message' => 'Error happened'
            ], 404);
        }
    }
}
