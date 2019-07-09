<?php

namespace Glitchbl\Backup\Traits;

trait HasLogger {
    /**
     * @param string $type Log type
     * @param string $message Log message
     * @throws \Exception
     */
    protected function log($type, $message)
    {
        if ($this->logger) {
            $class_name = explode('\\', get_class($this));
            $class_name = end($class_name);
            if (method_exists($this->logger, $type)) {
                call_user_func([$this->logger, $type], "{$class_name}: {$message}");
            } else {
                throw new \Exception("Logger has not method '{$type}'");
            }
        }
    }
}