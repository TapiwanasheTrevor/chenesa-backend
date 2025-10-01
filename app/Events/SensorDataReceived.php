<?php

namespace App\Events;

use App\Models\Sensor;
use App\Models\SensorReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorDataReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sensor;
    public $sensorReading;

    /**
     * Create a new event instance.
     */
    public function __construct(Sensor $sensor, SensorReading $sensorReading)
    {
        $this->sensor = $sensor;
        $this->sensorReading = $sensorReading;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sensors'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sensor.data.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'sensor_id' => $this->sensor->id,
            'device_id' => $this->sensor->device_id,
            'reading_id' => $this->sensorReading->id,
            'battery_level' => $this->sensor->battery_level,
            'signal_strength' => $this->sensor->signal_strength,
            'water_level_percentage' => $this->sensorReading->water_level_percentage,
            'timestamp' => $this->sensorReading->created_at->toISOString(),
        ];
    }
}
