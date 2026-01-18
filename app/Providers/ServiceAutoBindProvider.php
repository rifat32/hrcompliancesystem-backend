<?php

namespace App\Providers;

class ServiceAutoBindProvider extends AutoBindServiceProvider
{
    protected string $relativePath = 'Services';
    protected string $baseNamespace = 'App\Services';
}
