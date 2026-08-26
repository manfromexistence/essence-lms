<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\Student;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;
use InvalidArgumentException;

class SmsService
{
    protected ?string $gateway;
    protected ?string $apiKey;
    protected ?string $senderId;
    protected int $maxRetries = 3;
    protected string $driver;
    protected array $mockConfig;

    public function __construct()
    {
        $this->gateway = Setting::getValue('sms_gateway');
        $this->apiKey = Setting::getValue('sms_api_key');
        $this->senderId = Setting::getValue('sms_sender_id');
        
        // Load SMS configuration
        $this->driver = config('sms.driver', 'mock');
        $this->mockConfig = config('sms.mock', [
            'delivery_delay' => 2,
            'failure_rate' => 10,
            'enable_logging' => true,
            'simulate_status_updates' => true,
        ]);
    }

    /**
     * Send SMS to a single recipient.
     * 
     * Uses mock implementation when driver is set to 'mock'.
     * Logs all SMS attempts to the database.
     * 
     * @param string $phone Recipient phone number
     * @param string $message SMS message content
     * @param array $metadata Additional metadata to store with the log
     * @return SmsLog The created SMS log entry
     * 
     * Validates: Requirements 10.1, 10.2, 15.1, 15.4
     */
    public function send(string $phone, string $message, array $metadata = []): SmsLog
    {
        // Extract type and related info from metadata
        $type = $metadata['type'] ?? 'general';
        $related = $metadata['related'] ?? null;
        
        // Create log entry first (pending status)
        try {
            $log = $this->logSms($phone, $message, 'pending', $metadata);
        } catch (Exception $e) {
            Log::error('Failed to create SMS log entry', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            // Create a minimal log object to return
            $log = new SmsLog([
                'phone' => $phone,
                'message' => $message,
                'status' => 'failed',
                'type' => $type,
            ]);
            return $log;
        }
        
        try {
            // Use mock implementation or real gateway based on driver
            if ($this->driver === 'mock') {
                $result = $this->mockSend($phone, $message);
            } else {
                $result = [
                    'success' => $this->sendViaGateway($phone, $message),
                    'message' => 'Sent via gateway',
                ];
            }
            
            if ($result['success']) {
                $this->updateStatus($log, 'sent');
                
                // Simulate delivery status update for mock
                if ($this->driver === 'mock' && $this->mockConfig['simulate_status_updates']) {
                    // In a real implementation, this would be handled by a webhook or queue job
                    // For mock, we immediately mark as delivered after the delay
                    $this->updateStatus($log, 'delivered');
                }
            } else {
                $this->updateStatus($log, 'failed');
                
                // Log error message if available
                if (isset($result['error']) && $this->mockConfig['enable_logging']) {
                    Log::warning('SMS Send Failed', [
                        'log_id' => $log->id,
                        'phone' => $phone,
                        'error' => $result['error'],
                    ]);
                }
            }
        } catch (QueryException $e) {
            Log::error('Database error during SMS sending', [
                'phone' => $phone,
                'log_id' => $log->id ?? null,
                'error' => $e->getMessage(),
            ]);
            if ($log->exists) {
                $this->updateStatus($log, 'failed');
            }
        } catch (Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'phone' => $phone,
                'log_id' => $log->id ?? null,
            ]);
            if ($log->exists) {
                $this->updateStatus($log, 'failed');
            }
        }
        
        return $log->fresh() ?? $log;
    }

    /**
     * Send bulk SMS to multiple recipients.
     * 
     * Processes all recipients and creates individual SMS log entries.
     * Returns a summary of successful and failed messages.
     * 
     * @param array $recipients Array of recipients (phone numbers or arrays with 'phone' key)
     * @param string $message SMS message content
     * @param array $metadata Additional metadata to store with each log
     * @return array Summary with 'total', 'successful', 'failed', and 'logs' keys
     * 
     * Validates: Requirements 11.2, 11.4
     */
    public function sendBulk(array $recipients, string $message, array $metadata = []): array
    {
        $logs = collect();
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        // Set default type for bulk operations
        $metadata['type'] = $metadata['type'] ?? 'bulk';
        
        $batchSize = config('sms.bulk.batch_size', 100);
        $batchDelay = config('sms.bulk.batch_delay', 1);
        
        try {
            // Process recipients in batches
            $recipientChunks = array_chunk($recipients, $batchSize);
            
            foreach ($recipientChunks as $chunkIndex => $chunk) {
                foreach ($chunk as $recipient) {
                    try {
                        // Extract phone number and any recipient-specific data
                        if (is_array($recipient)) {
                            $phone = $recipient['phone'] ?? null;
                            $recipientMetadata = array_merge($metadata, [
                                'recipient_name' => $recipient['name'] ?? null,
                                'recipient_type' => $recipient['recipient_type'] ?? null,
                                'related' => $recipient['related'] ?? null,
                            ]);
                        } else {
                            $phone = $recipient;
                            $recipientMetadata = $metadata;
                        }
                        
                        if (empty($phone)) {
                            $failed++;
                            $errors[] = 'Empty phone number for recipient';
                            continue;
                        }
                        
                        $log = $this->send($phone, $message, $recipientMetadata);
                        $logs->push($log);
                        
                        if ($log->isSent() || $log->isDelivered()) {
                            $successful++;
                        } else {
                            $failed++;
                        }
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = "Failed to send to {$phone}: " . $e->getMessage();
                        Log::warning('Bulk SMS individual send failed', [
                            'phone' => $phone ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Add delay between batches (except for the last batch)
                if ($chunkIndex < count($recipientChunks) - 1 && $batchDelay > 0) {
                    sleep($batchDelay);
                }
            }
        } catch (Exception $e) {
            Log::error('Bulk SMS operation failed', [
                'total_recipients' => count($recipients),
                'processed' => $successful + $failed,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [
            'total' => count($recipients),
            'successful' => $successful,
            'failed' => $failed,
            'logs' => $logs,
            'errors' => $errors,
        ];
    }

    /**
     * Create an SMS log entry in the database.
     * 
     * Stores recipient phone number, message content, timestamp, and delivery status.
     * 
     * @param string $phone Recipient phone number
     * @param string $message SMS message content
     * @param string $status Initial status (pending, sent, failed, delivered)
     * @param array $metadata Additional metadata to store
     * @return SmsLog The created SMS log entry
     * @throws Exception If log creation fails
     * 
     * Validates: Requirements 10.1, 10.2, 15.4
     */
    public function logSms(string $phone, string $message, string $status = 'pending', array $metadata = []): SmsLog
    {
        try {
            $data = [
                'phone' => $phone,
                'message' => $message,
                'status' => $status,
                'type' => $metadata['type'] ?? 'general',
            ];
            
            // Handle polymorphic relationship
            if (isset($metadata['related']) && is_object($metadata['related'])) {
                $data['related_type'] = get_class($metadata['related']);
                $data['related_id'] = $metadata['related']->id;
            }
            
            // Set sent_at timestamp if status is sent or delivered
            if (in_array($status, ['sent', 'delivered'])) {
                $data['sent_at'] = now();
            }
            
            $log = SmsLog::create($data);
            
            // Log to Laravel log if mock logging is enabled
            if ($this->driver === 'mock' && $this->mockConfig['enable_logging']) {
                Log::info('SMS Log Created', [
                    'log_id' => $log->id,
                    'phone' => $phone,
                    'status' => $status,
                    'type' => $data['type'],
                ]);
            }
            
            return $log;
        } catch (QueryException $e) {
            Log::error('Database error creating SMS log', [
                'phone' => $phone,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to create SMS log entry. Please try again.');
        } catch (Exception $e) {
            Log::error('Failed to create SMS log', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to create SMS log: ' . $e->getMessage());
        }
    }

    /**
     * Update the delivery status of an SMS log entry.
     * 
     * @param SmsLog $smsLog The SMS log entry to update
     * @param string $status New status (pending, sent, failed, delivered)
     * @return void
     * 
     * Validates: Requirements 10.4, 14.1
     */
    public function updateStatus(SmsLog $smsLog, string $status): void
    {
        try {
            $updateData = ['status' => $status];
            
            // Set appropriate timestamp based on status
            switch ($status) {
                case 'sent':
                    $updateData['sent_at'] = now();
                    break;
                case 'delivered':
                    // Keep sent_at if already set, otherwise set it now
                    if (!$smsLog->sent_at) {
                        $updateData['sent_at'] = now();
                    }
                    break;
                case 'failed':
                    // No additional timestamp needed
                    break;
            }
            
            $smsLog->update($updateData);
            
            // Log status update if mock logging is enabled
            if ($this->driver === 'mock' && $this->mockConfig['enable_logging']) {
                Log::info('SMS Status Updated', [
                    'log_id' => $smsLog->id,
                    'phone' => $smsLog->phone,
                    'old_status' => $smsLog->getOriginal('status'),
                    'new_status' => $status,
                ]);
            }
        } catch (QueryException $e) {
            Log::error('Database error updating SMS status', [
                'log_id' => $smsLog->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - status update failure shouldn't break the flow
        } catch (Exception $e) {
            Log::error('Failed to update SMS status', [
                'log_id' => $smsLog->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retry sending a failed SMS.
     * 
     * Creates a new log entry for the retry attempt.
     * 
     * @param SmsLog $smsLog The failed SMS log entry to retry
     * @return SmsLog The new SMS log entry for the retry attempt
     * @throws InvalidArgumentException If the SMS is not in failed status
     * @throws Exception If retry fails
     * 
     * Validates: Requirements 14.4, 14.5
     */
    public function retry(SmsLog $smsLog): SmsLog
    {
        // Only retry failed messages
        if (!$smsLog->isFailed()) {
            Log::warning('Attempted to retry non-failed SMS', [
                'log_id' => $smsLog->id,
                'status' => $smsLog->status,
            ]);
            throw new InvalidArgumentException('Can only retry failed SMS messages. This message has status: ' . $smsLog->status);
        }
        
        try {
            // Create metadata for retry attempt
            $metadata = [
                'type' => $smsLog->type ?? 'general',
                'retry_of' => $smsLog->id,
            ];
            
            // Preserve related model if exists
            if ($smsLog->related_type && $smsLog->related_id) {
                try {
                    $relatedClass = $smsLog->related_type;
                    $metadata['related'] = $relatedClass::find($smsLog->related_id);
                } catch (Exception $e) {
                    Log::warning('Could not load related model for SMS retry', [
                        'log_id' => $smsLog->id,
                        'related_type' => $smsLog->related_type,
                        'related_id' => $smsLog->related_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Log retry attempt
            if ($this->driver === 'mock' && $this->mockConfig['enable_logging']) {
                Log::info('SMS Retry Initiated', [
                    'original_log_id' => $smsLog->id,
                    'phone' => $smsLog->phone,
                ]);
            }
            
            // Send the SMS (creates new log entry)
            return $this->send($smsLog->phone, $smsLog->message, $metadata);
        } catch (QueryException $e) {
            Log::error('Database error during SMS retry', [
                'log_id' => $smsLog->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to retry SMS due to a database error. Please try again.');
        } catch (Exception $e) {
            Log::error('SMS retry failed', [
                'log_id' => $smsLog->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to retry SMS: ' . $e->getMessage());
        }
    }

    /**
     * Mock SMS sending implementation.
     * 
     * Simulates SMS sending with configurable delay and random failure rate.
     * Logs to database with the same structure as real SMS.
     * 
     * @param string $phone Recipient phone number
     * @param string $message SMS message content
     * @return array Result with 'success' boolean and optional 'error' message
     * 
     * Validates: Requirements 15.1, 15.2, 15.3, 15.4
     */
    public function mockSend(string $phone, string $message): array
    {
        // Simulate delivery delay
        $delay = $this->mockConfig['delivery_delay'] ?? 2;
        if ($delay > 0) {
            // Use usleep for sub-second delays in testing, sleep for production-like behavior
            usleep($delay * 100000); // 0.1 second per configured second for faster testing
        }
        
        // Simulate random failures based on configured failure rate
        $failureRate = $this->mockConfig['failure_rate'] ?? 10;
        $randomValue = mt_rand(1, 100);
        
        if ($randomValue <= $failureRate) {
            // Simulate failure
            $errorMessages = [
                'Network timeout',
                'Invalid phone number format',
                'Gateway temporarily unavailable',
                'Rate limit exceeded',
                'Insufficient balance',
            ];
            
            $error = $errorMessages[array_rand($errorMessages)];
            
            if ($this->mockConfig['enable_logging']) {
                Log::warning('Mock SMS Failed', [
                    'phone' => $phone,
                    'error' => $error,
                    'random_value' => $randomValue,
                    'failure_rate' => $failureRate,
                ]);
            }
            
            return [
                'success' => false,
                'error' => $error,
                'message' => 'Mock SMS delivery failed',
            ];
        }
        
        // Simulate success
        if ($this->mockConfig['enable_logging']) {
            Log::info('Mock SMS Sent Successfully', [
                'phone' => $phone,
                'message_length' => strlen($message),
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Mock SMS delivered successfully',
            'mock_id' => 'MOCK_' . uniqid(),
        ];
    }

    /**
     * Get SMS logs with filters.
     * 
     * @param array $filters Filter criteria (status, type, phone, date_from, date_to)
     * @return Collection Collection of SmsLog entries
     * 
     * Validates: Requirements 10.3
     */
    public function getLogs(array $filters = []): Collection
    {
        try {
            $query = SmsLog::query();
            
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (!empty($filters['phone'])) {
                $query->where('phone', 'like', "%{$filters['phone']}%");
            }
            
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
            
            return $query->orderBy('created_at', 'desc')->get();
        } catch (QueryException $e) {
            Log::error('Database error retrieving SMS logs', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve SMS logs', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Send result notification to student/parent.
     * 
     * @param Student $student The student to notify
     * @param array $results Exam results data
     * @return SmsLog|null The SMS log entry or null if no phone available
     */
    public function sendResultNotification(Student $student, array $results): ?SmsLog
    {
        $phone = $student->guardian_phone ?? $student->phone;
        
        if (!$phone) {
            return null;
        }
        
        $message = $this->buildResultMessage($student, $results);
        
        return $this->send($phone, $message, [
            'type' => 'result',
            'related' => $student,
        ]);
    }

    /**
     * Send payment confirmation.
     * 
     * @param Student $student The student to notify
     * @param array $paymentDetails Payment details
     * @return SmsLog|null The SMS log entry or null if no phone available
     */
    public function sendPaymentConfirmation(Student $student, array $paymentDetails): ?SmsLog
    {
        $phone = $student->guardian_phone ?? $student->phone;
        
        if (!$phone) {
            return null;
        }
        
        $message = $this->buildPaymentMessage($student, $paymentDetails);
        
        return $this->send($phone, $message, [
            'type' => 'payment',
            'related' => $student,
        ]);
    }

    /**
     * Send attendance alert.
     * 
     * @param Student $student The student to notify
     * @param float $percentage Attendance percentage
     * @return SmsLog|null The SMS log entry or null if no phone available
     */
    public function sendAttendanceAlert(Student $student, float $percentage): ?SmsLog
    {
        $phone = $student->guardian_phone ?? $student->phone;
        
        if (!$phone) {
            return null;
        }
        
        $message = "Dear Parent, {$student->name}'s attendance is {$percentage}%. Please ensure regular attendance.";
        
        return $this->send($phone, $message, [
            'type' => 'attendance',
            'related' => $student,
        ]);
    }

    /**
     * Log SMS message for audit trail.
     * 
     * @deprecated Use logSms() instead
     */
    public function logMessage(string $phone, string $message, ?string $type = 'general', $related = null): SmsLog
    {
        return $this->logSms($phone, $message, 'pending', [
            'type' => $type,
            'related' => $related,
        ]);
    }

    /**
     * Retry failed messages in bulk.
     * 
     * @param int $limit Maximum number of messages to retry
     * @return int Number of successfully retried messages
     */
    public function retryFailed(int $limit = 100): int
    {
        $failed = SmsLog::failed()
            ->where('created_at', '>=', now()->subDay())
            ->limit($limit)
            ->get();
        
        $retried = 0;
        
        foreach ($failed as $log) {
            try {
                $newLog = $this->retry($log);
                if ($newLog->isSent() || $newLog->isDelivered()) {
                    $retried++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retry SMS', [
                    'log_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $retried;
    }

    /**
     * Get recipients by filter criteria.
     * 
     * @param array $criteria Filter criteria (batch_id, course_id, class, year)
     * @return Collection Collection of recipient data
     */
    public function getRecipients(array $criteria): Collection
    {
        try {
            $query = Student::query();
            
            if (!empty($criteria['batch_id'])) {
                $query->where('batch_id', $criteria['batch_id']);
            }
            
            if (!empty($criteria['course_id'])) {
                $query->whereHas('batch', function ($q) use ($criteria) {
                    $q->where('course_id', $criteria['course_id']);
                });
            }
            
            if (!empty($criteria['class'])) {
                $query->where('class', $criteria['class']);
            }
            
            if (!empty($criteria['year'])) {
                $startOfYear = Carbon::create($criteria['year'], 1, 1)->startOfYear();
                $endOfYear = Carbon::create($criteria['year'], 12, 31)->endOfYear();
                $query->whereBetween('created_at', [$startOfYear, $endOfYear]);
            }
            
            return $query->get()->map(function ($student) {
                return [
                    'phone' => $student->guardian_phone ?? $student->phone,
                    'name' => $student->name,
                    'related' => $student,
                ];
            })->filter(fn($r) => !empty($r['phone']));
        } catch (QueryException $e) {
            Log::error('Database error retrieving SMS recipients', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            return collect();
        } catch (Exception $e) {
            Log::error('Failed to retrieve SMS recipients', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Send via configured gateway.
     * 
     * @param string $phone Recipient phone number
     * @param string $message SMS message content
     * @return bool Whether the send was successful
     */
    protected function sendViaGateway(string $phone, string $message): bool
    {
        if (!$this->gateway || !$this->apiKey) {
            Log::warning('SMS gateway not configured');
            return false;
        }
        
        // Gateway-specific implementations
        return match ($this->gateway) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            'nexmo' => $this->sendViaNexmo($phone, $message),
            'ssl_wireless' => $this->sendViaSslWireless($phone, $message),
            'bulk_sms_bd' => $this->sendViaBulkSmsBd($phone, $message),
            default => $this->sendViaGenericApi($phone, $message),
        };
    }

    protected function sendViaTwilio(string $phone, string $message): bool
    {
        // Twilio implementation placeholder
        return true;
    }

    protected function sendViaNexmo(string $phone, string $message): bool
    {
        // Nexmo implementation placeholder
        return true;
    }

    protected function sendViaSslWireless(string $phone, string $message): bool
    {
        // SSL Wireless (Bangladesh) implementation placeholder
        return true;
    }

    protected function sendViaBulkSmsBd(string $phone, string $message): bool
    {
        // Bulk SMS BD implementation placeholder
        return true;
    }

    protected function sendViaGenericApi(string $phone, string $message): bool
    {
        // Generic API implementation placeholder
        return true;
    }

    /**
     * Build result notification message.
     * 
     * @param Student $student The student
     * @param array $results Exam results data
     * @return string The formatted message
     */
    protected function buildResultMessage(Student $student, array $results): string
    {
        $examName = $results['exam_name'] ?? 'Exam';
        $marks = $results['marks'] ?? 0;
        $total = $results['total'] ?? 100;
        $grade = $results['grade'] ?? '';
        
        return "Dear Parent, {$student->name} scored {$marks}/{$total} ({$grade}) in {$examName}.";
    }

    /**
     * Build payment confirmation message.
     * 
     * @param Student $student The student
     * @param array $details Payment details
     * @return string The formatted message
     */
    protected function buildPaymentMessage(Student $student, array $details): string
    {
        $amount = $details['amount'] ?? 0;
        $receipt = $details['receipt_number'] ?? '';
        $balance = $details['balance'] ?? 0;
        
        $message = "Payment of Tk.{$amount} received for {$student->name}. Receipt: {$receipt}.";
        
        if ($balance > 0) {
            $message .= " Due: Tk.{$balance}";
        } elseif ($balance < 0) {
            $message .= " Advance: Tk." . abs($balance);
        }
        
        return $message;
    }

    /**
     * Check if the service is using mock implementation.
     * 
     * @return bool Whether mock driver is active
     */
    public function isMockEnabled(): bool
    {
        return $this->driver === 'mock';
    }

    /**
     * Get the current driver name.
     * 
     * @return string The driver name
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get mock configuration.
     * 
     * @return array The mock configuration
     */
    public function getMockConfig(): array
    {
        return $this->mockConfig;
    }
}
