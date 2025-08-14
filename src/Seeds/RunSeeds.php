<?php

declare(strict_types=1);

namespace Budgetcontrol\ApplicationTests\Seeds;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Phinx\Seed\AbstractSeed;

/**
 * Seeds runner class for BudgetControl Application Tests
 * 
 * This class handles the execution of seed classes with dependency resolution,
 * error handling, and logging capabilities.
 * 
 * @package Budgetcontrol\ApplicationTests\Seeds
 * @author Marco De Felice <marco.defelice@mlabfactory.it>
 */
class RunSeeds extends AbstractSeed
{
    /**
     * @var array<string, SeedInterface> Registered seed instances
     */
    private array $seeds = [];

    /**
     * @var array<string> Executed seeds tracker
     */
    private array $executed = [];

    /**
     * @var array<string> Currently executing seeds (for circular dependency detection)
     */
    private array $executing = [];

    /**
     * @var bool Enable verbose output
     */
    private bool $verbose = false;

    /**
     * @var callable|null Custom logger function
     */
    private $logger = null;

    /**
     * Constructor
     * 
     * @param bool $verbose Enable verbose output
     * @param callable|null $logger Custom logger function
     */
    public function __construct(bool $verbose = false, ?callable $logger = null)
    {
        $this->verbose = $verbose;
        $this->logger = $logger;
    }

    /**
     * Phinx run method - required by AbstractSeed
     * 
     * This method is called by Phinx when executing the seed.
     * It automatically discovers and runs all seeds in the resources/seeds directory.
     */
    public function run(): void
    {
        //first run the main seed of application
        $this->log("Running main seed for application...");
        $mainSeed = new \Budgetcontrol\Seeds\Resources\Seed();
        $mainSeed->runAllSeeds();
        
        $this->log("Discovering and running user seeds...");
        $this->discoverAndRunUserSeeds();


    }

    /**
     * Discover and run seeds from the user's resources/seeds directory
     * 
     * @param string|null $customPath Optional custom path to seeds directory
     * @return self
     */
    private function discoverAndRunUserSeeds(?string $customPath = null): self
    {
        $seedsPath = $customPath ?: $this->getUserSeedsPath();
        
        if (!is_dir($seedsPath)) {
            $this->log("Seeds directory not found: {$seedsPath}");
            $this->log("Creating seeds directory...");
            mkdir($seedsPath, 0755, true);
            $this->log("Seeds directory created: {$seedsPath}");
            return $this;
        }

        $this->log("Discovering seeds in: {$seedsPath}");
        
        // Auto-discover seeds from user's directory
        $this->discoverSeeds($seedsPath);
        
        // Run all discovered seeds
        if (empty($this->seeds)) {
            $this->log("No seeds found in {$seedsPath}");
        }

        $this->runAll();
        
        
        return $this;
    }

    /**
     * Get the user's seeds directory path
     * 
     * @return string
     */
    private function getUserSeedsPath(): string
    {
        // Start from current working directory and look for project root
        $currentDir = getcwd();
        
        // Try different possible locations for resources/seeds
        $possiblePaths = [
            $currentDir . '/resources/seeds',
            $currentDir . '/database/seeds',
            dirname($currentDir) . '/resources/seeds',
            dirname($currentDir) . '/database/seeds',
        ];

        // Also check if we're in vendor directory, go back to project root
        if (strpos($currentDir, 'vendor') !== false) {
            $projectRoot = $this->findProjectRoot($currentDir);
            if ($projectRoot) {
                $possiblePaths[] = $projectRoot . '/resources/seeds';
                $possiblePaths[] = $projectRoot . '/database/seeds';
            }
        }

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Default to resources/seeds in current directory
        return $currentDir . '/resources/seeds';
    }

    /**
     * Find project root directory by looking for composer.json
     * 
     * @param string $startPath Starting path to search from
     * @return string|null Project root path or null if not found
     */
    private function findProjectRoot(string $startPath): ?string
    {
        $currentPath = $startPath;
        
        while ($currentPath !== dirname($currentPath)) {
            if (file_exists($currentPath . '/composer.json')) {
                return $currentPath;
            }
            $currentPath = dirname($currentPath);
        }
        
        return null;
    }

    /**
     * Register a seed instance
     * 
     * @param SeedInterface $seed The seed instance to register
     * @return self
     */
    public function registerSeed(SeedInterface $seed): self
    {
        $name = $seed->getName();
        $this->seeds[$name] = $seed;
        $this->log("Registered seed: {$name}");
        
        return $this;
    }

    /**
     * Register multiple seeds
     * 
     * @param array<SeedInterface> $seeds Array of seed instances
     * @return self
     */
    public function registerSeeds(array $seeds): self
    {
        foreach ($seeds as $seed) {
            if (!$seed instanceof SeedInterface) {
                throw new InvalidArgumentException('All seeds must implement SeedInterface');
            }
            $this->registerSeed($seed);
        }
        
        return $this;
    }

    /**
     * Auto-discover and register seeds from a directory
     * 
     * @param string $directory Directory path to scan for seed files
     * @param string $namespace Base namespace for seed classes
     * @return self
     */
    public function discoverSeeds(string $directory, string $namespace = ''): self
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Directory does not exist: {$directory}");
        }

        $this->log("Scanning directory: {$directory}");
        $files = glob($directory . '/*Seed.php');
        
        if (empty($files)) {
            $this->log("No Seed files found in {$directory}");
            return $this;
        }

        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            
            // Skip interface and abstract classes
            if (in_array($className, ['SeedInterface', 'AbstractSeed', 'RunSeeds'])) {
                continue;
            }

            $this->log("Processing file: {$file}");
            
            try {
                require_once $file;
                
                // Try to find the class with different namespace strategies
                $possibleClassNames = [
                    $className, // No namespace
                    $namespace . '\\' . $className, // Provided namespace
                    'App\\Seeds\\' . $className, // Laravel-style
                    'Database\\Seeds\\' . $className, // Another common pattern
                ];

                $seedClass = null;
                foreach ($possibleClassNames as $fullClassName) {
                    if (class_exists($fullClassName)) {
                        $seedClass = $fullClassName;
                        break;
                    }
                }

                if ($seedClass) {
                    $reflection = new \ReflectionClass($seedClass);
                    
                    // Check if implements SeedInterface and is not abstract
                    if ($reflection->implementsInterface(SeedInterface::class) && !$reflection->isAbstract()) {
                        $seed = $reflection->newInstance();
                        $this->registerSeed($seed);
                        $this->log("Successfully registered seed: {$className}");
                    } else {
                        $this->log("Skipping {$className}: does not implement SeedInterface or is abstract");
                    }
                } else {
                    $this->log("Warning: Could not find class {$className} in file {$file}");
                }
                
            } catch (\Throwable $e) {
                $this->log("Error processing {$file}: " . $e->getMessage(), 'error');
                // Continue with other files instead of failing completely
            }
        }
        
        return $this;
    }

    /**
     * Run all registered seeds
     * 
     * @return self
     * @throws Exception When seed execution fails
     */
    public function runAll(): self
    {
        $this->log("Starting seed execution...");
        $this->log("Total seeds registered: " . count($this->seeds));

        foreach ($this->seeds as $name => $seed) {
            $this->runSeed($name);
        }

        $this->log("All seeds completed successfully!");
        $this->log("Total seeds executed: " . count($this->executed));
        
        return $this;
    }

    /**
     * Run a specific seed by name
     * 
     * @param string $name The name of the seed to run
     * @return self
     * @throws Exception When seed execution fails
     */
    public function runSeed(string $name): self
    {
        // Check if seed exists
        if (!isset($this->seeds[$name])) {
            throw new InvalidArgumentException("Seed not found: {$name}");
        }

        // Check if already executed
        if (in_array($name, $this->executed)) {
            $this->log("Seed {$name} already executed, skipping...");
            return $this;
        }

        // Check for circular dependencies
        if (in_array($name, $this->executing)) {
            throw new RuntimeException("Circular dependency detected for seed: {$name}");
        }

        $seed = $this->seeds[$name];

        // Check if seed should run
        if (!$seed->shouldRun()) {
            $this->log("Seed {$name} skipped (shouldRun returned false)");
            $this->executed[] = $name;
            return $this;
        }

        // Mark as currently executing
        $this->executing[] = $name;

        try {
            // Execute dependencies first
            $this->executeDependencies($seed);

            // Execute the seed
            $this->log("Executing seed: {$name}");
            $this->log("Description: " . $seed->getDescription());
            
            $startTime = microtime(true);
            $seed->run();
            $endTime = microtime(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            $this->log("Seed {$name} completed successfully in {$executionTime}ms");

            // Mark as executed
            $this->executed[] = $name;

        } catch (Exception $e) {
            $this->log("Seed {$name} failed: " . $e->getMessage(), 'error');
            throw new RuntimeException("Failed to execute seed {$name}: " . $e->getMessage(), 0, $e);
        } finally {
            // Remove from executing list
            $this->executing = array_filter($this->executing, fn($item) => $item !== $name);
        }

        return $this;
    }

    /**
     * Execute dependencies for a seed
     * 
     * @param SeedInterface $seed The seed whose dependencies to execute
     * @return void
     */
    private function executeDependencies(SeedInterface $seed): void
    {
        $dependencies = $seed->getDependencies();
        
        foreach ($dependencies as $dependencyClass) {
            // Find seed by class name
            $dependencyName = null;
            foreach ($this->seeds as $name => $registeredSeed) {
                if (get_class($registeredSeed) === $dependencyClass || $registeredSeed->getName() === $dependencyClass) {
                    $dependencyName = $name;
                    break;
                }
            }

            if ($dependencyName === null) {
                throw new RuntimeException("Dependency not found: {$dependencyClass}");
            }

            $this->runSeed($dependencyName);
        }
    }

    /**
     * Get list of executed seeds
     * 
     * @return array<string>
     */
    public function getExecutedSeeds(): array
    {
        return $this->executed;
    }

    /**
     * Get list of registered seeds
     * 
     * @return array<string>
     */
    public function getRegisteredSeeds(): array
    {
        return array_keys($this->seeds);
    }

    /**
     * Reset the runner state
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->seeds = [];
        $this->executed = [];
        $this->executing = [];
        
        return $this;
    }

    /**
     * Check if a seed has been executed
     * 
     * @param string $name Seed name
     * @return bool
     */
    public function hasExecuted(string $name): bool
    {
        return in_array($name, $this->executed);
    }

    /**
     * Get seed instance by name
     * 
     * @param string $name Seed name
     * @return SeedInterface|null
     */
    public function getSeed(string $name): ?SeedInterface
    {
        return $this->seeds[$name] ?? null;
    }

    /**
     * Create a configured instance for running user seeds
     * 
     * @param bool $verbose Enable verbose output
     * @param callable|null $logger Custom logger
     * @return static
     */
    public static function createForUserSeeds(bool $verbose = true, ?callable $logger = null): self
    {
        return new static($verbose, $logger);
    }

    /**
     * Quick method to run all seeds from user's resources/seeds directory
     * 
     * @param bool $verbose Enable verbose output
     * @param string|null $customPath Optional custom path to seeds directory
     * @return void
     */
    public static function runUserSeeds(bool $verbose = true, ?string $customPath = null): void
    {
        $runner = static::createForUserSeeds($verbose);
        $runner->discoverAndRunUserSeeds($customPath);
    }

    /**
     * Log a message
     * 
     * @param string $message The message to log
     * @param string $level Log level (info, error, warning)
     * @return void
     */
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            call_user_func($this->logger, $message, $level);
        } elseif ($this->verbose) {
            $timestamp = date('Y-m-d H:i:s');
            $levelUpper = strtoupper($level);
            echo "[{$timestamp}] [{$levelUpper}] {$message}\n";
        }
    }
}