<?php

namespace App\Service\Actions;

use App\Service\AppService;

class GetDump
{
    private string $uri = 'project_dump_statuses';
    private string $method = 'GET';

    private AppService $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
    }

    public function getScheduledDumps(): array
    {
        $this->appService->sendRequest([
            'uri' => $this->uri,
            'method' => 'GET',
            'body' => [
                'project.code' => 'sdfs981nsa'
            ]
        ]);

        return [];
    }


/*    private string $uri = 'project_dump_statuses';

    private array $query = [
        'project.code' => 'sdfs981nsa'
    ];

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getQuery(): array
    {
        return $this->query;
    }*/
}
