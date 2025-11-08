<?php

namespace App\Console\Commands;

use App\Models\Sensor;
use Illuminate\Console\Command;

class SetSensorAlias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensor:alias
                            {device_id? : The device ID or friendly name to update}
                            {alias? : The new alias/name for the sensor}
                            {--list : List all sensors with their aliases}
                            {--auto : Auto-generate friendly names for all sensors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage sensor aliases (friendly names)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // List all sensors
        if ($this->option('list')) {
            return $this->listSensors();
        }

        // Auto-generate names
        if ($this->option('auto')) {
            return $this->autoGenerateNames();
        }

        $deviceId = $this->argument('device_id');
        $alias = $this->argument('alias');

        // Interactive mode if no arguments
        if (!$deviceId) {
            return $this->interactiveMode();
        }

        // Set alias
        if ($deviceId && $alias) {
            return $this->setAlias($deviceId, $alias);
        }

        $this->error('Please provide both device_id and alias, or use --list or --auto');
        return Command::FAILURE;
    }

    /**
     * List all sensors with their aliases
     */
    private function listSensors(): int
    {
        $sensors = Sensor::all();

        if ($sensors->isEmpty()) {
            $this->warn('No sensors found.');
            return Command::SUCCESS;
        }

        $this->info('📡 All Sensors:');
        $this->newLine();

        $table = [];
        foreach ($sensors as $sensor) {
            $table[] = [
                'ID' => substr($sensor->id, 0, 8) . '...',
                'Device ID' => $sensor->device_id,
                'Alias/Name' => $sensor->name ?? '(none)',
                'Model' => $sensor->model,
                'Status' => $sensor->status,
                'Last Seen' => $sensor->last_seen ? $sensor->last_seen->diffForHumans() : 'Never',
            ];
        }

        $this->table(['ID', 'Device ID', 'Alias/Name', 'Model', 'Status', 'Last Seen'], $table);

        return Command::SUCCESS;
    }

    /**
     * Auto-generate friendly names for all sensors
     */
    private function autoGenerateNames(): int
    {
        $sensors = Sensor::whereNull('name')->orWhere('name', '')->get();

        if ($sensors->isEmpty()) {
            $this->info('All sensors already have names.');
            return Command::SUCCESS;
        }

        $this->info("Auto-generating friendly names for {$sensors->count()} sensor(s)...");
        $this->newLine();

        $updated = 0;
        foreach ($sensors as $sensor) {
            $friendlyName = $sensor->generateFriendlyName();
            $sensor->name = $friendlyName;
            $sensor->save();

            $this->line("✓ {$sensor->device_id} → {$friendlyName}");
            $updated++;
        }

        $this->newLine();
        $this->info("✅ Updated {$updated} sensor(s)");

        return Command::SUCCESS;
    }

    /**
     * Set alias for a specific sensor
     */
    private function setAlias(string $deviceId, string $alias): int
    {
        // Find sensor by device_id or current name
        $sensor = Sensor::where('device_id', $deviceId)
            ->orWhere('name', $deviceId)
            ->first();

        if (!$sensor) {
            $this->error("Sensor not found: {$deviceId}");
            $this->info('Use --list to see all sensors');
            return Command::FAILURE;
        }

        $oldName = $sensor->name ?? $sensor->device_id;
        $sensor->name = $alias;
        $sensor->save();

        $this->info("✅ Sensor alias updated:");
        $this->line("   Device ID: {$sensor->device_id}");
        $this->line("   Old Name: {$oldName}");
        $this->line("   New Name: {$alias}");

        return Command::SUCCESS;
    }

    /**
     * Interactive mode
     */
    private function interactiveMode(): int
    {
        $sensors = Sensor::all();

        if ($sensors->isEmpty()) {
            $this->warn('No sensors found. Sync sensors first with: php artisan dingtek:sync');
            return Command::FAILURE;
        }

        $this->info('📡 Sensor Alias Manager');
        $this->newLine();

        // Display sensors
        $choices = [];
        foreach ($sensors as $sensor) {
            $displayName = $sensor->name ?? '(no alias)';
            $choices[$sensor->id] = "{$sensor->device_id} [{$displayName}]";
        }

        $sensorId = $this->choice('Select sensor to update', $choices);
        $sensor = Sensor::find($sensorId);

        if (!$sensor) {
            $this->error('Sensor not found');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Current Details:');
        $this->line("  Device ID: {$sensor->device_id}");
        $this->line("  Current Name: " . ($sensor->name ?? '(none)'));
        $this->line("  Model: {$sensor->model}");
        $this->newLine();

        // Suggest friendly name
        $suggestedName = $sensor->generateFriendlyName();
        $this->line("💡 Suggested name: {$suggestedName}");
        $this->newLine();

        $newAlias = $this->ask('Enter new alias (or press Enter to use suggested name)', $suggestedName);

        if (!$newAlias) {
            $this->warn('Cancelled. No changes made.');
            return Command::SUCCESS;
        }

        $sensor->name = $newAlias;
        $sensor->save();

        $this->newLine();
        $this->info("✅ Sensor alias updated to: {$newAlias}");

        return Command::SUCCESS;
    }
}
