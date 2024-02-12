<?php

namespace Fpaipl\Brandy;

use Illuminate\Database\Eloquent\Model;

class Util
{
    /**
     * Convert JSON string to array sum.
     *
     * @param string $quantities The JSON string to convert.
     * @param string $type The type of sum to calculate.
     * @return int The calculated sum.
     */
    public static function calculateQuantity($quantities, $type = 'array')
    {
        $quantity = 0;
        $quantitiesArr = json_decode($quantities, true);

        foreach ($quantitiesArr as $qty) {
            if ($type == 'int') {
                $quantity += $qty;  // $qty is an integer
            } else {
                $quantity += array_sum($qty);
            }
        }

        return $quantity;
    }

    /**
     * Update the status log of a model.
     *
     * @param Model $model The model instance to update.
     * @param string $key The status key to add to the log.
     * @param string $logAttribute The name of the log attribute in the model.
     * @return string Updated log as JSON.
     */
    public static function updateStatusLog(Model $model, $key, $logAttribute = 'log_status_time')
    {
        // Initialize or retrieve the existing log
        $log = $model->{$logAttribute} ? (json_decode($model->{$logAttribute}, true) ?: []) : [];

        // Append the new status and time to the log
        $log[] = ['status' => $key, 'time' => now()->toDateTimeString()];

        // Return the updated log as JSON
        return json_encode($log);
    }
}
