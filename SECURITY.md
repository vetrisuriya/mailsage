# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |

## Reporting a Vulnerability

If you discover a security vulnerability in MailSage, please **do not** open
a public GitHub issue.

Instead, report it privately via GitHub's Security Advisories feature or
email the maintainers directly.

We will:
- Acknowledge receipt within 48 hours
- Provide an estimated fix timeline within 7 days
- Notify you when the fix is released

## Security Notes

MailSage processes potentially untrusted email content. Please be aware:

- **Attachment saving**: Always validate the save directory before calling
  `saveAttachments()`. Never save to a web-accessible directory.
- **Dangerous attachments**: Use `attachmentRisk()` to check risk level before
  processing attachments further.
- **Phishing**: Always check `isPhishing()` and `securityReport()` for emails
  from unknown senders.
- **No code execution**: MailSage never executes attachment content or scripts.
- **Local processing**: All analysis runs locally — no data is sent externally.
