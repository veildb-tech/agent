<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\File;
use App\ServiceApi\Actions\GetDumpByUuid;

class DownloadController extends AbstractController
{

    public function __construct(
        private readonly GetDumpByUuid $dumpService
    ) {
    }

    #[Route('/download', name: 'app_download')]
    public function index(): JsonResponse | BinaryFileResponse
    {
        $encryptedData = file_get_contents('php://input');
        $keys = explode("\n", file_get_contents('/app/config/keys'));

        $decrypted = false;
        foreach ($keys as $key) {
            if (empty($key)) continue;
            $key = "-----BEGIN PRIVATE KEY-----\n" . $key . "\n-----END PRIVATE KEY-----";
            if (openssl_private_decrypt($encryptedData, $decryptedData, trim($key))) {
                $decrypted = true;
            }
        }

        if (!$decrypted) {
            return $this->json([
                'message' => 'Access denied'
            ], 503);
        }

        $decryptedData = json_decode($decryptedData, true);
        $dumpService = $this->dumpService->execute($decryptedData['dumpuuid']);

        if ($dumpService && !empty($dumpService['filename'])) {

            $file = new File('/app/dumps/processed/' . $decryptedData['dbuuid'] . '/' . $dumpService['filename']);
            return $this->file($file);
        } else {
            return $this->json([
                'message' => 'Not found'
            ], 404);
        }
//        var_dump($dumpService);
//        exit;
//
//
////        var_dump($keys);
//        exit;
//        return $this->json([
//            'message' => 'Welcome to your new controller!',
//            'path' => 'src/Controller/DownloadController.php',
//        ]);
    }
}
