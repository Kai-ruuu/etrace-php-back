<?php

require_once __DIR__ . "/../Core/Logger.php";
require_once __DIR__ . "/../Core/Constants.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailingConfig
{
    public $host;
    public $port;
    public $username;
    public $password;
    public $smtpAuth;
    public $smtpSecure;
    
    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        bool $smtpAuth,
        $smtpSecure,
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->smtpAuth = $smtpAuth;
        $this->smtpSecure = $smtpSecure;
    }

    public static function buildFromEnv(): static
    {
        $host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $email = $_ENV['SMTP_MAIL'] ?? '';
        $pass = $_ENV['SMTP_PASS'] ?? '';

        if (!$email) {
            Logger::error(Logger::ERR_MAILING_SERVICE, "SMTP_MAIL is not set.");
            die();
        }

        if (!$pass) {
            Logger::error(Logger::ERR_MAILING_SERVICE, "SMTP_PASS is not set.");
            die();
        }

        return new static($host, $port, $email, $pass, true, PHPMailer::ENCRYPTION_STARTTLS);
    }
}

class MailingService
{
    protected PHPMailer $mail;

    public function __construct(MailingConfig $config)
    {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = $config->host;
        $this->mail->SMTPAuth = $config->smtpAuth;
        $this->mail->Username = $config->username;
        $this->mail->Password = $config->password;
        $this->mail->SMTPSecure = $config->smtpSecure;
        $this->mail->Port = $config->port;
    }

    public function send($to, $toName, $from, $subject, $body): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->setFrom($from, 'E-trace');
            $this->mail->addAddress($to, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            Logger::error(Logger::ERR_MAILING_SERVICE, $e->getMessage());
            return false;
        }
    }

    // tested and working
    public function sendNewlyAssignedMail($sysad, $targetUser): bool
    {
        $sRole  = "System Administrator";
        $sEmail = $sysad['email'];
        $tRole  = Role::getDisplay($targetUser['role']);
        $tEmail = $targetUser['email'];

        $subject = "You Have Been Assigned as {$tRole} on E-trace";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dcfce7; color:#16a34a; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>New Account</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        You have been assigned as a <strong>{$tRole}</strong> on E-trace by a <strong>{$sRole}</strong>. Your account is now active and ready to use.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Assigned by</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.5;'>{$sRole} &mdash; <a href='mailto:{$sEmail}' style='color:#16a34a; text-decoration:none;'>{$sEmail}</a></p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#fffbeb; border:1px solid #fde68a; border-left: 3px solid #f59e0b; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Not you?</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    If you believe this was sent to you by mistake — for example, due to an email typo — please contact the {$sRole} immediately so your account can be disabled.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$sEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$sRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$sEmail}' style='color:#16a34a; text-decoration:none;'>{$sEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($tEmail, $tEmail, $sEmail, $subject, $body);
    }

    // tested and working
    public function sendDisableMail($disablerUser, $targetUser): bool
    {
        $dRole  = Role::getDisplay($disablerUser['role']);
        $dEmail = $disablerUser['email'];
        $tRole  = Role::getDisplay($targetUser['role']);
        $tEmail = $targetUser['email'];

        $subject = "Your E-trace Account Has Been Disabled";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>

                            <!-- Header -->
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td>
                                                <span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span>
                                            </td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#fee2e2; color:#dc2626; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Disabled</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your <strong>{$tRole}</strong> account on E-trace has been <strong style='color:#dc2626;'>disabled</strong> by a <strong>{$dRole}</strong>.
                                    </p>

                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you believe this was a mistake, please reach out to the {$dRole} who performed this action directly.
                                    </p>

                                    <!-- Contact button -->
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$dEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>
                                                    Contact {$dRole}
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>
                                        Or copy this address: <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>
                                        This is an automated message from E-trace. Please do not reply directly to this email.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($tEmail, $tEmail, $dEmail, $subject, $body);
    }

    // tested and working
    public function sendEnableMail($enablerUser, $targetUser): bool
    {
        $dRole  = Role::getDisplay($enablerUser['role']);
        $dEmail = $enablerUser['email'];
        $tRole  = Role::getDisplay($targetUser['role']);
        $tEmail = $targetUser['email'];

        $subject = "Your E-trace Account Has Been Re-enabled";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>

                            <!-- Header -->
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td>
                                                <span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span>
                                            </td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dcfce7; color:#16a34a; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Re-enabled</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your <strong>{$tRole}</strong> account on E-trace has been <strong style='color:#16a34a;'>re-enabled</strong> by a <strong>{$dRole}</strong>. You can now log in and access your account again.
                                    </p>

                                    <!-- Info box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Re-enabled by</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.5;'>{$dRole} &mdash; <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a></p>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have any questions or concerns about your account, feel free to reach out to the {$dRole} directly.
                                    </p>

                                    <!-- Contact button -->
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$dEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>
                                                    Contact {$dRole}
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>
                                        Or copy this address: <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>
                                        This is an automated message from E-trace. Please do not reply directly to this email.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($tEmail, $tEmail, $dEmail, $subject, $body);
    }

    // tested and working
    public function sendCompanyUnderReviewMail($reviewerUser, $company): bool
    {
        $rRole  = Role::getDisplay($reviewerUser['role']);
        $rEmail = $reviewerUser['email'];
        $cEmail = $company['email'];

        $subject = "Your E-trace Company Appeal Has Been Accepted";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dbeafe; color:#2563eb; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Appeal Accepted</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your appeal has been reviewed and <strong style='color:#16a34a;'>accepted</strong> by a <strong>{$rRole}</strong>.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#eff6ff; border:1px solid #bfdbfe; border-left: 3px solid #2563eb; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Action required</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    Your account has been reinstated to <strong>Pending</strong> status. Please ensure all your submitted documents and information are complete and accurate.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have questions, you may contact the {$rRole} who accepted your appeal.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$rEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$rRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$rEmail}' style='color:#16a34a; text-decoration:none;'>{$rEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($cEmail, $cEmail, $rEmail, $subject, $body);
    }

    // tested and working
    public function sendCompanyVerifiedMail($reviewerUser, $company): bool
    {
        $rRole  = Role::getDisplay($reviewerUser['role']);
        $rEmail = $reviewerUser['email'];
        $cEmail = $company['email'];
        $isPstaff = $reviewerUser['role'] === Role::PSTAFF;

        $subject = "Your E-trace Company Account Has Been Verified";

        $fullyVerifiedNote = $isPstaff
            ? "Your account has been verified by both a <strong>System Administrator</strong> and a <strong>PESO Staff</strong> — you are now <strong style='color:#16a34a;'>fully verified</strong>."
            : "Your account has been verified by a <strong>{$rRole}</strong>. It will proceed to the next stage of verification.";

        $accessNote = $isPstaff
            ? "You can now manage your job postings — post new vacancies, close them, repost closed ones, or remove them entirely."
            : "Please wait for the next verification step. You will be notified once your account has been fully reviewed.";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dcfce7; color:#16a34a; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Verified</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>{$fullyVerifiedNote}</p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>What you can do now</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>{$accessNote}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have any questions, feel free to contact the {$rRole} who reviewed your account.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$rEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$rRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$rEmail}' style='color:#16a34a; text-decoration:none;'>{$rEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($cEmail, $cEmail, $rEmail, $subject, $body);
    }

    // tested and working
    public function sendCompanyRequirementRevisionMail($pstaff, $company, $requirementDisplay, $revisionRequestReason): bool
    {
        $pEmail  = $pstaff['email'];
        $pRole   = "PESO Staff";
        $tEmail  = $company['email'];

        $isVacancies = $requirementDisplay === 'List of Vacancies';
        $actionNote  = $isVacancies
            ? "Please log in to your E-trace account and update your <strong>List of Vacancies</strong> accordingly in the verification center."
            : "Please log in to your E-trace account, reupload the revised <strong>{$requirementDisplay}</strong> in the verification center, and ensure it meets the requirements.";

        $subject = "Revision Requested for Your {$requirementDisplay} — E-trace";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#fffbeb; color:#d97706; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Revision Requested</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        A <strong>{$pRole}</strong> has reviewed your submitted <strong>{$requirementDisplay}</strong> and is requesting a revision before your account can proceed with verification.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:16px;'>
                                        <tr>
                                            <td style='background-color:#f9fafb; border:1px solid #e5e7eb; border-left: 3px solid #d97706; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Requirement</p>
                                                <p style='margin:0; font-size:14px; font-weight:600; color:#111827;'>{$requirementDisplay}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f9fafb; border:1px solid #e5e7eb; border-left: 3px solid #d97706; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Reason for revision</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.5;'>{$revisionRequestReason}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#eff6ff; border:1px solid #bfdbfe; border-left: 3px solid #2563eb; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>What you need to do</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>{$actionNote}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have questions about this revision request, feel free to contact the {$pRole} who reviewed your submission.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$pEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$pRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$pEmail}' style='color:#16a34a; text-decoration:none;'>{$pEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($tEmail, $tEmail, $pEmail, $subject, $body);
    }

    // tested and working
    public function sendCompanyRequirementApprovedMail($pstaff, $company, $requirementDisplay): bool
    {
        $pEmail = $pstaff['email'];
        $pRole  = "PESO Staff";
        $tEmail = $company['email'];

        $subject = "Your {$requirementDisplay} Has Been Approved — E-trace";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dcfce7; color:#16a34a; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Requirement Approved</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your submitted <strong>{$requirementDisplay}</strong> has been <strong style='color:#16a34a;'>approved</strong> by a <strong>{$pRole}</strong>.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Approved requirement</p>
                                                <p style='margin:0; font-size:14px; font-weight:600; color:#111827;'>{$requirementDisplay}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        Please ensure all your other requirements are also complete and accurate to proceed with verification. If you have questions, feel free to contact the {$pRole} who reviewed your submission.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$pEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$pRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$pEmail}' style='color:#16a34a; text-decoration:none;'>{$pEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($tEmail, $tEmail, $pEmail, $subject, $body);
    }

    // tested and working
    public function sendCompanyRejectedMail($reviewerUser, $company, string $reason): bool
    {
        $rRole  = Role::getDisplay($reviewerUser['role']);
        $rEmail = $reviewerUser['email'];
        $cEmail = $company['email'];

        $subject = "Your E-trace Company Account Has Been Rejected";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#fee2e2; color:#dc2626; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Rejected</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your company account on E-trace has been <strong style='color:#dc2626;'>rejected</strong> by a <strong>{$rRole}</strong>.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f9fafb; border:1px solid #e5e7eb; border-left: 3px solid #dc2626; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Reason</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.5;'>{$reason}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#fffbeb; border:1px solid #fde68a; border-left: 3px solid #f59e0b; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>What you can do</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    You may write an <strong>appeal message</strong> through your E-trace account. Once your appeal has been reviewed and accepted, you will receive an email and your account will be given another chance to comply.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have questions about the rejection, contact the {$rRole} who reviewed your account.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$rEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$rRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$rEmail}' style='color:#16a34a; text-decoration:none;'>{$rEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($cEmail, $cEmail, $rEmail, $subject, $body);
    }

    public function sendAlumniUnderReviewMail($dean, $alumni): bool
    {
        $dRole  = "Dean";
        $dEmail = $dean['email'];
        $aEmail = $alumni['email'];

        $subject = "Your E-trace Alumni Appeal Has Been Accepted";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dbeafe; color:#2563eb; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Appeal Accepted</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your appeal has been reviewed and <strong style='color:#16a34a;'>accepted</strong> by your <strong>{$dRole}</strong>.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#eff6ff; border:1px solid #bfdbfe; border-left: 3px solid #2563eb; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Action required</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    Your account has been reinstated to <strong>Pending</strong> status. Please ensure all your submitted documents and information are complete and accurate.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have questions, you may contact the {$dRole} who accepted your appeal.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$dEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$dRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($aEmail, $aEmail, $dEmail, $subject, $body);
    }

    public function sendAlumniVerifiedMail($dean, $alumni): bool
    {
        $dRole  = "Dean";
        $dEmail = $dean['email'];
        $aEmail = $alumni['email'];

        $subject = "Your E-trace Alumni Account Has Been Verified";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#dcfce7; color:#16a34a; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Verified</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your alumni account on E-trace has been <strong style='color:#16a34a;'>verified</strong> by your <strong>{$dRole}</strong>. Welcome to the E-trace alumni community!
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-left: 3px solid #16a34a; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>What you can do now</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    You can now browse and search for job postings from verified companies on E-trace. Explore opportunities that match your course and career goals.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have any questions, feel free to reach out to your {$dRole}.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$dEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$dRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($aEmail, $aEmail, $dEmail, $subject, $body);
    }

    public function sendAlumniRejectedMail($dean, $alumni, string $reason): bool
    {
        $dRole  = "Dean";
        $dEmail = $dean['email'];
        $aEmail = $alumni['email'];

        $subject = "Your E-trace Alumni Account Has Been Rejected";

        $body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f9fafb; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f9fafb; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:8px; border: 1px solid #e5e7eb; overflow:hidden;'>
                            <tr>
                                <td style='background-color:#ffffff; padding: 24px 32px; border-bottom: 1px solid #e5e7eb;'>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td><span style='font-size:18px; font-weight:700; color:#111827; letter-spacing:1px;'>E-trace</span></td>
                                            <td align='right'>
                                                <span style='display:inline-block; background-color:#fee2e2; color:#dc2626; font-size:11px; font-weight:600; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;'>Account Rejected</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin:0 0 16px; font-size:14px; color:#6b7280;'>Hello,</p>
                                    <p style='margin:0 0 24px; font-size:15px; color:#111827; line-height:1.6;'>
                                        Your alumni account on E-trace has been <strong style='color:#dc2626;'>rejected</strong> by your <strong>{$dRole}</strong>.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#f9fafb; border:1px solid #e5e7eb; border-left: 3px solid #dc2626; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>Reason</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.5;'>{$reason}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;'>
                                        <tr>
                                            <td style='background-color:#fffbeb; border:1px solid #fde68a; border-left: 3px solid #f59e0b; border-radius:6px; padding:16px;'>
                                                <p style='margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;'>What you can do</p>
                                                <p style='margin:0; font-size:14px; color:#374151; line-height:1.6;'>
                                                    You may write an <strong>appeal message</strong> through your E-trace account. Once your appeal has been reviewed and accepted by your Dean, you will receive an email and your account will be given another chance to comply.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0 0 24px; font-size:14px; color:#6b7280; line-height:1.6;'>
                                        If you have questions about the rejection, contact your {$dRole} directly.
                                    </p>
                                    <table cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
                                        <tr>
                                            <td style='background-color:#16a34a; border-radius:6px;'>
                                                <a href='mailto:{$dEmail}' style='display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none;'>Contact {$dRole}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>Or copy this address: <a href='mailto:{$dEmail}' style='color:#16a34a; text-decoration:none;'>{$dEmail}</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;'>
                                    <p style='margin:0; font-size:12px; color:#9ca3af; line-height:1.5;'>This is an automated message from E-trace. Please do not reply directly to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $this->send($aEmail, $aEmail, $dEmail, $subject, $body);
    }
}