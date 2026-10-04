<?php
namespace App\Enums;
enum MessageStatus: string { case Queued = 'queued'; case Sent = 'sent'; case Failed = 'failed'; }
