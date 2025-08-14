<?php
declare(strict_types=1);
use Budgetcontrol\ApplicationTests\Seeds\SeedInterface;
use Phinx\Seed\AbstractSeed;

/**
 * Example seed class for BudgetControl Application Tests
 * 
 * This is a template file that gets copied to your project's database/seeds directory
 * after installing budgetcontrol/application-tests package.
 * 
 * You can customize this seed or create additional seed files based on your needs.
 */
class SeedExample extends AbstractSeed implements SeedInterface
{
    /**
     * Execute the seed
     * 
     * @return void
     */
    public function run(): void
    {
        // Example seed implementation
        // Add your database seeding logic here
        
        echo "Running " . $this->getName() . "...\n";
        echo $this->getDescription() . "\n";
        
        // Example: Insert test data
        // DB::table('users')->insert([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        // ]);
        
        echo "SeedExample completed successfully.\n";
    }
    
    /**
     * Get seed dependencies
     * 
     * @return array<string>
     */
    public function getDependencies(): array
    {
        return [
            \Budgetcontrol\Seeds\Resources\Seed::class,
            // List other seed classes that should run before this one
            // Example: OtherSeedClass::class
        ];
    }

    /**
     * Get the seed name/identifier
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'SeedExample';
    }

    /**
     * Check if the seed should be executed
     * 
     * @return bool
     */
    public function shouldRun(): bool
    {
        // Add your logic to determine if this seed should run
        // For example, check if data already exists:
        // return DB::table('users')->count() === 0;
        
        return true; // Always run by default
    }

    /**
     * Get seed description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return 'Example seed that demonstrates the basic structure and usage of BudgetControl seeds';
    }
}
