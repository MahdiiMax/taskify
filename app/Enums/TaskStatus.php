<?php

namespace App\Enums;

enum TaskStatus: string
{
    /**
     * Task is not yet started.
     */
    case PENDING = 'pending';

    /**
     * Task is currently being worked on.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * Task has been completed.
     */
    case DONE = 'done';
}
