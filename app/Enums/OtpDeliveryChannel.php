<?php

namespace App\Enums;

enum OtpDeliveryChannel: string
{
    /** Code was handed to the SMS gateway and delivered by SMS. */
    case Sms = 'sms';

    /** Gateway was unreachable — code waits in the admin panel for manual delivery. */
    case Manual = 'manual';
}
