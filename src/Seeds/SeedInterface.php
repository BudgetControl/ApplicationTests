<?php

declare(strict_types=1);

namespace Budgetcontrol\ApplicationTests\Seeds;

/**
 * Interface for seed classes in BudgetControl Application Tests
 * 
 * This interface defines the contract that all seed classes must implement
 * to ensure consistency and proper execution within the seeding system.
 * 
 * @package Budgetcontrol\ApplicationTests\Seeds
 * @author Marco De Felice <marco.defelice@mlabfactory.it>
 */
interface SeedInterface
{
    /**
     * Execute the seed operation
     * 
     * This method contains the main logic for seeding data into the database.
     * It should be idempotent when possible to allow safe re-execution.
     * 
     * @return void
     * @throws \Exception When seeding fails
     */
    public function run(): void;

    /**
     * Get the list of seed dependencies
     * 
     * Returns an array of seed class names that must be executed before this seed.
     * This allows for proper ordering of seed execution based on dependencies.
     * 
     * @return array<string> Array of fully qualified class names of dependent seeds
     */
    public function getDependencies(): array;

    /**
     * Get the seed name/identifier
     * 
     * Returns a unique identifier for this seed. If not implemented,
     * the class name will be used as the default identifier.
     * 
     * @return string The seed identifier
     */
    public function getName(): string;

    /**
     * Check if the seed should be executed
     * 
     * This method allows seeds to determine if they should run based on
     * current database state or other conditions. Return false to skip execution.
     * 
     * @return bool True if the seed should be executed, false otherwise
     */
    public function shouldRun(): bool;

    /**
     * Get seed description
     * 
     * Returns a human-readable description of what this seed does.
     * Useful for logging and documentation purposes.
     * 
     * @return string Description of the seed operation
     */
    public function getDescription(): string;
}