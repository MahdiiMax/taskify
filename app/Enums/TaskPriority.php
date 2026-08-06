<?php

namespace App\Enums;

enum TaskPriority: string
{
    /**
     * The task is a low priority.
     */
    case LOW = 'low';
    /**
     * The task is a medium priority.
     */
    case MEDIUM = 'medium';
    /**
     * The task is a high priority.
     */
    case HIGH = 'high';
}
