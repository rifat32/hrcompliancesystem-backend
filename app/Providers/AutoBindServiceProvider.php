<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AutoBindServiceProvider extends ServiceProvider
{
    /**
     * Folder path relative to app/ to scan for classes to bind
     * Example: 'Services' or 'Http/Components'
     */
    protected string $relativePath = 'Services';

    /**
     * Base namespace for classes in the folder
     * Example: 'App\Services' or 'App\Http\Components'
     */
    protected string $baseNamespace = 'App\Services';

    public function register()
    {
        $path = app_path($this->relativePath);

        if (!File::exists($path)) {
            return;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className) {
                $this->app->bind($className, fn($app) => new $className());
            }
        }
    }

    protected function getClassNameFromFile($file)
    {
        $filePath = $file->getRealPath();

        // Get relative path from app/
        $relativePath = str_replace(app_path() . DIRECTORY_SEPARATOR, '', $filePath);

        // Remove .php extension
        $relativePath = substr($relativePath, 0, -4);

        // Convert directory separators to namespace separators
        $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        $className = $relativeNamespace;

        // Check if class exists
        if (class_exists($className)) {
            return $className;
        }

        // Sometimes class autoloading isn't warmed up - you can optionally require_once
        // require_once $filePath;
        // if (class_exists($className)) {
        //     return $className;
        // }

        return null;
    }

    public function boot()
    {
        //
    }
}
