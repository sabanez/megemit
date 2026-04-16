# WooCommerce Dynamic Pricing - Development Notes

## Security Scan Commands

To run a security scan on the plugin:

```bash
# Build the distribution package
npm run build

# Run qit security test
qit run:security woocommerce-dynamic-pricing --zip=./dist/woocommerce-dynamic-pricing.zip

# Check results (replace with actual test run ID)
qit get [TEST_RUN_ID]
```

When you say "security scan", I should run these commands to test the plugin for security vulnerabilities.
