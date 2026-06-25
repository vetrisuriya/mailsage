# Contributing to MailSage

Thank you for considering contributing! MailSage is an open-source project and
all contributions are welcome.

## How to Contribute

### Reporting Bugs
Open an issue on GitHub with:
- A clear description of the bug
- Steps to reproduce
- Expected vs actual behavior
- PHP version and environment details

### Suggesting Features
Open a GitHub issue tagged `enhancement` describing:
- The use case
- Proposed API or behavior
- Any alternative approaches considered

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Write tests for your changes
4. Ensure all tests pass: `composer test`
5. Ensure code style: `composer format`
6. Ensure PHPStan passes: `composer analyse`
7. Commit with a clear message
8. Push and open a pull request

## Development Setup

```bash
git clone https://github.com/vetrisuriya/mailsage.git
cd mailsage
composer install
composer test
```

## Code Style

MailSage follows PSR-12. Run Pint before committing:
```bash
composer format
```

## Testing

All new features must include tests. The project targets 95%+ code coverage.

```bash
composer test
composer test-coverage
```

## License

By contributing, you agree that your contributions will be licensed under
the MIT License.
