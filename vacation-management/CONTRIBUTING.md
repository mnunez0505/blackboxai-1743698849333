# Contributing to Employee Vacation Management System

First off, thank you for considering contributing to the Employee Vacation Management System! It's people like you that make this system better for everyone.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the issue list as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* Use a clear and descriptive title
* Describe the exact steps which reproduce the problem
* Provide specific examples to demonstrate the steps
* Describe the behavior you observed after following the steps
* Explain which behavior you expected to see instead and why
* Include screenshots if possible
* Include error messages and stack traces

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* Use a clear and descriptive title
* Provide a step-by-step description of the suggested enhancement
* Provide specific examples to demonstrate the steps
* Describe the current behavior and explain which behavior you expected to see instead
* Explain why this enhancement would be useful
* List some other applications where this enhancement exists

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Follow the PHP coding style
* Include thoughtfully-worded, well-structured tests
* Document new code
* End all files with a newline

## Development Process

1. Fork the repo
2. Create a new branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes
4. Run tests:
   ```bash
   php tests/test_system.php
   ```
5. Commit your changes:
   ```bash
   git commit -m "Description of your changes"
   ```
6. Push to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```
7. Create a Pull Request

## Coding Standards

### PHP

* Follow PSR-12 coding standard
* Use meaningful variable and function names
* Comment your code when necessary
* Keep functions focused and small
* Use type hints where possible
* Handle errors appropriately

### JavaScript

* Use ES6+ features
* Follow Airbnb JavaScript Style Guide
* Use meaningful variable and function names
* Comment complex logic
* Handle errors appropriately

### CSS

* Use meaningful class names
* Follow BEM naming convention
* Keep selectors specific but not too specific
* Comment complex styles
* Maintain responsive design principles

### SQL

* Use uppercase for SQL keywords
* Use meaningful table and column names
* Comment complex queries
* Include appropriate indexes
* Follow Oracle best practices

## Documentation

* Keep README.md updated
* Document all functions and classes
* Update CHANGELOG.md for significant changes
* Include JSDoc comments for JavaScript functions
* Update API documentation when endpoints change

## Testing

* Write unit tests for new features
* Ensure all tests pass before submitting PR
* Include integration tests where necessary
* Test across different browsers
* Test responsive design on different devices

## Version Control

### Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line

Example:
```
Add vacation approval notification system

- Implement email notifications for approvals/rejections
- Add WhatsApp integration for instant notifications
- Update user preferences for notification methods

Fixes #123
```

### Branch Naming

* Feature branches: `feature/description`
* Bug fixes: `fix/description`
* Documentation: `docs/description`
* Performance improvements: `perf/description`

## Release Process

1. Update version number in config.php
2. Update CHANGELOG.md
3. Create release notes
4. Tag the release
5. Deploy to staging
6. Test thoroughly
7. Deploy to production

## Setting Up Development Environment

1. Clone the repository
2. Copy config.example.php to config.php
3. Set up Oracle database
4. Install dependencies
5. Run setup script
6. Configure email settings
7. Set up test data

## Additional Notes

### Issue and Pull Request Labels

* `bug`: Something isn't working
* `enhancement`: New feature or request
* `documentation`: Improvements or additions to documentation
* `good first issue`: Good for newcomers
* `help wanted`: Extra attention is needed
* `invalid`: Something's wrong
* `question`: Further information is requested
* `wontfix`: This will not be worked on

## Recognition

Contributors who submit significant improvements will be:
* Listed in the README.md
* Mentioned in release notes
* Given credit in relevant documentation

Thank you for contributing to make this project better!