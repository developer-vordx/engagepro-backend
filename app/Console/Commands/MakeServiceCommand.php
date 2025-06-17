<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name : The name of the service class (e.g. Auth/LoginService)}';
    protected $description = 'Create a new service class, its contract, and bind them in a provider';

    public function handle()
    {
        $input = str_replace('\\', '/', $this->argument('name'));
        $className = class_basename($input);
        // Check if 'Service' is already at the end of the class name
        $serviceName = Str::endsWith($className, 'Service') ? $className : $className . 'Service';
        $interfaceName = $className . 'Interface';

        $subPath = trim(dirname($input), '/');
        $serviceDir = app_path('Services' . ($subPath ? '/' . $subPath : ''));
        $contractDir = app_path('Contracts' . ($subPath ? '/' . $subPath : ''));

        if (!File::exists($serviceDir)) File::makeDirectory($serviceDir, 0755, true);
        if (!File::exists($contractDir)) File::makeDirectory($contractDir, 0755, true);

        $servicePath = "$serviceDir/$serviceName.php";
        $contractPath = "$contractDir/$interfaceName.php";

        if (File::exists($servicePath) || File::exists($contractPath)) {
            $this->components->error("Service or contract already exists.");
            return self::FAILURE;
        }

        $serviceNamespace = 'App\\Services' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');
        $contractNamespace = 'App\\Contracts' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');

        $interfaceTemplate = <<<PHP
        <?php

        namespace $contractNamespace;

        interface $interfaceName
        {
            public function handle(\$request);
        }

        PHP;

        $serviceTemplate = <<<PHP
        <?php

        namespace $serviceNamespace;

        use App\Utils\BaseService;
        use $contractNamespace\\$interfaceName;

        class $serviceName extends  BaseService  implements $interfaceName
        {
            public function handle(\$request)
            {
                // TODO: Implement service logic
            }
        }

        PHP;

        File::put($contractPath, $interfaceTemplate);
        File::put($servicePath, $serviceTemplate);

        $this->components->info("Service created: $servicePath");
        $this->components->info("Contract created: $contractPath");

        $this->bindInServiceProvider($contractNamespace . '\\' . $interfaceName, $serviceNamespace . '\\' . $serviceName);

        return self::SUCCESS;
    }

    protected function bindInServiceProvider(string $interfaceFQCN, string $serviceFQCN): void
    {
        $providerPath = app_path('Providers/ServiceBindingProvider.php');
        $binding = "\$this->app->bind(\\$interfaceFQCN::class, \\$serviceFQCN::class);";

        // Create the provider if not exists
        if (!File::exists($providerPath)) {
            $providerTemplate = <<<PHP
            <?php

            namespace App\Providers;

            use Illuminate\Support\ServiceProvider;

            class ServiceBindingProvider extends ServiceProvider
            {
                public function register()
                {
                    // Bindings will be appended here
                }

                public function boot(): void {}
            }
            PHP;
            File::put($providerPath, $providerTemplate);
            $this->components->info('Created ServiceBindingProvider');
        }

        // Add binding if not already there
        $providerContent = File::get($providerPath);

        if (!Str::contains($providerContent, $binding)) {
            $providerContent = str_replace(
                '// Bindings will be appended here',
                "$binding\n        // Bindings will be appended here",
                $providerContent
            );
            File::put($providerPath, $providerContent);
            $this->components->info("Bound $interfaceFQCN to $serviceFQCN in ServiceBindingProvider.");
        } else {
            $this->components->info("Binding already exists in ServiceBindingProvider.");
        }
    }
}
