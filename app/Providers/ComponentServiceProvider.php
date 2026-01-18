<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Directory where components are located
        $componentsPath = app_path('Http/Components');

        // Get all PHP files in the components directory
        $files = File::files($componentsPath);

        foreach ($files as $file) {
            // Get the class name from the file path
            $className = $this->getClassNameFromFile($file);

            if ($className) {
                // Bind the class to the service container
                $this->app->bind($className, function ($app) use ($className) {
                    return new $className();
                });
            }
        }
    }
    protected function getClassNameFromFile($file)
    {
        $filePath = $file->getPathname();
        $relativePath = str_replace(app_path() . '/', '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // Construct the class name
        $namespace = 'App\Http\Components';
        $className = $namespace . '\\' . $relativePath;

        // Check if the class exists
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
