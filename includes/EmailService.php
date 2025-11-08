<?php
/**
 * EmailService Class
 * Handles SMTP email sending with detailed error reporting
 */

class EmailService {
    private $settings;
    private $smtpLog = [];
    private $socket = null;

    public function __construct($settings = null) {
        if ($settings) {
            $this->settings = $settings;
        } else {
            $emailSettings = new EmailSettings();
            $this->settings = $emailSettings->getRawSettings();
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection() {
        try {
            $this->smtpLog = [];
            $this->smtpLog[] = "Testing SMTP connection to {$this->settings['smtp_host']}:{$this->settings['smtp_port']}";

            // Connect to SMTP server
            $this->connect();

            // Authenticate if credentials provided
            if (!empty($this->settings['smtp_username']) && !empty($this->settings['smtp_password'])) {
                $this->authenticate();
            }

            // Close connection
            $this->sendCommand("QUIT");
            $this->disconnect();

            $this->smtpLog[] = "✓ Connection test successful";

            return [
                'success' => true,
                'message' => 'SMTP connection successful',
                'log' => $this->smtpLog
            ];
        } catch (Exception $e) {
            $this->disconnect();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log' => $this->smtpLog
            ];
        }
    }

    /**
     * Send weekly report email
     */
    public function sendWeeklyReport($reportData) {
        try {
            $this->smtpLog = [];

            // Generate email content
            $subject = "Weekly Substitute Report - " . date('F d, Y');
            $htmlBody = $this->generateWeeklyReportHTML($reportData);
            $textBody = $this->generateWeeklyReportText($reportData);

            // Send email
            $result = $this->sendEmail(
                $this->settings['recipient_email'],
                $subject,
                $htmlBody,
                $textBody
            );

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log' => $this->smtpLog
            ];
        }
    }

    /**
     * Send email via SMTP
     */
    private function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        try {
            // Connect and authenticate
            $this->connect();

            if (!empty($this->settings['smtp_username']) && !empty($this->settings['smtp_password'])) {
                $this->authenticate();
            }

            // Send email
            $domain = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
            $from = $this->settings['smtp_username'] ?: 'noreply@' . $domain;
            $fromName = SITE_NAME;

            $this->sendCommand("MAIL FROM: <{$from}>");
            $this->sendCommand("RCPT TO: <{$to}>");
            $this->sendCommand("DATA");

            // Build email headers and body
            $boundary = md5(time());
            $headers = "From: {$fromName} <{$from}>\r\n";
            $headers .= "To: <{$to}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "\r\n";

            // Email body
            $message = "--{$boundary}\r\n";
            if ($textBody) {
                $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $message .= $textBody . "\r\n";
                $message .= "--{$boundary}\r\n";
            }
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlBody . "\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= ".\r\n";

            // Send headers and body
            fputs($this->socket, $headers . $message);
            $response = $this->readResponse();
            $this->smtpLog[] = "DATA Response: " . $response;

            if (!$this->isSuccessResponse($response)) {
                throw new Exception("Failed to send email data: " . $response);
            }

            // Close connection
            $this->sendCommand("QUIT");
            $this->disconnect();

            $this->smtpLog[] = "✓ Email sent successfully";

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'log' => $this->smtpLog
            ];
        } catch (Exception $e) {
            $this->disconnect();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log' => $this->smtpLog
            ];
        }
    }

    /**
     * Connect to SMTP server
     */
    private function connect() {
        $host = $this->settings['smtp_host'];
        $port = $this->settings['smtp_port'];
        $encryption = $this->settings['smtp_encryption'];

        // Create socket connection
        $errno = 0;
        $errstr = '';

        if ($encryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $this->smtpLog[] = "Connecting to {$host}:{$port}...";
        $this->socket = fsockopen($host, $port, $errno, $errstr, 30);

        if (!$this->socket) {
            throw new Exception("Failed to connect: {$errstr} ({$errno})");
        }

        $response = $this->readResponse();
        $this->smtpLog[] = "Server: " . $response;

        if (!$this->isSuccessResponse($response)) {
            throw new Exception("Server connection failed: " . $response);
        }

        // Send EHLO
        $domain = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
        $response = $this->sendCommand("EHLO {$domain}");

        // If TLS encryption, start TLS
        if ($encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }
            $this->smtpLog[] = "✓ TLS encryption enabled";
            // Send EHLO again after STARTTLS
            $this->sendCommand("EHLO {$domain}");
        }
    }

    /**
     * Authenticate with SMTP server
     */
    private function authenticate() {
        $this->smtpLog[] = "Authenticating...";
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->settings['smtp_username']));
        $response = $this->sendCommand(base64_encode($this->settings['smtp_password']));

        if (!$this->isSuccessResponse($response)) {
            throw new Exception("Authentication failed: " . $response);
        }

        $this->smtpLog[] = "✓ Authentication successful";
    }

    /**
     * Send SMTP command and get response
     */
    private function sendCommand($command) {
        // Don't log passwords
        $logCommand = (strpos($command, base64_encode($this->settings['smtp_password'] ?? '')) !== false)
            ? "[PASSWORD]"
            : $command;

        fputs($this->socket, $command . "\r\n");
        $response = $this->readResponse();
        $this->smtpLog[] = "→ {$logCommand}";
        $this->smtpLog[] = "← {$response}";

        if (!$this->isSuccessResponse($response)) {
            throw new Exception("Command failed: {$command} - Response: {$response}");
        }

        return $response;
    }

    /**
     * Read response from SMTP server
     */
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // Multi-line responses end when the 4th character is a space
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * Check if response is successful (2xx or 3xx status)
     */
    private function isSuccessResponse($response) {
        $code = substr($response, 0, 3);
        return $code[0] === '2' || $code[0] === '3';
    }

    /**
     * Disconnect from SMTP server
     */
    private function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Generate HTML email template for weekly report
     */
    private function generateWeeklyReportHTML($data) {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $entries = $data['entries'];
        $totalHours = $data['total_hours'];
        $totalAmount = $data['total_amount'];
        $substituteSummary = $data['substitute_summary'];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
                .header p { margin: 10px 0 0; font-size: 14px; opacity: 0.9; }
                .content { padding: 30px; }
                .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center; }
                .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
                .stat-value { font-size: 24px; font-weight: 700; color: #1e40af; }
                .section { margin: 25px 0; }
                .section-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #1e40af; }
                table { width: 100%; border-collapse: collapse; font-size: 13px; }
                th { background: #f1f5f9; padding: 10px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
                td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📊 Weekly Substitute Report</h1>
                    <p><?php echo date('F d, Y', strtotime($startDate)); ?> - <?php echo date('F d, Y', strtotime($endDate)); ?></p>
                </div>

                <div class="content">
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-label">Total Hours</div>
                            <div class="stat-value"><?php echo number_format($totalHours, 1); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total Amount</div>
                            <div class="stat-value">$<?php echo number_format($totalAmount, 2); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Substitute Summary</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Substitute</th>
                                    <th>Hours</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($substituteSummary as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td><?php echo number_format($sub['hours'], 1); ?></td>
                                    <td>$<?php echo number_format($sub['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="section">
                        <div class="section-title">Detailed Entries (<?php echo count($entries); ?> total)</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Substitute</th>
                                    <th>Teacher</th>
                                    <th>Hours</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?php echo date('m/d', strtotime($entry['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($entry['substitute_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['teacher_name']); ?></td>
                                    <td><?php echo number_format($entry['hours'], 1); ?></td>
                                    <td>$<?php echo number_format($entry['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="footer">
                    <p>This is an automated weekly report from <?php echo SITE_NAME; ?></p>
                    <p>Generated on <?php echo date('F d, Y \a\t g:i A'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate plain text version of weekly report
     */
    private function generateWeeklyReportText($data) {
        $output = "WEEKLY SUBSTITUTE REPORT\n";
        $output .= str_repeat("=", 50) . "\n";
        $output .= "Period: " . date('F d, Y', strtotime($data['start_date'])) . " - " . date('F d, Y', strtotime($data['end_date'])) . "\n\n";
        $output .= "Total Hours: " . number_format($data['total_hours'], 1) . "\n";
        $output .= "Total Amount: $" . number_format($data['total_amount'], 2) . "\n\n";

        $output .= "SUBSTITUTE SUMMARY\n";
        $output .= str_repeat("-", 50) . "\n";
        foreach ($data['substitute_summary'] as $sub) {
            $output .= sprintf("%-30s %6.1f hrs  $%7.2f\n",
                $sub['name'], $sub['hours'], $sub['amount']);
        }

        $output .= "\n" . str_repeat("=", 50) . "\n";
        $output .= "This is an automated report from " . SITE_NAME . "\n";

        return $output;
    }

    /**
     * Get SMTP log
     */
    public function getLog() {
        return $this->smtpLog;
    }
}
