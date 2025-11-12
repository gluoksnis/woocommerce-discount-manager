# Contributing to WooCommerce Discount Manager

Thank you for your interest in contributing! This document provides guidelines for contributing to this project.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include:

- **Clear title** and description
- **Steps to reproduce** the behavior
- **Expected behavior**
- **Actual behavior**
- **Screenshots** if applicable
- **Environment details**:
  - WordPress version
  - WooCommerce version
  - PHP version
  - Plugin version

### Suggesting Features

Feature requests are welcome! Please provide:

- **Clear description** of the feature
- **Use case** - why is it needed?
- **Proposed solution** if you have one
- **Alternatives considered**

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Test thoroughly**
5. **Commit with clear messages**: `git commit -m 'Add amazing feature'`
6. **Push to your fork**: `git push origin feature/amazing-feature`
7. **Open a Pull Request**

## Development Guidelines

### Code Style

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use tabs for indentation (WordPress standard)
- Class names use `WCDM_` prefix
- Use meaningful variable and function names
- Comment complex logic

### Security

- Always sanitize input: `sanitize_text_field()`, `intval()`, etc.
- Always escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Use WordPress nonces for forms
- Check user capabilities: `current_user_can('manage_options')`
- Use prepared statements for database queries

### Testing

Before submitting:

- [ ] Test on a fresh WordPress install
- [ ] Test with WooCommerce active
- [ ] Test all forms and functions
- [ ] Check for PHP errors (enable WP_DEBUG)
- [ ] Test with different product types
- [ ] Verify nonce security
- [ ] Test bulk operations
- [ ] Verify translations work

### File Organization

```
includes/          # PHP classes
assets/css/        # Stylesheets
assets/js/         # JavaScript files
```

### Naming Conventions

- **Classes**: `WCDM_Class_Name`
- **Functions**: `wcdm_function_name()`
- **Variables**: `$variable_name`
- **Constants**: `WCDM_CONSTANT_NAME`

### Translation

- Use text domain: `wc-discount-manager`
- Wrap all strings: `__('Text', 'wc-discount-manager')`
- For variables: `sprintf(__('Text %s', 'wc-discount-manager'), $var)`

## Commit Messages

Use clear, descriptive commit messages:

```
Good:
- Add category filter to discounted products list
- Fix: CSV upload validation error
- Update: Improve bulk action performance

Bad:
- fixed stuff
- update
- changes
```

## Version Numbers

We use [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (0.1.0): New features, backwards compatible
- **PATCH** (0.0.1): Bug fixes, backwards compatible

## Code of Conduct

- Be respectful and professional
- Welcome newcomers
- Accept constructive criticism
- Focus on what's best for the project
- Show empathy towards others

## Questions?

If you have questions:
- Check existing issues and documentation
- Open a new issue with the "question" label
- Be clear and provide context

## License

By contributing, you agree that your contributions will be licensed under GPL v2 or later.

---

Thank you for contributing to WooCommerce Discount Manager! 🎉
